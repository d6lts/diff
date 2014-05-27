<?php

namespace Drupal\diff\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\Date;
use Drupal\Component\Utility\String;
use \Drupal\Core\Utility\LinkGenerator;

/**
 * Provides a form for revision overview page.
 */
class RevisionOverviewForm extends FormBase implements ContainerInjectionInterface {

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
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $link_generator;


  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Datetime\Date $date
   *   The date service.
   */
  public function __construct(EntityManagerInterface $entityManager, AccountInterface $currentUser,Date $date, LinkGenerator $link_generator) {
    $this->entityManager = $entityManager;
    $this->currentUser = $currentUser;
    $this->date = $date;
    $this->link_generator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('date'),
      $container->get('link_generator')
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
  public function buildForm(array $form, array &$form_state, $node = NULL) {
    $account = $this->currentUser;
    $node_storage = $this->entityManager->getStorage('node');
    $type = $node->getType();

    $build = array();
    $build['#title'] = $this->t('Revisions for %title', array('%title' => $node->label()));

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

    $vids = $node_storage->revisionIds($node);
    // @todo We should take care of pagination in the future.
    foreach (array_reverse($vids) as $vid) {
      if ($revision = $node_storage->loadRevision($vid)) {
        $row = array();

        $revision_log = '';
        if ($revision->log->value != '') {
          $revision_log = '<p class="revision-log">' . Xss::filter($revision->log->value) . '</p>';
        }
        $username = array(
          '#theme' => 'username',
          '#account' => $revision->uid->entity,
        );
        $revision_date = $this->date->format($revision->revision_timestamp->value, 'short');

        // Current revision.
        if ($vid == $node->getRevisionId()) {
          $row[] = array(
            'data' => $this->t('!date by !username', array(
                '!date' => $this->link_generator->generate($revision_date, 'node.view',array('node' => $node->id())),
                '!username' => drupal_render($username),
              )) . $revision_log,
            'class' => array('revision-current'),
          );
          // @todo If #value key is not provided a notice of undefined key appears
          // @todo check if there are better ways to do this (without #value).
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_left',
              '#return_value' => $vid,
              '#default_value' => FALSE,
              '#value' => FALSE,
            ),
          );
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_right',
              '#default_value' => $vid,
              '#return_value' => $vid,
              '#value' => $vid,
            ),
          );
          $row[] = array(
            'data' => String::placeholder($this->t('current revision')),
            'class' => array('revision-current')
          );
        }
        else {
          $row[] = $this->t('!date by !username', array(
              '!date' => $this->link_generator->generate($revision_date, 'node.revision_show', array(
                'node' => $node->id(),
                'node_revision' => $vid
              )),
              '!username' => drupal_render($username)
            )) . $revision_log;

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
              '#default_value' => TRUE,
              '#value' => ($vid == $node->getRevisionId() - 1) ? TRUE : FALSE,
            ),
          );
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_right',
              '#return_value' => $vid,
              '#default_value' => FALSE,
              '#value' => FALSE,
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
    );
    $build['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Compare'),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $vid_left = $form_state['input']['radios_left'];
    $vid_right = $form_state['input']['radios_right'];
    if ($vid_left == $vid_right || !$vid_left || !$vid_right) {
      $this->setFormError('node_revisions_table', $form_state, $message = 'Select different revisions to compare.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {

  }
}
