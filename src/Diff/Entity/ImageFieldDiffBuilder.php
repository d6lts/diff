<?php

/**
 * @file
 * Contains \Drupal\diff\ImageFieldDiffBuilder.
 */

namespace Drupal\diff\Diff\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\Diff\FieldDiffBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormBuilderInterface;


class ImageFieldDiffBuilder implements FieldDiffBuilderInterface {
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
  public function applies(array $context) {
    // This class can handle diffs for image field types.
    if ($context['field_type'] == 'image') {
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

        // Compare file names.
        if (isset($values['target_id'])) {
          $image = $fileManager->load($values['target_id']);
          $result[$field_key][] = $this->t('Image: !image', array('!image' => $image->getFilename()));
        }

        // Compare Alt fields.
        if (isset($compare['compare_alt_field']) && $compare['compare_alt_field'] == 1) {
          if (isset($values['alt'])) {
            $result[$field_key][] = $this->t('Alt: !alt', array('!alt' => $values['alt']));
          }
        }

        // Compare Title fields.
        if (isset($compare['compare_title_field']) && $compare['compare_title_field'] == 1) {
          if (isset($values['title'])) {
            $result[$field_key][] = $this->t('Title: !title', array('!title' => $values['title']));
          }
        }

        // Compare file id.
        if (isset($compare['show_id']) && $compare['show_id'] == 1) {
          if (isset($values['target_id'])) {
            $result[$field_key][] = $this->t('File ID: !fid', array('!fid' => $values['target_id']));
          }
        }
        // @todo Investigate why this is marked as a change rather than an addition.
        $separator = $compare['property_separator'] == 'nl' ? "\n" : $compare['property_separator'];
        $result[$field_key] = implode($separator, $result[$field_key]);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm($field_type) {
    return $this->formBuilder->getForm('Drupal\diff\Form\ImageFieldSettingsForm', $field_type);
  }

}
