<?php

/**
 * Tweak.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * @ConfigEntityType(
 *   id = "ldap_listing_tweak",
 *   label = @Translation("Directory Listing Tweak"),
 *   handlers = {
 *     "list_builder" = "Drupal\ldap_listing\TweakEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ldap_listing\Form\TweakForm",
 *       "edit" = "Drupal\ldap_listing\Form\TweakForm",
 *       "delete" = "Drupal\ldap_listing\Form\TweakDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     }
 *   },
 *   config_prefix = "tweak",
 *   admin_permission = "administer ldap_listing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/ldap_listing_tweak/add",
 *     "edit-form" = "/admin/structure/ldap_listing_tweak/{ldap_listing_tweak}/edit",
 *     "delete-form" = "/admin/structure/ldap_listing_tweak/{ldap_listing_tweak}/delete",
 *     "collection" = "/admin/structure/ldap_listing_tweak"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "user_dn",
 *     "name_override",
 *     "phone_override",
 *     "email_override",
 *     "job_title_override",
 *     "position_before_user_dn",
 *     "position_after_user_dn",
 *     "exclude"
 *   }
 * )
 */
class Tweak extends ConfigEntityBase implements TweakInterface {
  /**
   * {@inheritdoc}
   */
  public function sectionId() : string {
    return $this->get('section_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function userDn() : string {
    return $this->get('user_dn')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function nameOverride() : string {
    return $this->get('name_override')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function phoneOverride() : string {
    return $this->get('phone_override')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function emailOverride() : string {
    return $this->get('email_override')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function jobTitleOverride() : string {
    return $this->get('job_title_override')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function positionBeforeUserDN() : string {
    return $this->get('position_before_user_dn')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function positionAfterUserDN() : string {
    return $this->get('position_after_user_dn')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function absolutePosition() : int {
    return $this->get('absolute_position')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() : bool {
    return $this->get('excluded')->value;
  }
}
