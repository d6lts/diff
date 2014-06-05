<?php

namespace Drupal\diff\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldItemList;
use Drupal\diff\TextFieldDiff;
use Drupal\diff\Diff\FieldDiffManager;


abstract class EntityComparisonBase extends ControllerBase implements  ContainerInjectionInterface{

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
   * @return array
   */
  private function parseEntity(RevisionableInterface $entity) {
    $result = array();

    // @TODO These values should be taken from the diff settings page.
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
   * @return array of items ready to be compared
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

      // @TODO These should be further processed to get to the form from the
      // function comment. For the moment send them as they are for testing purposes.
      return array(
        'left' => $left_values,
        'right' => $right_values,
      );
    }

  }


} 