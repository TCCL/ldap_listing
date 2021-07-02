<?php

/**
 * DirectorySectionInterface.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface DirectorySectionInterface extends ConfigEntityInterface {
  /**
   * Gets the DN of the group assigned to the directory listing section.
   *
   * @return string
   */
  public function getGroupDN() : string;

  /**
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  public function setGroupDN(string $groupDN);

  /**
   * @return string
   */
  public function getHeaderEntriesText() : string;

  /**
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  public function setHeaderEntriesFromText(string $text);
}
