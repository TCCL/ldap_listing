<?php

/**
 * SettingsForm.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form,FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_OBJECT);

    $entries = [
      'ldap_server' => 'ldap_server',
      'base_dn' => 'base_dn',
      'filter' => 'filter',
    ];

    foreach ($entries as $formKey => $configKey) {
      $config->set($configKey,$form_state->getValue($formKey));
    }

    $config->save();

    parent::submitForm($form,$form_state);
  }
}
