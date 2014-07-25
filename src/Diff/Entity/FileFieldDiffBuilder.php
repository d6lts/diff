<?php

/**
 * @file
 * Contains \Drupal\diff\FileFieldDiffBuilder.
 */

namespace Drupal\diff\Diff\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\Diff\FieldDiffBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;


class FileFieldDiffBuilder implements FieldDiffBuilderInterface {
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
   * Constructs a ImageFieldDiffBuilder object.
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
    // Check if this class can handle diff for image fields.
    if ($field_definition->getType() == 'file') {
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
    $fileManager = $this->entityManager->getStorage('file');
    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();

        // Add file name to the comparison.
        if (isset($values['target_id'])) {
          $file = $fileManager->load($values['target_id']);
          $result[$field_key][] = $this->t('File: !image', array('!image' => $file->getFilename()));;
        }

        // Add file id to the comparison.
        if (isset($compare['show_id']) && $compare['show_id'] == 1) {
          if (isset($values['target_id'])) {
            $result[$field_key][] = $this->t('File ID: !fid', array('!fid' => $values['target_id']));;
          }
        }

        // Compare file description fields.
        if (isset($compare['compare_description_field']) && $compare['compare_description_field'] == 1) {
          if (isset($values['description'])) {
            $result[$field_key][] = $this->t('Description: !description', array('!description' => $values['description']));;
          }
        }

        // Compare Enable Display property.
        if (isset($compare['compare_display_field']) && $compare['compare_display_field'] == 1) {
          if (isset($values['display'])) {
            if ($values['display'] == 1) {
              $result[$field_key][] = $this->t('Displayed');
            }
            else {
              $result[$field_key][] = $this->t('Hidden');
            }
          }
        }

        // Add the requested separator between resulted strings.
        if (isset($compare['property_separator'])) {
          $separator = $compare['property_separator'] == 'nl' ? "\n" : $compare['property_separator'];
          $result[$field_key] = implode($separator, $result[$field_key]);
        }
      }

    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm($field_type) {
    return $this->formBuilder->getForm('Drupal\diff\Form\FileFieldSettingsForm', $field_type);
  }

}