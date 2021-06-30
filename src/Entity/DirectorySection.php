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
 *   id = "ldap_listing_directory_section",
 *   label = @Translation("Directory Listing Section"),
 *   handlers = {
 *     "list_builder" = "Drupal\ldap_listing\DirectorySectionEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ldap_listing\Form\DirectorySectionForm",
 *       "edit" = "Drupal\ldap_listing\Form\DirectorySectionForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   config_prefix = "directory_section",
 *   admin_permission = "administer ldap_listing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/ldap_listing_directory_section/add",
 *     "edit-form" = "/admin/structure/ldap_listing_directory_section/{ldap_listing_directory_section}/edit",
 *     "collection" = "/admin/structure/ldap_listing_directory_section"
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
