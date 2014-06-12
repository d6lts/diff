<?php

/**
 * @file
 * Contains \Drupal\diff\EntityComparisonBase.
 */

namespace Drupal\diff;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\diff\Diff\FieldDiffManager;
use Drupal\Core\Render\Element;
use Drupal\Component\Diff\DiffFormatter;
use Drupal\Component\Diff\Diff;

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
   * Constructs an EntityComparisonBase object.
   *
   * @param FieldDiffManager $fieldDiffManager
   *   Field diff manager negotiated service.
   */
  public function __construct(FieldDiffManager $fieldDiffManager) {
    $this->fieldDiffManager = $fieldDiffManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('diff.manager'));
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
    $settings = array(
      'summary' => TRUE,
      'format' => TRUE,
      'value' => TRUE,
    );

    foreach ($entity as $field_items) {
      $context = array(
        'field_type' => $field_items->getIterator()->current()->getFieldDefinition()->getType(),
        'settings' => $settings,
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

      // @todo These should be further processed to get to form from doc-block comment of this function.

      // Parse all the fields from the left entity and build an array with field
      // label, left and right values, field settings and weight.
      foreach ($left_values as $field_name => $values) {
        // @todo Consider refactoring this to an object.
        $result[$field_name] = array(
          '#name' => $left_entity->getFieldDefinition($field_name)->label(),
          '#left' => array(),
          '#right' => array(),
          '#settings' => array(),
        );

        if (isset($right_values[$field_name])) {
          $max = max(array(count($left_values[$field_name]), count($right_values[$field_name])));
          for ($delta = 0; $delta < $max; $delta++) {
            if (isset($left_values[$field_name][$delta])) {
              $value = $left_values[$field_name][$delta];
              $result[$field_name]['#left'][] = is_array($value) ? implode("\n", $value) : $value;
            }
            if (isset($right_values[$field_name][$delta])) {
              $value = $right_values[$field_name][$delta];
              $result[$field_name]['#right'][] = is_array($value) ? implode("\n", $value) : $value;
            }
          }

          $result[$field_name]['#left'] = implode("\n", $result[$field_name]['#left']);
          $result[$field_name]['#right'] = implode("\n", $result[$field_name]['#right']);

        }
      }

      // We start off assuming all form elements are in the correct order.
//      $result['#sorted'] = TRUE;

      // Field rows. Recurse through all child elements.
      $count = 0;
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
  public function getRows($a, $b, $show_header = FALSE, &$line_stats = NULL) {
    $a = is_array($a) ? $a : explode("\n", $a);
    $b = is_array($b) ? $b : explode("\n", $b);

    if (!isset($line_stats)) {
      $line_stats = array(
        'counter' => array('x' => 0, 'y' => 0),
        'offset' => array('x' => 0, 'y' => 0),
      );
    }

    $formatter = new DiffFormatter();
    // Header is the line counter.
    $formatter->show_header = $show_header;
    $formatter->line_stats = &$line_stats;

    $diff = new Diff($a, $b);

    // @todo This doesn't seem to work.
    return $formatter->format($diff);
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