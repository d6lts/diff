<?php

/**
 * @file
 * Contains \Drupal\diff\Diff\Entity\TermReferenceFieldBuilder.
 */

namespace Drupal\diff\Diff\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\Diff\FieldDiffBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;


class TermReferenceFieldBuilder implements FieldDiffBuilderInterface {
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
   * Constructs a TaxonomyReferenceDiffBuilder object.
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
    if ($field_definition->getType() == 'taxonomy_term_reference') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items, array $context) {
    $result = array();
    $compare = $context['settings']['compare'];

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      // Build the array for comparison only if the field is not empty.
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();
        if (isset($values['target_id'])) {
          // Show term name.
          if (isset($compare['show_name']) && $compare['show_name'] == 1) {
            $controller = $this->entityManager->getStorage('taxonomy_term');
            $taxonomy_term = $controller->load($values['target_id']);
            if ($taxonomy_term != NULL) {
              $result[$field_key][] = $this->t('Term name: ') . $taxonomy_term->getName();
            }
          }
          // Show term ids.
          if (isset($compare['show_id']) && $compare['show_id'] == 1) {
            $result[$field_key][] = $this->t('Term id: ') . $values['target_id'];
          }
        }

        $result[$field_key] = implode('; ', $result[$field_key]);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm($field_type) {
    return $this->formBuilder->getForm('Drupal\diff\Form\TermReferenceSettingsForm', $field_type);
  }

}
