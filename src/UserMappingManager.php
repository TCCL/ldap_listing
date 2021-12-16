<?php

/**
 * UserMappingManager.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing;

use Drupal\ldap_servers\ServerInterface;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Symfony\Component\Ldap\Entry;

/**
 * Helper class used to efficiently map LDAP entries to Drupal users.
 */
class UserMappingManager {
  /**
   * The LDAP server from which user identities are pulled.
   *
   * @var \Drupal\ldap_servers\ServerInterface
   */
  private $ldapServer;

  /**
   * Cached map of PUID to user entity.
   *
   * @var array
   */
  private $userInfo;

  /**
   * Creates a new UserMappingManager instance.
   *
   * @param \Drupal\ldap_servers\ServerInterface $ldapServer
   */
  public function __construct(ServerInterface $ldapServer) {
    $this->ldapServer = $ldapServer;
  }

  /**
   * Maps an LDAP entry to a Drupal user.
   *
   * @param \Symfony\Component\Ldap\Entry $entry
   *  The LDAP entry to map.
   *
   * @return ?\Drupal\user\UserInterface
   *  Returns null if the user mapping was unsuccessful.
   */
  public function mapUserFromLdapEntry(Entry $entry) : ?UserInterface {
    // Lazy load the users map.
    if (!isset($this->userInfo)) {
      $this->loadMapping();
    }

    $user = null;

    $puid = $this->ldapServer->derivePuidFromLdapResponse($entry);
    if (!empty($puid) && isset($this->userInfo[$puid])) {
      $user = $this->userInfo[$puid];
    }

    return $user;
  }

  private function loadMapping() : void {
    // Load all users having a PUID attribute that is specific to the configured
    // LDAP server.

    $this->userInfo = [];

    $serverId = $this->ldapServer->id();
    $persistAttr = $this->ldapServer->getUniquePersistentAttribute();

    $entityTypeManager = \Drupal::service('entity_type.manager');
    $query = $entityTypeManager
      ->getStorage('user')
      ->getQuery()
      ->condition('ldap_user_puid_sid',$serverId,'=')
      ->condition('ldap_user_puid_property',$persistAttr,'=')
      ->accessCheck(false);

    $ids = $query->execute();

    $users = User::loadMultiple($ids);

    foreach ($users as $user) {
      $fld = $user->get('ldap_user_puid')->first();
      if ($fld) {
        $puid = $fld->getString();
        $this->userInfo[$puid] = $user;
      }
    }
  }
}
