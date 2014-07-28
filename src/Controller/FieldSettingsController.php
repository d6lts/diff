<?php

/**
 * @file
 * Contains \Drupal\diff\Controller\FieldSettingsController.
 */

namespace Drupal\diff\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\diff\Diff\FieldDiffManager;

class FieldSettingsController implements ContainerInjectionInterface {

  /**
   * Field diff manager negotiated service.
   *
   * @var \Drupal\diff\Diff\FieldDiffManager
   */
  protected $fieldDiffManager;


  /**
   * Constructs a new FieldSettingsController.
   *
   * @param FieldDiffManager $field_diff_manager
   *   Field diff manager negotiated service.
   */
  public function __construct(FieldDiffManager $field_diff_manager) {
    $this->fieldDiffManager = $field_diff_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.manager')
    );
  }

  /**
   * Returns a diff settings form for the field type received as argument.
   *
   * @param $field_type
   *   Field type for which to return a settings form.
   *
   * @return array
   *   Settings form for field type in the argument list.
   */
  public function settingsForm($field_type) {
    return $this->fieldDiffManager->getSettingsForm($field_type);
  }

}
