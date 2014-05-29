<?php

namespace Drupal\diff\Controller;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldItemList;
use Drupal\diff\TextFieldDiff;

abstract class EntityComparisonBase extends ControllerBase {

  /**
   * This is a factory method used for creating an object which implements the
   * interface FieldDiffInterface.
   *
   * @param FieldItemList $field_items A field of the entity to be compared.
   * @return TextFieldDiff|null If there is a class which implements the
   *   interface FieldDiffInterface for this field provider the return an object
   *   of that class. Else return null.
   */
  private function fieldDiffFactory(FieldItemList $field_items) {
    $field_plugin_definition = $field_items->getIterator()->current()->getPluginDefinition();

    // @TODO Here we need to find a better solution for finding the right class
    // to be returned rather hard-coding the returned object (since I suppose
    // that in OOP we cannot rely on naming conventions to load the classes, I
    // added a method getFieldProvider to the interface which should return the
    // provider for that field e.g. text, image, entity_reference, core, etc.)
    // Maybe we can use that to find and return the object of the right class.
    if ($field_plugin_definition['provider'] == 'text') {
      return new TextFieldDiff($field_items);
    }

    return NULL;
  }

  private function parseEntity(RevisionableInterface $entity) {
    foreach ($entity as $field_items) {
      $field_diff = $this->fieldDiffFactory($field_items);

      // A class providing diff for this type of field has been found.
      if($field_diff != NULL) {
        return $field_diff->view($field_items, array());
      }
    }

    return NULL;
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
    // described above. Examine them and maybe just abandon them.
    if ($right_entity instanceof $entity_type_class) {
      $left_values = $this->parseEntity($left_entity);
      $right_values = $this->parseEntity($right_entity);

      return array($left_values, $right_values);
    }

  }


} 