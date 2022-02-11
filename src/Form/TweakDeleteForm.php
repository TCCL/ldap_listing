<?php

/**
 * TweakDeleteForm.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ldap_listing\DirectoryQuery;

class TweakDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete tweak %name?',[
      '%name' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.ldap_listing_tweak.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form,FormStateInterface $form_state) {
    $this->entity->delete();

    \Drupal::logger('ldap_listing')->notice('@type: deleted %title.',
      [
        '@type' => $this->entity->bundle(),
        '%title' => $this->entity->label(),
      ]);

    $this->messenger()->addMessage(
      $this->t('Directory Listing Tweak: deleted @label.',
        [
          '@label' => $this->entity->label(),
        ]
      )
    );

    Cache::invalidateTags([DirectoryQuery::CACHE_TAG]);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
