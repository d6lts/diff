<?php

namespace Drupal\diff\Plugin\diff\Layout;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\diff\DiffEntityComparison;
use Drupal\diff\DiffEntityParser;
use Drupal\diff\DiffLayoutBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @DiffLayoutBuilder(
 *   id = "split_fields",
 *   label = @Translation("Split fields"),
 * )
 */
class SplitFieldsDiffLayout extends DiffLayoutBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The diff entity comparison service.
   */
  protected $entityComparison;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\diff\DiffEntityComparison $entity_comparison
   *   The diff entity comparison service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, DiffEntityParser $entity_parser, DateFormatter $date, RendererInterface $renderer, DiffEntityComparison $entity_comparison, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config, $entity_type_manager, $entity_parser, $date);
    $this->renderer = $renderer;
    $this->entityComparison = $entity_comparison;
    $this->requestStack = $request_stack;
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
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('diff.entity_comparison'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $left_revision, EntityInterface $right_revision, EntityInterface $entity) {
    $active_filter = $this->requestStack->getCurrentRequest()->query->get('filter') ?: 'raw';
    $build['filter'] = [
      '#type' => 'item',
      '#title' => $this->t('Filter'),
    ];
    $build['filter']['options'] = $this->buildFilterNavigation($entity, $left_revision, $right_revision, 'split_fields', $active_filter);

    // Build the diff comparison table.
    $diff_header = $this->buildTableHeader($left_revision, $right_revision);
    // Perform comparison only if both entity revisions loaded successfully.
    $fields = $this->entityComparison->compareRevisions($left_revision, $right_revision);
    // Build the diff rows for each field and append the field rows
    // to the table rows.
    $diff_rows = [];
    foreach ($fields as $field) {
      $field_label_row = '';
      if (!empty($field['#name'])) {
        $field_label_row = [
          'data' => $field['#name'],
          'colspan' => 4,
          'class' => ['field-name'],
        ];
      }

      if ($active_filter == 'strip_tags') {
        $field_settings = $field['#settings'];
        if (!empty($field_settings['settings']['markdown'])) {
          $field['#data']['#left'] = $this->applyMarkdown($field_settings['settings']['markdown'], $field['#data']['#left']);
          $field['#data']['#right'] = $this->applyMarkdown($field_settings['settings']['markdown'], $field['#data']['#right']);
        }
        // In case the settings are not loaded correctly use drupal_html_to_text
        // to avoid any possible notices when a user clicks on markdown.
        else {
          $field['#data']['#left'] = $this->applyMarkdown('drupal_html_to_text', $field['#data']['#left']);
          $field['#data']['#right'] = $this->applyMarkdown('drupal_html_to_text', $field['#data']['#right']);
        }
      }
      // Process the array (split the strings into single line strings)
      // and get line counts per field.
      $this->entityComparison->processStateLine($field);

      $field_diff_rows = $this->entityComparison->getRows(
        $field['#data']['#left'],
        $field['#data']['#right']
      );

      // Add the field label to the table only if there are changes to that field.
      if (!empty($field_diff_rows) && !empty($field_label_row)) {
        $diff_rows[] = [$field_label_row];
      }

      // Add field diff rows to the table rows.
      $diff_rows = array_merge($diff_rows, $field_diff_rows);
    }

    $build['diff'] = [
      '#type' => 'table',
      '#header' => $diff_header,
      '#rows' => $diff_rows,
      '#empty' => $this->t('No visible changes'),
      '#attributes' => [
        'class' => ['diff'],
      ],
    ];

    $build['#attached']['library'][] = 'diff/diff.double_column';
    $build['#attached']['library'][] = 'diff/diff.colors';
    return $build;
  }

  /**
   * Build the header for the diff table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $left_revision
   *   Revision from the left hand side.
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   Revision from the right hand side.
   *
   * @return array
   *   Header for Diff table.
   */
  protected function buildTableHeader(EntityInterface $left_revision, EntityInterface $right_revision) {
    $header = [];
    $header[] = [
      'data' => ['#markup' => $this->buildRevisionLink($left_revision)],
      'colspan' => 2,
    ];
    $header[] = [
      'data' => ['#markup' => $this->buildRevisionLink($right_revision)],
      'colspan' => 2,
    ];

    return $header;
  }

}