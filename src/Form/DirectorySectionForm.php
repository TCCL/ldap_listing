<?php

namespace Drupal\ldap_listing\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_listing\DirectoryQuery;

class DirectorySectionForm extends EntityForm {
  /**
   * @var \Drupal\ldap_listing\Entity\DirectorySectionInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form,FormStateInterface $form_state) {
    $form = parent::form($form,$form_state);

    $form['directory_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Directory Listing Section'),
      '#open' => true,
    ];

    $form['directory_section']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading'),
      '#maxlength' => 256,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The heading label for this section.'),
      '#required' => true,
    ];

    $form['directory_section']['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ldap_listing\Entity\DirectorySection::load',
        'source' => ['directory_section','label'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['directory_section']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('The description label for this section.'),
      '#maxlength' => 512,
      '#required' => false,
    ];

    $form['directory_section']['abbrev'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Abbreviation'),
      '#description' => $this->t('Choose an abbreviation that describes this section'),
      '#default_value' => $this->entity->get('abbrev'),
      '#maxlength' => 16,
      '#required' => false,
    ];

    $form['directory_section']['group_dn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group DN'),
      '#description' => $this->t(
        'The Distinguished Name of the LDAP group to assign to this section. '
        . 'The members of the group will populate this directory listing section.'
      ),
      '#default_value' => $this->entity->get('group_dn'),
      '#maxlength' => 1024,
      '#required' => true,
    ];

    $form['directory_section']['depth'] = [
      '#type' => 'number',
      '#title' => 'Depth',
      '#description' => $this->t(
        'The recursive depth for the LDAP search to apply when a group contains '
        . 'subgroups. A value less than 1 means an unlimited depth.'
      ),
      '#default_value' => $this->entity->get('depth') ?? 1,
      '#required' => false,
    ];

    $form['directory_section']['header_entries'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Static Header Entries'),
      '#description' => $this->t(
        'Static entries that are added to the section header. Each comma-separated '
        . 'element is rendered as a distinct column in the entry row.'
      ),
      '#default_value' => $this->entity->getHeaderEntriesText(),
      '#cols' => 80,
      '#rows' => 5,
    ];

    $form['directory_section']['footer_entries'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Static Footer Entries'),
      '#description' => $this->t(
        'Static entries that are added to the section footer. Each comma-separated '
        . 'element is rendered as a distinct column in the entry row.'
      ),
      '#default_value' => $this->entity->getFooterEntriesText(),
      '#cols' => 80,
      '#rows' => 5,
    ];

    $form['directory_section']['exclude_from_directory'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude From Directory Page'),
      '#description' => $this->t(
        'Determines if the section is excluded from the directory page. Note: the section '
        . 'can still be rendered in a field element when excluded from the directory page.'
      ),
      '#default_value' => $this->entity->get('exclude_from_directory') ?? false,
    ];

    $form['directory_section']['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $this->entity->get('weight') ?? 0,
      '#description' => $this->t('The weight value used to sort the list of sections'),
      '#required' => false,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form,FormStateInterface $form_state) {
    $this->entity->setGroupDN($form_state->getValue('group_dn'));
    $this->entity->setHeaderEntriesFromText($form_state->getValue('header_entries'));
    $this->entity->setFooterEntriesFromText($form_state->getValue('footer_entries'));

    $status = $this->entity->save();

    if ($status == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Created section %label',[
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
}
