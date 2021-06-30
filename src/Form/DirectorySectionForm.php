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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form,FormStateInterface $form_state) {
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
