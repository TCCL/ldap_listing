<?php

namespace Drupal\ldap_listing\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

class DirectorySectionForm extends EntityForm {
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
      '#description' => $this->t('Choose a heading label for this section'),
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

    $status = $this->entity->save();

    if ($status == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Create section %label',[
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('Updated section %label',[
        '%label' => $this->entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }
}
