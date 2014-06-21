<?php

/**
 * @file
 * Contains \Drupal\diff\Controller\FieldSettingsController.
 */

namespace Drupal\diff\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\diff\Diff\FieldDiffManager;

class FieldSettingsController implements ContainerInjectionInterface {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Field diff manager negotiated service.
   *
   * @var \Drupal\diff\Diff\FieldDiffManager
   */
  protected $fieldDiffManager;


  /**
   * Constructs a new FieldSettingsController
   *
   * @param FormBuilderInterface $form_builder
   *   Form builder service.
   * @param FieldDiffManager $field_diff_manager
   *   Field diff manager negotiated service.
   */
  public function __construct(FormBuilderInterface $form_builder, FieldDiffManager $field_diff_manager) {
    $this->formBuilder = $form_builder;
    $this->fieldDiffManager = $field_diff_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('diff.manager')
    );
  }

  /**
   * Returns a diff settings form for fields types.
   *
   * @param $field_type Field type for which to return a settings form.
   *
   * @return array Settings form for field type in the argument list.
   */
  public function settingsForm($field_type) {
    $form_name = $this->fieldDiffManager->getSettingsForm($field_type);

    return $this->formBuilder->getForm($form_name, $field_type);
  }

}
