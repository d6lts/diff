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
      $build = $this->fieldDiffManager->build($field_items, $context);

      if (!empty($build)) {
        $result[] = $build;
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

      // @todo These should be further processed to get to form from docblock comment of this function.
      // For the moment send them as they are for testing purposes.
      return array(
        'left' => $left_values,
        'right' => $right_values,
      );
    }

  }


} 