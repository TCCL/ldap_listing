<?php

/**
 * TweakForm.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_listing\DirectoryQuery;

class TweakForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form,FormStateInterface $form_state) {
    $form = parent::form($form,$form_state);

    $form['tweak'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Directory Listing Tweak'),
      '#open' => true,
    ];

    $form['tweak']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 512,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t(
        'A brief description of the purpose of this tweak.'
      ),
      '#required' => true,
    ];

    $form['tweak']['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ldap_listing\Entity\Tweak::load',
        'source' => ['tweak','label'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $sectionOptions = $this->makeDirectorySectionOptions();

    if (empty($sectionOptions)) {
      $form['tweak']['section_id'] = [
        '#type' => 'select',
        '#options' => [],
        '#default_value' => '',
        '#empty_value' => '',
        '#empty_option' => '- No Options -',
        '#sort_options' => true,
        '#required' => true,
      ];
    }
    else {
      $form['tweak']['section_id'] = [
        '#type' => 'select',
        '#options' => $sectionOptions,
        '#sort_options' => true,
        '#empty_value' => '',
        '#empty_option' => '- None -',
        '#default_value' => $this->entity->sectionId() ?? '',
        '#required' => true,
      ];
    }

    $form['tweak']['section_id'] += [
      '#title' => $this->t('Directory Section'),
      '#description' => $this->t(
        'The Directory Section under which to apply this tweak. The tweak will only '
        .'be applied to the indicated user under the indicated directory section.'
      ),
    ];

    $form['tweak']['user_dn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User DN'),
      '#description' => $this->t(
        'The Distinguished Name of the LDAP user that is tweaked by '
        . 'this configuration.'
      ),
      '#default_value' => $this->entity->userDn(),
      '#required' => true,
      '#max_length' => 1024,
    ];

    $form['tweak']['name_override'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Name'),
      '#description' => $this->t(
        'An alternate name to use instead of the attribute value from LDAP.'
      ),
      '#default_value' => $this->entity->nameOverride(),
      '#required' => false,
      '#max_length' => 1024,
    ];

    $form['tweak']['phone_override'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Phone Number'),
      '#description' => $this->t(
        'An alternate phone number to use instead of the attribute value from LDAP.'
      ),
      '#default_value' => $this->entity->phoneOverride(),
      '#required' => false,
      '#max_length' => 1024,
    ];

    $form['tweak']['email_override'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Email Address'),
      '#description' => $this->t(
        'An alternate email address to use instead of the attribute value from LDAP.'
      ),
      '#default_value' => $this->entity->emailOverride(),
      '#required' => false,
      '#max_length' => 1024,
    ];

    $form['tweak']['job_title_override'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Job Title'),
      '#description' => $this->t(
        'An alternate job title to use instead of the attribute value from LDAP.'
      ),
      '#default_value' => $this->entity->jobTitleOverride(),
      '#required' => false,
      '#max_length' => 1024,
    ];

    $form['tweak']['position_before_user_dn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Position Before User DN'),
      '#description' => $this->t(
        'The Distinguished Name of the user before which to position the tweaked user.'
      ),
      '#default_value' => $this->entity->positionBeforeUserDN(),
      '#required' => false,
      '#max_length' => 1024,
    ];

    $form['tweak']['position_after_user_dn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Position After User DN'),
      '#description' => $this->t(
        'The Distinguished Name of the user after which to position the tweaked user.'
      ),
      '#default_value' => $this->entity->positionAfterUserDN(),
      '#required' => false,
      '#max_length' => 1024,
    ];

    $form['tweak']['absolute_position'] = [
      '#type' => 'number',
      '#title' => $this->t('Absolute Position'),
      '#description' => $this->t(
        'The index of the absolute position of the tweaked user. Note: this overrides '
        .'Position Before User DN and Position After User DN.'
      ),
      '#default_value' => $this->entity->absolutePosition(),
      '#required' => false,
      '#min' => 0,
    ];

    $form['tweak']['exclude'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude From Listing'),
      '#description' => $this->t(
        'Excludes the tweaked user from a direction section listing.'
      ),
      '#default_value' => $this->entity->isExcluded(),
      '#required' => false,
      '#return_value' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form,FormStateInterface $form_state) {
    $status = $this->entity->save();

    if ($status == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Created tweak %label',[
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('Updated section %label',[
        '%label' => $this->entity->label(),
      ]));
    }

    Cache::invalidateTags([DirectoryQuery::CACHE_TAG]);

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  private function makeDirectorySectionOptions() : array {
    $storage = $this->entityTypeManager->getStorage('ldap_listing_directory_section');

    $options = [];

    $sections = $storage->getQuery()->execute();
    if (empty($sections)) {
      return [];
    }

    foreach ($sections as $sectionId) {
      $section = $storage->load($sectionId);
      if (!isset($section)) {
        continue;
      }

      $options[$sectionId] = $section->label();
    }

    return $options;
  }
}
