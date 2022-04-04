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
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
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
 *     "add-form" = "/admin/structure/ldap_listing/tweak/add",
 *     "edit-form" = "/admin/structure/ldap_listing/tweak/{ldap_listing_tweak}/edit",
 *     "delete-form" = "/admin/structure/ldap_listing/tweak/{ldap_listing_tweak}/delete",
 *     "collection" = "/admin/structure/ldap_listing/tweak"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "section_id",
 *     "user_dn",
 *     "name_override",
 *     "phone_override",
 *     "email_override",
 *     "job_title_override",
 *     "position_before_user_dn",
 *     "position_after_user_dn",
 *     "absolute_position",
 *     "exclude"
 *   }
 * )
 */
class Tweak extends ConfigEntityBase implements TweakInterface {
  protected $section_id;
  protected $user_dn;
  protected $name_override;
  protected $phone_override;
  protected $email_override;
  protected $job_title_override;
  protected $position_before_user_dn;
  protected $position_after_user_dn;
  protected $absolute_position;
  protected $exclude;

  /**
   * {@inheritdoc}
   */
  public function sectionId() : ?string {
    return $this->get('section_id');
  }

  /**
   * {@inheritdoc}
   */
  public function userDn() : string {
    return $this->get('user_dn') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function nameOverride() : string {
    return $this->get('name_override') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function phoneOverride() : string {
    return $this->get('phone_override') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function emailOverride() : string {
    return $this->get('email_override') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function jobTitleOverride() : string {
    return $this->get('job_title_override') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function positionBeforeUserDN() : string {
    return $this->get('position_before_user_dn') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function positionAfterUserDN() : string {
    return $this->get('position_after_user_dn') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function absolutePosition() : ?int {
    return $this->get('absolute_position');
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() : bool {
    return $this->get('exclude') ?? false;
  }

  /**
   * Gets the complete tweak info in a single array.
   *
   * @return array
   */
  public function getTweakInfo() : array {
    $info = [
      'overrides' => [
        'name' => $this->nameOverride(),
        'phone' => $this->phoneOverride(),
        'email' => $this->emailOverride(),
        'jobTitle' => $this->jobTitleOverride(),
      ],

      'relativeToUser' => [
        'before' => $this->positionBeforeUserDN(),
        'after' => $this->positionAfterUserDN(),
      ],

      'absolutePosition' => $this->absolutePosition(),

      'isExcluded' => $this->isExcluded(),
    ];

    return $info;
  }
}
