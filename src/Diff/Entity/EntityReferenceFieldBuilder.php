<?php

/**
 * @file
 * Contains \Drupal\diff\EntityReferenceFieldBuilder.
 */

namespace Drupal\diff\Diff\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\Diff\FieldDiffBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;


class EntityReferenceFieldBuilder implements FieldDiffBuilderInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;


  /**
   * Constructs a ImageFieldBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param FormBuilderInterface $form_builder
   *   Form builder service.
   */
  public function __construct(EntityManagerInterface $entityManager, FormBuilderInterface $form_builder) {
    $this->entityManager = $entityManager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(FieldDefinitionInterface $field_definition) {
    // This class can handle diffs for image field types.
    if ($field_definition->getType() == 'entity_reference') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items, array $context) {
    $result = array();
    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();
        // Compare entity ids.
        if (isset($values['target_id'])) {
          $result[$field_key][] = $this->t('Entity ID: ') . $values['target_id'];
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm($field_type) {
    return $this->formBuilder->getForm('Drupal\diff\Form\DiffFieldBaseSettingsForm', $field_type);
  }

}
