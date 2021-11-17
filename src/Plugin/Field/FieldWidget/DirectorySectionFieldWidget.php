<?php

/**
 * DirectorySectionFieldWidget.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Widget type for LDAP Directory Section field type.
 *
 * @FieldWidget(
 *   id = "ldap_listing_directory_section",
 *   label = "LDAP Directory Section Widget",
 *   field_types = {
 *     "ldap_listing_directory_section"
 *   }
 * )
 */
class DirectorySectionFieldWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state)
  {
    $element['value'] = [
      '#type' => 'select',
      '#title' => 'LDAP Directory Section',
      '#options' => $this->makeOptions(),
      '#sort_options' => true,
      '#default_value' => $items[$delta]->value ?? 0,
    ];

    return $element;
  }

  private function makeOptions() : array {
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage('ldap_listing_directory_section');

    $options = [];

    $sections = $storage->getQuery()->execute();
    foreach ($sections as $sectionId) {
      $section = $storage->load($sectionId);
      if (!isset($section)) {
        continue;
      }

      $options[$sectionId] = $section->get('label');
    }

    return $options;
  }
}
