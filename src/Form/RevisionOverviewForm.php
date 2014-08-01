<?php

/**
 * @file
 * Contains the revision overview form.
 */

namespace Drupal\diff\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\Date;
use Drupal\Component\Utility\String;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


/**
 * Provides a form for revision overview page.
 */
class RevisionOverviewForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $date;

  /**
   * Wrapper object for writing/reading simple configuration from diff.settings.yml
   */
  protected $config;


  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Datetime\Date $date
   *   The date service.
   * @param ConfigFactoryInterface $config_factory
   *   Config Factory service
   */
  public function __construct(EntityManagerInterface $entityManager, AccountInterface $currentUser, Date $date, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entityManager;
    $this->currentUser = $currentUser;
    $this->date = $date;
    $this->config = $config_factory->get('diff.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('date'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'revision_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $account = $this->currentUser;
    $node_storage = $this->entityManager->getStorage('node');
    $type = $node->getType();

    $build = array();
    $build['#title'] = $this->t('Revisions for %title', array('%title' => $node->label()));
    $build['nid'] = array(
      '#type' => 'hidden',
      '#value' => $node->nid->value,
    );

    $header = array($this->t('Revision'), '', '', $this->t('Operations'));

    $revert_permission = ((
        $account->hasPermission("revert $type revisions") ||
        $account->hasPermission('revert all revisions') ||
        $account->hasPermission('administer nodes')) &&
      $node->access('update')
    );
    $delete_permission = ((
        $account->hasPermission("delete $type revisions") ||
        $account->hasPermission('delete all revisions') ||
        $account->hasPermission('administer nodes')) &&
      $node->access('delete')
    );

    $rows = array();

    $vids = array_reverse($node_storage->revisionIds($node));
    // @todo We should take care of pagination in the future.
    foreach ($vids as $vid) {
      if ($revision = $node_storage->loadRevision($vid)) {
        $row = array();

        $revision_log = '';

        if ($revision->revision_log->value != '') {
          $revision_log = '<p class="revision-log">' . Xss::filter($revision->revision_log->value) . '</p>';
        }
        $username = array(
          '#theme' => 'username',
          '#account' => $revision->uid->entity,
        );
        $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');

        // Current revision.
        if ($revision->isDefaultRevision()) {
          // @todo When solved in core check to see if there's a better solution
          //   to avoid double escaping.
          $row[] = array(
            'data' => SafeMarkup::set($this->t('!date by !username', array(
                '!date' => $this->l($revision_date, 'node.view', array('node' => $node->id())),
                '!username' => drupal_render($username),
              )) . $revision_log),
            'class' => array('revision-current'),
          );
          // @todo If #value key is not provided a notice of undefined key appears.
          //   I've created issue https://drupal.org/node/2275837 for this bug.
          //   When resolved refactor this.
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_left',
              '#return_value' => $vid,
              '#default_value' => FALSE,
            ),
          );
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_right',
              '#default_value' => $vid,
              '#return_value' => $vid,
            ),
          );
          $row[] = array(
            'data' => String::placeholder($this->t('current revision')),
            'class' => array('revision-current')
          );
        }
        else {
          $row[] = SafeMarkup::set($this->t('!date by !username', array(
              '!date' => $this->l($revision_date, 'node.revision_show', array(
                  'node' => $node->id(),
                  'node_revision' => $vid
                )),
              '!username' => drupal_render($username)
            )) . $revision_log);

          if ($revert_permission) {
            $links['revert'] = array(
              'title' => $this->t('Revert'),
              'route_name' => 'node.revision_revert_confirm',
              'route_parameters' => array(
                'node' => $node->id(),
                'node_revision' => $vid
              ),
            );
          }

          if ($delete_permission) {
            $links['delete'] = array(
              'title' => $this->t('Delete'),
              'route_name' => 'node.revision_delete_confirm',
              'route_parameters' => array(
                'node' => $node->id(),
                'node_revision' => $vid
              ),
            );
          }

          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_left',
              '#return_value' => $vid,
              '#default_value' => isset ($vids[1]) ? $vids[1] : FALSE,
            ),
          );
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_right',
              '#return_value' => $vid,
              '#default_value' => FALSE,
            ),
          );
          $row[] = array(
            'data' => array(
              '#type' => 'operations',
              '#links' => $links,
            ),
          );
        }

        $rows[] = $row;
      }
    }

    $build['node_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => array('class' => array('diff-revisions')),
      '#attached' => array(
        'js' => array(
          drupal_get_path('module', 'diff') . '/js/diff.js',
          array(
            'data' => array('diffRevisionRadios' => $this->config->get('general_settings.radio_behavior')),
            'type' => 'setting',
          ),
        ),
        'css' => array(
          drupal_get_path('module', 'diff') . '/css/diff.default.css',
        ),
      ),
    );

    $build['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Compare'),
      '#attributes' => array(
        'class' => array(
          'diff-button',
        ),
      ),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $vid_left = $form_state['input']['radios_left'];
    $vid_right = $form_state['input']['radios_right'];
    if ($vid_left == $vid_right || !$vid_left || !$vid_right) {
      // @todo See why radio-boxes reset if there are errors.
      $form_state->setError($form['node_revision_table'], $this->t('Select different revisions to compare.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vid_left = $form_state['input']['radios_left'];
    $vid_right = $form_state['input']['radios_right'];
    $nid = $form_state['input']['nid'];

    // Always place the older revision on the left side of the comparison
    // and the newer revision on the right side (however revisions can be
    // compared both ways if we manually change the order of the parameters).
    if ($vid_left > $vid_right) {
      $aux = $vid_left;
      $vid_left = $vid_right;
      $vid_right = $aux;
    }
    // Builds the redirect Url.
    $redirect_url = new Url(
      'diff.revisions_diff',
      array(
        'node' => $nid,
        'left_vid' => $vid_left,
        'right_vid' => $vid_right,
      )
    );
    $form_state->setRedirect($redirect_url);
  }

}
