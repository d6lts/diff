<?php

/**
 * @file
 * Contains \Drupal\diff\FieldDiffBuilderBase
 */

namespace Drupal\diff;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class FieldDiffBuilderBase extends PluginBase implements FieldDiffBuilderInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a FieldDiffBuilderBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config) {
    $this->configFactory = $config;
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
//    $form['field_type'] = array(
//      '#type' => 'hidden',
//      '#value' => $field_type,
//    );
    $form['show_header'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show field title'),
      '#weight' => -5,
//      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'show_header'),
    );
    $form['markdown'] = array(
      '#type' => 'select',
      '#title' => $this->t('Markdown callback'),
//      '#default_value' => $config->get('field_types.' . $field_type . '.' . 'markdown'),
      '#options' => array(
        'drupal_html_to_text' => $this->t('Drupal HTML to Text'),
        'filter_xss' => $this->t('Filter XSS (some tags)'),
        'filter_xss_all' => $this->t('Filter XSS (all tags)'),
      ),
      '#description' => $this->t('These provide ways to clean markup tags to make comparisons easier to read.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // By default an empty validation function is provided.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $keys = array('show_header', 'markdown');
    foreach ($keys as $key) {
      $this->configuration[$key] = $form_state['values'][$key];
    }
    // @todo Consider refactoring field diff builders to ConfigEntity so this is not needed.
    $this->setConfiguration($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configFactory->get('diff.settings')->get();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $config = $this->configFactory->get('diff.settings');
    foreach ($configuration as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}