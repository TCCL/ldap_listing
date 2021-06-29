<?php

/**
 * DirectorySection.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the directory_section entity.
 *
 * @ConfigEntityType(
 *   id = "directory_section",
 *   label = @Translation("Directory Listing Section"),
 *   handlers = {},
 *   config_prefix = "directory_section",
 *   admin_permission = "administer ldap_listing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label"
 *   }
 * )
 */
class DirectorySection extends ConfigEntityBase {
  /**
   * Creates a new DirectorySection instance.
   *
   * @param array $values
   *   Values.
   * @param string $entity_type
   *   Entity Type.
   */
  public function __construct(array $values,$entity_type) {
    parent::__construct($values,$entity_type);
  }
}
