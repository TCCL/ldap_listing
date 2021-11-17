<?php

/**
 * DirectorySectionFieldItem.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type for rendering a single directory section.
 *
 * @FieldType(
 *   id = "ldap_listing_directory_section",
 *   label = "LDAP Directory Section",
 *   description = "Field item for rendering single directory section",
 *   category = "LDAP Listing",
 *   default_widget = "ldap_listing_directory_section",
 *   default_formatter = "ldap_listing_directory_section"
 * )
 */
class DirectorySectionFieldItem extends FieldItemBase {
  public static function schema(FieldStorageDefinitionInterface $field_def) {
    return [
      'columns' => [
        // Store the ID of the referenced ldap_listing_directory_section config
        // entity.
        'value' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => false,
        ],
      ],
    ];
  }

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_def) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();

    if (empty($value)) {
      return true;
    }

    // Check if the directory section still exists.
    $section = $this->loadDirectorySection($value);
    return is_null($section);
  }

  private function loadDirectorySection(string $id) : ?EntityInterface {
    static $storage;
    if (!isset($storage)) {
      $entityTypeManager = \Drupal::service('entity_type.manager');
      $storage = $entityTypeManager->getStorage('ldap_listing_directory_section');
    }

    return $storage->load($id);
  }
}
