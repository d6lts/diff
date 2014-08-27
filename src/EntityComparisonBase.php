<?php

/**
 * @file
 * Contains \Drupal\diff\EntityComparisonBase.
 */

namespace Drupal\diff;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;
use Drupal\Component\Diff\Diff;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Mail\MailFormatHelper;

/**
 * Builds an array of data out of entity fields.
 *
 * The resulted data is then passed through the Diff component and
 * displayed on the UI and represents the differences between two entities.
 */
class EntityComparisonBase extends ControllerBase {

  /**
   * DiffFormatter service.
   *
   * @var \Drupal\diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * The diff field builder plugin manager.
   *
   * @var \Drupal\diff\DiffBuilderManager
   */
  protected $diffBuilderManager;

  /**
   * Wrapper object for writing/reading simple configuration from diff.settings.yml
   */
  protected $config;

  /**
   * Wrapper object for writing/reading simple configuration from diff.plugins.yml
   */
  protected $pluginsConfig;

  /**
   * A list of all the field types from the system and their definitions.
   */
  protected $fieldTypeDefinitions;

  /**
   * Represents non breaking space HTML character entity marked as safe markup.
   */
  protected $nonBreakingSpace;

  /**
   * Constructs an EntityComparisonBase object.
   *
   * @param DiffFormatter $diff_formatter
   *   Diff formatter service.
   * @param DateFormatter $date
   *   DateFormatter service.
   * @param PluginManagerInterface $plugin_manager
   *   The Plugin manager service.
   * @param DiffBuilderManager $diffBuilderManager
   *   The diff field builder plugin manager.
   */
  public function __construct(DiffFormatter $diff_formatter, DateFormatter $date, PluginManagerInterface $plugin_manager, DiffBuilderManager $diffBuilderManager) {
    $this->diffFormatter = $diff_formatter;
    $this->date = $date;
    $this->fieldTypeDefinitions = $plugin_manager->getDefinitions();
    $this->config = $this->config('diff.settings');
    $this->pluginsConfig = $this->config('diff.plugins');
    $this->nonBreakingSpace = SafeMarkup::set('&nbsp');
    $this->diffBuilderManager = $diffBuilderManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.diff.formatter'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.diff.builder')
    );
  }

  /**
   * Transforms an entity into an array of strings.
   *
   * Parses an entity's fields and for every field it builds an array of string
   * to be compared. Basically this function transforms an entity into an array
   * of strings.
   *
   * @param ContentEntityInterface $entity
   *   An entity containing fields.
   *
   * @return array
   *   Array of strings resulted by parsing the entity.
   */
  private function parseEntity(ContentEntityInterface $entity) {
    $result = array();
    $entity_type_id = $entity->getEntityTypeId();
    // Load all entity base fields.
    $entity_base_fields = $this->entityManager()->getBaseFieldDefinitions($entity_type_id);
    // Loop through entity fields and transform every FieldItemList object
    // into an array of strings according to field type specific settings.
    foreach ($entity as $field_items) {
      $field_type = $field_items->getFieldDefinition()->getType();
      $plugin_config = $this->pluginsConfig->get($field_type);
      $plugin = NULL;
      if ($plugin_config && $plugin_config['type'] != 'hidden') {
        $plugin = $this->diffBuilderManager->createInstance($plugin_config['type'], $plugin_config['settings']);
      }
      if ($plugin) {
        // Configurable field. It is the responsibility of the class extending
        // this class to hide some configurable fields from comparison. This
        // class compares all configurable fields.
        if (!array_key_exists($field_items->getName(), $entity_base_fields)) {
          $build = $plugin->build($field_items);
          if (!empty($build)) {
            $result[$field_items->getName()] = $build;
          }
        }
        // If field is one of the entity base fields take visibility settings from
        // diff admin config page. This means that the visibility of these fields
        // is controlled per entity type.
        else {
          // Check if this field needs to be compared.
          $config_key = 'entity.' . $entity_type_id . '.' . $field_items->getName();
          $enabled = $this->config->get($config_key);
          if ($enabled) {
            $build = $plugin->build($field_items);
            if (!empty($build)) {
              $result[$field_items->getName()] = $build;
            }
          }
        }
      }
    }

    return $result;
  }

  /**
   * This method should return an array of items ready to be compared.
   *
   * @param ContentEntityInterface $left_entity
   *   The left entity
   * @param ContentEntityInterface $right_entity
   *   The right entity
   *
   * @return array
   *   Items ready to be compared by the Diff component.
   */
  public function compareRevisions(ContentEntityInterface $left_entity, ContentEntityInterface $right_entity) {
    $result = array();

    $left_values = $this->parseEntity($left_entity);
    $right_values = $this->parseEntity($right_entity);

    foreach ($left_values as $field_name => $values) {
      $field_definition = $left_entity->getFieldDefinition($field_name);
      // Get the compare settings for this field type.
      $compare_settings = $this->pluginsConfig->get($field_definition->getType());
      $result[$field_name] = array(
        '#name' => ($compare_settings['settings']['show_header'] == 1) ? $field_definition->getLabel() : '',
        '#settings' => $compare_settings,
      );

      // Fields which exist on the right entity also.
      if (isset($right_values[$field_name])) {
        $result[$field_name] += $this->combineFields($left_values[$field_name], $right_values[$field_name]);
        // Unset the field from the right entity so that we know if the right
        // entity has any fields that left entity doesn't have.
        unset($right_values[$field_name]);
      }
      // This field exists only on the left entity.
      else {
        $result[$field_name] += $this->combineFields($left_values[$field_name], array());
      }
    }

    // Fields which exist only on the right entity.
    foreach ($right_values as $field_name => $values) {
      $field_definition = $right_entity->getFieldDefinition($field_name);
      $compare_settings = $this->config->get('field_types.' . $field_definition->getType());
      $result[$field_name] = array(
        '#name' => ($compare_settings['show_header'] == 1) ? $field_definition->getLabel() : '',
        '#settings' => $compare_settings,
      );
      $result[$field_name] += $this->combineFields(array(), $right_values[$field_name]);
    }

    // Field rows. Recurse through all child elements.
    foreach (Element::children($result) as $key) {
      $result[$key]['#states'] = array();

      // Ensure that the element follows the #states format.
      if (isset($result[$key]['#left'])) {
        $result[$key]['#states']['raw']['#left'] = $result[$key]['#left'];
        unset($result[$key]['#left']);
      }
      if (isset($result[$key]['#right'])) {
        $result[$key]['#states']['raw']['#right'] = $result[$key]['#right'];
        unset($result[$key]['#right']);
      }

      $field_settings = $result[$key]['#settings'];

      if (!empty($field_settings['settings']['markdown'])) {
        $result[$key]['#states']['raw_plain']['#left'] = $this->applyMarkdown($field_settings['settings']['markdown'], $result[$key]['#states']['raw']['#left']);
        $result[$key]['#states']['raw_plain']['#right'] = $this->applyMarkdown($field_settings['settings']['markdown'], $result[$key]['#states']['raw']['#right']);
      }
      // In case the settings are not loaded correctly use drupal_html_to_text
      // to avoid any possible notices when a user clicks on markdown.
      else {
        $result[$key]['#states']['raw_plain']['#left'] = $this->applyMarkdown('drupal_html_to_text', $result[$key]['#states']['raw']['#left']);
        $result[$key]['#states']['raw_plain']['#right'] = $this->applyMarkdown('drupal_html_to_text', $result[$key]['#states']['raw']['#right']);
      }
    }

    // Process the array (split the strings into single line strings)
    // and get line counts per field.
    array_walk($result, array($this, 'processStateLine'));

    return $result;
  }

  /**
   * Combine two fields into an array with keys '#left' and '#right'.
   *
   * @param $left_values
   *   Entity field formatted into an array of strings.
   * @param $right_values
   *   Entity field formatted into an array of strings.
   *
   * @return array
   *   Array resulted after combining the left and right values.
   */
  protected function combineFields($left_values, $right_values) {
    $result = array(
      '#left' => array(),
      '#right' => array(),
    );
    $max = max(array(count($left_values), count($right_values)));
    for ($delta = 0; $delta < $max; $delta++) {
      if (isset($left_values[$delta])) {
        $value = $left_values[$delta];
        $result['#left'][] = is_array($value) ? implode("\n", $value) : $value;
      }
      if (isset($right_values[$delta])) {
        $value = $right_values[$delta];
        $result['#right'][] = is_array($value) ? implode("\n", $value) : $value;
      }
    }

    // If a field has multiple values combine them into one single string.
    $result['#left'] = implode("\n", $result['#left']);
    $result['#right'] = implode("\n", $result['#right']);

    return $result;
  }

  /**
   * Prepare the table rows for theme 'table'.
   *
   * @param string $a
   *   The source string to compare from.
   * @param string $b
   *   The target string to compare to.
   * @param boolean $show_header
   *   Display diff context headers. For example, "Line x".
   * @param array $line_stats
   *   This structure tracks line numbers across multiple calls to DiffFormatter.
   *
   * @return array
   *   Array of rows usable with theme('table').
   */
  protected function getRows($a, $b, $show_header = FALSE, &$line_stats = NULL) {
    $a = is_array($a) ? $a : explode("\n", $a);
    $b = is_array($b) ? $b : explode("\n", $b);

    // Temporary workaround: when comparing with an empty string, Diff Component
    // returns a change OP instead of an add OP.
    if (count($a) == 1 && $a[0] == "") {
      $a = array();
    }

    if (!isset($line_stats)) {
      $line_stats = array(
        'counter' => array('x' => 0, 'y' => 0),
        'offset' => array('x' => 0, 'y' => 0),
      );
    }

    // Header is the line counter.
    $this->diffFormatter->show_header = $show_header;
    $diff = new Diff($a, $b);

    return $this->diffFormatter->format($diff);
  }

  /**
   * Splits the strings into lines and counts the resulted number of lines.
   *
   * @param $diff
   *   Array of strings.
   */
  function processStateLine(&$diff) {
    foreach ($diff['#states'] as $state => $data) {
      if (isset($data['#left'])) {
        if (is_string($data['#left'])) {
          $diff['#states'][$state]['#left'] = explode("\n", $data['#left']);
        }
        $diff['#states'][$state]['#count_left'] = count($diff['#states'][$state]['#left']);
      }
      else {
        $diff['#states'][$state]['#count_left'] = 0;
      }
      if (isset($data['#right'])) {
        if (is_string($data['#right'])) {
          $diff['#states'][$state]['#right'] = explode("\n", $data['#right']);
        }
        $diff['#states'][$state]['#count_right'] = count($diff['#states'][$state]['#right']);
      }
      else {
        $diff['#states'][$state]['#count_right'] = 0;
      }
    }
  }

  /**
   * Applies a markdown function to a string.
   *
   * @param $markdown
   *   Key of the markdown function to be applied to the items.
   *   One of drupal_html_to_text, filter_xss, filter_xss_all.
   * @param $items
   *   String to be processed.
   * 
   * @return array|string
   *   Result after markdown was applied on $items.
   */
  protected function applyMarkdown($markdown, $items) {
    if (!$markdown) {
      return $items;
    }

    if ($markdown == 'drupal_html_to_text') {
      return trim(MailFormatHelper::htmlToText($items), "\n");
    }
    elseif ($markdown == 'filter_xss') {
      return trim(Xss::filter($items), "\n");
    }
    elseif ($markdown == 'filter_xss_all') {
      return trim(Xss::filter($items, array()), "\n");
    }
    else {
      return $items;
    }
  }

}