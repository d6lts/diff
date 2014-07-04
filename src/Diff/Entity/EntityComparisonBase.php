<?php

/**
 * @file
 * Contains \Drupal\diff\EntityComparisonBase.
 */

namespace Drupal\diff\Diff\Entity;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\diff\Diff\FieldDiffManager;
use Drupal\Core\Render\Element;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Component\Diff\Diff;
use Drupal\Core\Datetime\Date;
use Drupal\Component\Utility\Xss;


/**
 * Class EntityComparisonBase
 *   Builds an array of data to be passed through the Diff component and
 *   displayed on the UI representing the differences between two entities.
 */
class EntityComparisonBase extends ControllerBase {

  /**
   * Field diff manager negotiated service.
   *
   * @var \Drupal\diff\Diff\FieldDiffManager
   */
  protected $fieldDiffManager;

  /**
   * DiffFormatter service.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $date;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an EntityComparisonBase object.
   *
   * @param FieldDiffManager $field_diff_manager
   *   Field diff manager negotiated service.
   * @param DiffFormatter $diff_formatter
   *   Diff formatter service.
   * @param Date $date
   *   Date service.
   * @param ConfigFactoryInterface $config_factory
   *   Config Factory service
   */
  public function __construct(FieldDiffManager $field_diff_manager, DiffFormatter $diff_formatter, Date $date, ConfigFactoryInterface $config_factory) {
    $this->fieldDiffManager = $field_diff_manager;
    $this->diffFormatter = $diff_formatter;
    $this->date = $date;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.manager'),
      $container->get('diff.formatter'),
      $container->get('date'),
      $container->get('config.factory')
    );
  }

  /**
   * Parses an entity's fields and for every field it builds an array of string
   * to be compared. Basically this function transforms and entity into an array
   * of strings.
   *
   * @param RevisionableInterface $entity
   *   An entity containing fields.
   * @return array
   *   Array of strings resulted by parsing the entity.
   *   @todo Insert here some code as example here.
   */
  private function parseEntity(RevisionableInterface $entity) {
    $result = array();

    // @todo provide default values for fields which don't have this set.
    // @todo don't compare all the fields from an entity (those without UI).
    $config = $this->configFactory->get('diff.settings');

    // Loop through entity fields and transform every FieldItemList object
    // into an array of strings according to field type specific settings.
    foreach ($entity as $field_items) {
      $field_type = $field_items->getIterator()->current()->getFieldDefinition()->getType();
      $context = array(
        'field_type' => $field_type,
        'settings' => array(
          'compare' => $config->get($field_type),
        ),
      );
      // For every field of the entity we call build method on the negotiated
      // service FieldDiffManager and this service will search for the service
      // that applies to this type of field and call the method on that service.
      $build = $this->fieldDiffManager->build($field_items, $context);

      if (!empty($build)) {
        $result[$field_items->getName()] = $build;
      }
    }

    return $result;
  }

  /**
   * This method should return an array of items ready to be compared.
   *
   * @todo Insert here some code as example.
   *
   * @param RevisionableInterface $left_entity
   *   The left entity
   * @param RevisionableInterface $right_entity
   *   The right entity
   *
   * @return array Items ready to be compared by the Diff component.
   */
  public function compareRevisions(RevisionableInterface $left_entity, RevisionableInterface $right_entity) {
    $result = array();
    // Wrapper object for writing and reading simple configuration from files.
    $config = $this->configFactory->get('diff.settings');

    $left_values = $this->parseEntity($left_entity);
    $right_values = $this->parseEntity($right_entity);

    foreach ($left_values as $field_name => $values) {
      // @todo Consider refactoring this to an object.
      $field_definition = $left_entity->getFieldDefinition($field_name);
      // Get the compare settings for this field type.
      $compare_settings = $config->get($field_definition->getType());
      $result[$field_name] = array(
        '#name' => ($compare_settings['show_header'] == 1) ? $field_definition->getLabel() : '',
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
      $compare_settings = $config->get($field_definition->getType());
      $result[$field_name] = array(
        '#name' => ($compare_settings['show_header'] == 1) ? $field_definition->getLabel() : '',
        '#settings' => $compare_settings,
      );
      $result[$field_name] += $this->combineFields(array(), $right_values[$field_name]);
    }

    // Field rows. Recurse through all child elements.
    // @todo Should this be injected ?
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

      if (!empty($field_settings['markdown'])) {
        $result[$key]['#states']['raw_plain']['#left'] = $this->apply_markdown($field_settings['markdown'], $result[$key]['#states']['raw']['#left']);
        $result[$key]['#states']['raw_plain']['#right'] = $this->apply_markdown($field_settings['markdown'], $result[$key]['#states']['raw']['#right']);
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
   * Render the table rows for theme('table').
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

    if (!isset($line_stats)) {
      $line_stats = array(
        'counter' => array('x' => 0, 'y' => 0),
        'offset' => array('x' => 0, 'y' => 0),
      );
    }

    // Header is the line counter.
    $this->diffFormatter->show_header = $show_header;
    // @todo Should Diff object be a service/should it be injected ?
    $diff = new Diff($a, $b);

    // @todo we need our custom settings for this service.
    return $this->diffFormatter->format($diff);
  }

  /**
   * Processes all the states by splitting strings into lines and counting
   * the resulted number of lines.
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
   * @return array|string
   *   Result after markdown was applied on $items.
   */
  function apply_markdown($markdown, $items) {
    if (!$markdown) {
      return $items;
    }

    if ($markdown == 'drupal_html_to_text') {
      return trim(drupal_html_to_text($items), "\n");
    }
    else if ($markdown == 'filter_xss') {
      return trim(Xss::filter($items), "\n");
    }
    else if ($markdown == 'filter_xss_all') {
      return trim(Xss::filter($items, array()), "\n");
    }
  }

} 