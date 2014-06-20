<?php

/**
 * @file
 * Contains \Drupal\diff\EntityComparisonBase.
 */

namespace Drupal\diff\Diff\Entity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\diff\Diff\FieldDiffManager;
use Drupal\Core\Render\Element;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Component\Diff\Diff;
use Drupal\Core\Datetime\Date;


/**
 * Class EntityComparisonBase
 *   Builds an array of data to be passed through the Diff component and
 * displayed on the UI representing the differences between entity fields.
 */
class EntityComparisonBase extends ControllerBase implements  ContainerInjectionInterface {

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
   * Constructs an EntityComparisonBase object.
   *
   * @param FieldDiffManager $field_diff_manager
   *   Field diff manager negotiated service.
   * @param DiffFormatter $diff_formatter
   *   Diff formatter service.
   * @param Date $date
   *   Date service.
   */
  public function __construct(FieldDiffManager $field_diff_manager, DiffFormatter $diff_formatter, Date $date) {
    $this->fieldDiffManager = $field_diff_manager;
    $this->diffFormatter = $diff_formatter;
    $this->date = $date;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.manager'),
      $container->get('diff.formatter'),
      $container->get('date')
    );
  }

  /**
   * @param RevisionableInterface $entity
   * @todo Document this properly.
   * @return array
   */
  private function parseEntity(RevisionableInterface $entity) {
    $result = array();

    // @todo These values should be taken from the diff module settings page.
    // They are hard-coded here for testing purposes only.
    $compare = array('format', 'summary', 'value');

    foreach ($entity as $field_items) {
      $context = array(
        'field_type' => $field_items->getIterator()->current()->getFieldDefinition()->getType(),
        'settings' => array(
          'compare' => $compare,
        ),
      );
      // For every field of the entity we call build method on the negotiated
      // service FieldDiffManager and this service will search for the services
      // that manage this type of field and call the right service.
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
   * E.g.
   * array(
   *   [field1_machine_name] => array(
   *     '#name' => ['field_name],
   *     '#old' => [old_value]
   *     '#new' => [new_value]
   *     '#settings' => array(...),
   *     '#weight' => ...,
   *   ),
   *   [field2_machine_name] => array(
   *     '#name' => ['field_name],
   *     '#old' => [old_value]
   *     '#new' => [new_value]
   *     '#settings' => array(...),
   *     '#weight' => ...,
   *   ),
   *   ...
   * );
   *
   * @param RevisionableInterface $left_entity The left entity
   * @param RevisionableInterface $right_entity The right entity
   *
   * @return array of items ready to be compared by the Diff component.
   */
  public function compareRevisions(RevisionableInterface $left_entity, RevisionableInterface $right_entity) {
    $result = array();
    $entity_type_class = $left_entity->getEntityType()->getClass();

    // Compare entities only if the entity type class of both entities is the same.
    // For now suppose that the entities provided here are revisions of the same
    // entity.
    // Maybe later provide support for comparing two entities of different types
    // or two entities of the same type but different bundles (entities can share
    // field across bundle but can't share fields across entity types; e.g. a user
    // cannot have a field from a node or vice-versa).
    // But again I don't think there are any real use cases for the two cases
    // described above.
    if ($right_entity instanceof $entity_type_class) {
      $left_values = $this->parseEntity($left_entity);
      $right_values = $this->parseEntity($right_entity);

      foreach ($left_values as $field_name => $values) {
        // @todo Consider refactoring this to an object.
        $result[$field_name] = array(
          '#name' => $left_entity->getFieldDefinition($field_name)->label(),
          '#settings' => array(),
        );

        // The field exists on the right entity also.
        if (isset($right_values[$field_name])) {
          $result[$field_name] += $this->combineFields($left_values[$field_name], $right_values[$field_name]);
          // Unset the field from the right entity so that we know if the right
          // entity has extra fields compared to left entity.
          unset($right_values[$field_name]);
        }
        // This field exists only on the left entity.
        else {
          $result[$field_name] += $this->combineFields($left_values[$field_name], array());
        }
      }

      // Fields which exist only on the right entity.
      foreach ($right_values as $field_name => $values) {
        $result[$field_name] = array(
          '#name' => $left_entity->getFieldDefinition($field_name)->label(),
          '#settings' => array(),
        );
        $result[$field_name] += $this->combineFields(array(), $right_values[$field_name]);
      }

      // We start off assuming all form elements are in the correct order.
//      $result['#sorted'] = TRUE;

      // Field rows. Recurse through all child elements.
      foreach (Element::children($result) as $key) {
        $result[$key]['#states'] = array();

        // Ensure that the element follows the new #states format.
        if (isset($result[$key]['#left'])) {
          $result[$key]['#states']['raw']['#left'] = $result[$key]['#left'];
          unset($result[$key]['#left']);
        }
        if (isset($result[$key]['#right'])) {
          $result[$key]['#states']['raw']['#right'] = $result[$key]['#right'];
          unset($result[$key]['#right']);
        }
      }
    }

    // Process the array and get line counts per field.
    array_walk($result, array($this, 'processStateLine'));
    return $result;
  }

  /**
   * Combine two fields into an array with keys '#left' and '#right'.
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
   * @param $diff
   * @param $key
   */
  function processStateLine(&$diff, $key) {
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

} 