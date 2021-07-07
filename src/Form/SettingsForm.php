<?php

/**
 * SettingsForm.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_listing\Controller\DirectoryPage;

class SettingsForm extends ConfigFormBase {
  const CONFIG_OBJECT = 'ldap_listing.settings';
  const UNSET = 'ldap_listing_unset_5a3ab8bf41b4910d7861ee419d24ed32b2d4e6a7';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ldap_listing_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      self::CONFIG_OBJECT,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form,FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_OBJECT);
    $container = \Drupal::getContainer();
    $entityTypeManager = $container->get('entity_type.manager');
    $storage = $entityTypeManager->getStorage('ldap_server');

    $query = $storage->getQuery();
    $servers = [];
    foreach ($storage->loadMultiple($query->execute()) as $sid => $ldapServer) {
      if ($ldapServer->get('status')) {
        $servers[$sid] = $ldapServer->get('label');
      }
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => 'Page Title',
      '#default_value' => $config->get('title'),
      '#description' => (
        'The title to display on the LDAP directory listing pages.'
      ),
    ];

    $form['ldap_server'] = [
      '#type' => 'select',
      '#title' => 'LDAP Server',
      '#options' => [
        self::UNSET => '<Unset>',
      ] + $servers,
      '#default_value' => $config->get('ldap_server'),
      '#description' => (
        'Select the LDAP server to use for querying the LDAP directory listing'
      ),
    ];

    $form['base_dn'] = [
      '#type' => 'textfield',
      '#title' => 'Base DN',
      '#default_value' => $config->get('base_dn'),
      '#description' => (
        'The base Distinguished Name that identifies the root of the '
        . 'user directory'
      ),
    ];

    $form['filter'] = [
      '#type' => 'textfield',
      '#title' => 'Filter Format',
      '#default_value' => $config->get('filter'),
      '#description' => (
        'The format string used to generate the filter string that queries '
        . 'membership of section groups. The "%s" token will be replaced with '
        . 'the Distinguished Name of the group.'
      ),
    ];

    $form['name_attr'] = [
      '#type' => 'textfield',
      '#title' => 'Display Name Attribute',
      '#default_value' => $config->get('name_attr'),
      '#description' => (
        'The user display name attribute name.'
      ),
    ];

    $form['email_attr'] = [
      '#type' => 'textfield',
      '#title' => 'Email Attribute',
      '#default_value' => $config->get('email_attr'),
      '#description' => (
        'The user email attribute name.'
      ),
    ];

    $form['title_attr'] = [
      '#type' => 'textfield',
      '#title' => 'Job Title Attribute',
      '#default_value' => $config->get('title_attr'),
      '#description' => (
        'The user job title attribute name.'
      ),
    ];

    $form['phone_attr'] = [
      '#type' => 'textfield',
      '#title' => 'Phone Number Attribute',
      '#default_value' => $config->get('phone_attr'),
      '#description' => (
        'The user phone number attribute name.'
      ),
    ];

    return parent::buildForm($form,$form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form,FormStateInterface $form_state) {
    $container = \Drupal::getContainer();
    $entityTypeManager = $container->get('entity_type.manager');
    $storage = $entityTypeManager->getStorage('ldap_server');

    // Verify server ID.

    $serverId = $form_state->getValue('ldap_server');

    if ($serverId != self::UNSET) {
      $query = $storage->getQuery();
      $query->condition('id',$serverId,'=');
      $queryResult = $query->execute();
      if (empty($queryResult)) {
        $form_state->setErrorByName(
          'ldap_server',
          "Server '$serverId' is invalid. Please enter a valid server."
        );
      }
    }

    // Verify fields that should be non-empty.

    $nonEmpty = [
      'title',
    ];

    foreach ($nonEmpty as $field) {
      $value = $form_state->getValue($field);
      if (empty($value)) {
        $form_state->setErrorByName(
          $field,
          'Field cannot be empty'
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form,FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_OBJECT);

    $entries = [
      'title' => 'title',
      'ldap_server' => 'ldap_server',
      'base_dn' => 'base_dn',
      'filter' => 'filter',
      'name_attr' => 'name_attr',
      'email_attr' => 'email_attr',
      'title_attr' => 'title_attr',
      'phone_attr' => 'phone_attr',
    ];

    foreach ($entries as $formKey => $configKey) {
      $config->set($configKey,$form_state->getValue($formKey));
    }

    $config->save();

    parent::submitForm($form,$form_state);

    Cache::invalidateTags([DirectoryPage::CACHE_TAG]);
  }
}
