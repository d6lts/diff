<?php

/**
 * @file
 * Contains \Drupal\config\Form\NodeEntitySettingsForm.
 */

namespace Drupal\diff\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;


/**
 * Defines the settings form for a node entity.
 */
class NodeEntitySettingsForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a NodeEntitySettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_entity_node';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = array();
    $config = $this->config('diff.settings');

    $form['info'] = array(
      '#markup' => 'Select which of the below base fields of node entities should be compared.',
    );

    $node_base_fields = $this->entityManager->getBaseFieldDefinitions('node');
    foreach ($node_base_fields as $field_key => $field) {
      $form[$field_key] = array(
        '#title' => $this->t('@field_label (%field_type)', array(
          '@field_label' => $field->getLabel(),
          '%field_type' => $field->getType(),
          )
        ),
        '#type' => 'checkbox',
        '#default_value' => $config->get('entity.node.' . $field_key),
      );
    }

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('diff.settings');

    $node_base_fields = $this->entityManager->getBaseFieldDefinitions('node');
    foreach ($node_base_fields as $field_key => $field) {
      $config->set('entity.node' . '.' . $field_key, $form_state['values'][$field_key]);
      $config->save();
    }

    return parent::submitForm($form, $form_state);
  }

}
