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
use Drupal\file\Entity\File;
use Drupal\ldap_listing\DirectoryQuery;
use Drupal\ldap_listing\Support\EntryParser;

class SettingsForm extends ConfigFormBase {
  const CONFIG_OBJECT = 'ldap_listing.settings';
  const UNSET = '';

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

    if (!empty($servers)) {
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
    }
    else {
      $form['ldap_server'] = [
        '#type' => 'select',
        '#title' => 'LDAP Server',
        '#options' => [],
        '#empty_option' => '-- No Options --',
        '#empty_value' => '',
        '#default_value' => $config->get('ldap_server'),
        '#description' => (
          'No LDAP servers have been created. Create an LDAP server '
          .'configuration in order to configure this module.'
        ),
      ];
    }

    $form['base_dn'] = [
      '#type' => 'textfield',
      '#title' => 'Base DN',
      '#default_value' => $config->get('base_dn'),
      '#description' => (
        'The base Distinguished Name for a user search'
      ),
    ];

    $form['group_base_dn'] = [
      '#type' => 'textfield',
      '#title' => 'Group Base DN',
      '#default_value' => $config->get('group_base_dn'),
      '#description' => (
        'The base Distinguished Name for a group search'
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

    $form['group_filter'] = [
      '#type' => 'textfield',
      '#title' => 'Group Filter Format',
      '#default_value' => $config->get('group_filter'),
      '#description' => (
        'The format string used to generate the filter string that queries '
        . 'subgroups within section groups. The "%s" token will be replaced with '
        . 'the Distinguished Name of the group. This is only used when a section has '
        . 'a configured recursive depth.'
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

    $form['manager_attr'] = [
      '#type' => 'textfield',
      '#title' => 'Manager Attribute (Optional)',
      '#default_value' => $config->get('manager_attr'),
      '#description' => (
        'The LDAP field that contains the user manager. (This '
        . 'field is optional and is used to rank the items in '
        . 'each section.)'
      ),
    ];

    $form['reports_attr'] = [
      '#type' => 'textfield',
      '#title' => 'Reports Attribute (Optional)',
      '#default_value' => $config->get('reports_attr'),
      '#description' => (
        'The LDAP field that contains the user employee reports. (This '
        . 'field is optional and is used to rank the items in '
        . 'each section.)'
      ),
    ];

    $form['invalidate_time'] = [
      '#type' => 'radios',
      '#options' => [
        0 => 'Never',
        86400 => 'Daily',
        604800 => 'Weekly',
        2592000 => 'Every 30 days',
      ],
      '#title' => 'Cache Invalidation Interval',
      '#default_value' => $config->get('invalidate_time'),
      '#description' => (
        'The interval of time that elapses before the directory page cache '
        . 'invalidates.'
      ),
    ];

    $form['link_to_user_page'] = [
      '#type' => 'checkbox',
      '#title' => 'Link to User Profile Page',
      '#default_value' => $config->get('link_to_user_page'),
      '#description' => (
        'If enabled, user entries in the directory listing will link to '
        . 'user profile pages if the entry can be mapped to a Drupal user.'
      ),
    ];

    $userPageAttributes = $config->get('user_page_attributes');
    $parser = new EntryParser;
    foreach ($userPageAttributes as $item) {
      $parser->addEntry([$item['attribute_label'],$item['attribute_name']]);
    }
    $form['user_page_attributes'] = [
      '#type' => 'textarea',
      '#title' => 'User Profile Page Attributes',
      '#default_value' => $parser->makeText(),
      '#description' => (
        'The list of LDAP attributes to fetch and render on the user profile '
        . 'page if enabled. Each line is a comma-separated pair '
        . '- <i>Attribute Label</i>,<i>attributeName</i> - denoting the label '
        . 'for the attribute and its LDAP name. Example: <i>Email Address,mail</i>'
      ),
      '#cols' => 80,
      '#rows' => 5,
    ];

    $form['preamble'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Preamble Message'),
      '#default_value' => $config->get('preamble'),
      '#description' => $this->t(
        'Optional preamble message to display on directory page.'
      ),
      '#cols' => 80,
      '#rows' => 5,
    ];

    $form['enable_pdf'] = [
      '#type' => 'checkbox',
      '#title' => 'Enable PDF Generation',
      '#default_value' => $config->get('enable_pdf'),
      '#description' => (
        'Enable PDF generation from the directory page.'
      ),
    ];

    $form['pdf_class'] = [
      '#type' => 'textfield',
      '#title' => 'PDF Document Generation Class',
      '#default_value' => $config->get('pdf_class'),
      '#description' => (
        'Specify the fully-qualified PHP class name that implements PDF '
        . 'generation. Leave empty to utilize the default functionality '
        . 'provided by the <i>ldap_listing</i> module.'
      ),
    ];

    $form['pdf_title'] = [
      '#type' => 'textfield',
      '#title' => 'PDF Title',
      '#default_value' => $config->get('pdf_title'),
      '#description' => (
        'The title text for the directory PDF document. May also be used to '
        . 'name the file download.'
      ),
    ];

    $form['pdf_header_image_file'] = [
      '#type' => 'managed_file',
      '#title' => 'PDF Header Image',
      '#upload_location' => 'public://images/ldap_listing',
      '#multiple' => false,
      '#upload_validators' => [
        // We include common file extensions for all image types supported by
        // TCPDF.
        'file_validate_extensions' => ['png jpg jpeg svg'],
      ],
      '#description' => (
        'Provide an image to use in the PDF header. Note: the use of this '
        . 'image depends on which PDF generation implementation you select.'
      ),
    ];

    $imageId = (int)$config->get('pdf_header_image_file_id');
    if ($imageId > 0) {
      $image = File::load($imageId);
      if ($image) {
        $form['pdf_header_image_file']['#default_value'] = [
          'target_id' => $image->id(),
        ];
      }
    }

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

    // Parse and verify user page attributes value.

    $parser = new EntryParser;
    $parser->parseText($form_state->getValue('user_page_attributes'));
    $userPageAttributes = [];
    foreach ($parser->getEntries() as $items) {
      $label = $items[0] ?? null;
      $attribute = $items[1] ?? null;
      if (empty($attribute) || empty($label)) {
        $form_state->setErrorByName(
          'user_page_attributes',
          'Value is invalid'
        );
        break;
      }
      $userPageAttributes[] = [
        'attribute_label' => $label,
        'attribute_name' => $attribute,
      ];
    }
    $form_state->setValue('user_page_attributes',$userPageAttributes);
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
      'group_base_dn' => 'group_base_dn',
      'filter' => 'filter',
      'group_filter' => 'group_filter',
      'name_attr' => 'name_attr',
      'email_attr' => 'email_attr',
      'title_attr' => 'title_attr',
      'phone_attr' => 'phone_attr',
      'manager_attr' => 'manager_attr',
      'reports_attr' => 'reports_attr',
      'invalidate_time' => 'invalidate_time',
      'link_to_user_page' => 'link_to_user_page',
      'user_page_attributes' => 'user_page_attributes',
      'preamble' => 'preamble',
      'enable_pdf' => 'enable_pdf',
      'pdf_class' => 'pdf_class',
      'pdf_title' => 'pdf_title',
    ];

    foreach ($entries as $formKey => $configKey) {
      $config->set($configKey,$form_state->getValue($formKey));
    }

    $pdfHeaderImageFile = $form_state->getValue('pdf_header_image_file');
    if (is_array($pdfHeaderImageFile) && isset($pdfHeaderImageFile[0])) {
      $imageId = (int)$pdfHeaderImageFile[0];
      $config->set('pdf_header_image_file_id',$imageId ?: null);

      if ($imageId) {
        $file = File::load($imageId);
        if ($file->isTemporary()) {
          $file->setPermanent();
          $file->save();
        }
      }
    }

    $config->save();

    parent::submitForm($form,$form_state);

    Cache::invalidateTags([DirectoryQuery::CACHE_TAG]);
  }
}
