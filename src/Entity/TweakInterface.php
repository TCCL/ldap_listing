<?php

/**
 * TweakInterface.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface TweakInterface extends ConfigEntityInterface {
  /**
   * Gets the ID of the section that is modified by this tweak instance.
   *
   * @return string
   */
  public function sectionId() : string;

  /**
   * Gets the distinguished name of the user modified by the tweak.
   *
   * @return string
   */
  public function userDn() : string;

  /**
   * Gets the name override value.
   *
   * @return string
   */
  public function nameOverride() : string;

  /**
   * Gets the phone override value.
   *
   * @return string
   */
  public function phoneOverride() : string;

  /**
   * Gets the email override value.
   *
   * @return string
   */
  public function emailOverride() : string;

  /**
   * Gets the job title override value.
   *
   * @return string
   */
  public function jobTitleOverride() : string;

  /**
   * Gets the distinguished name of the user before which to position the
   * tweaked user.
   *
   * @return string
   */
  public function positionBeforeUserDN() : string;

  /**
   * Gets the distinguished name of the user after which to position the tweaked
   * user.
   *
   * @return string
   */
  public function positionAfterUserDN() : string;

  /**
   * Gets the absolute position index within the section listing with which to
   * position the tweaked user.
   *
   * @return int
   */
  public function absolutePosition() : int;

  /**
   * Determines if the tweak excludes the user from the section listing.
   *
   * @return bool
   */
  public function isExcluded() : bool;
}
