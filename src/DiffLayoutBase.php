<?php

namespace Drupal\diff;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class DiffLayoutBase extends PluginBase implements DiffLayoutInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity parser.
   *
   * @var \Drupal\diff\DiffEntityParser
   */
  protected $entityParser;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\diff\DiffEntityParser $entity_parser
   *   The entity manager.
   * @param \Drupal\Core\DateTime\DateFormatter $date
   *   The date service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, DiffEntityParser $entity_parser, DateFormatter $date) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityParser = $entity_parser;
    $this->date = $date;
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
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('diff.entity_parser'),
      $container->get('date.formatter')
    );
  }

  /**
   * Build the revision link for a revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $revision
   *   A revision where to add a link.
   *
   * @return \Drupal\Core\GeneratedLink
   *   Header link for a revision in the table.
   */
  protected function buildRevisionLink(EntityInterface $revision) {
    $entity_type_id = $revision->getEntityTypeId();
    if ($revision instanceof EntityRevisionLogInterface || $revision instanceof NodeInterface) {
      $revision_log = '';

      if ($revision instanceof EntityRevisionLogInterface) {
        $revision_log = Xss::filter($revision->getRevisionLogMessage());
      }
      elseif ($revision instanceof NodeInterface) {
        $revision_log = $revision->revision_log->value;
      }
      $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
      $route_name = $entity_type_id != 'node' ? "entity.$entity_type_id.revisions_diff" : 'entity.node.revision';
      $revision_link = $this->t($revision_log . '@date', [
        '@date' => \Drupal::l($revision_date, Url::fromRoute($route_name, [
          $entity_type_id => $revision->id(),
          $entity_type_id . '_revision' => $revision->getRevisionId(),
        ])),
      ]);
    }
    else {
      $revision_link = \Drupal::l($revision->label(), $revision->toUrl('revision'));
    }
    return $revision_link;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configFactory->getEditable('diff.layout_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $config = $this->configFactory->getEditable('diff.layout_plugins');
    $config->set($this->pluginId, $configuration);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }
}
