<?php

/**
 * UserQuery.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\externalauth\Authmap;
use Drupal\ldap_listing\Form\SettingsForm;
use Drupal\ldap_servers\LdapBridgeInterface;
use Drupal\ldap_servers\LdapTransformationTraits;
use Drupal\user\UserInterface;
use Symfony\Component\Ldap\Exception\LdapException;

class UserQuery {
  use LdapTransformationTraits;

  /**
   * Loaded configuration item 'user_page_attributes'.
   *
   * @var array
   */
  private $userPageAttributes;

  /**
   * The LDAP server config.
   *
   * @var \Drupal\ldap_servers\Entity\Server
   */
  private $ldapServer;

  /**
   * Active LDAP connection.
   *
   * @var \Symfony\Component\Ldap\Ldap
   */
  private $ldap;

  /**
   * External auth authmap instance.
   *
   * @var \Drupal\externalauth\Authmap
   */
  private $externalAuth;

  /**
   * Creates a new UserQuery instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   * @param \Drupal\ldap_servers\LdapBridgeInterface
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LdapBridgeInterface $ldapBridge,
    Authmap $externalAuth)
  {
    $config = \Drupal::config(SettingsForm::CONFIG_OBJECT);

    // Load LDAP server config object.
    $serverId = $config->get('ldap_server');
    if (empty($serverId)) {
      throw new Exception('No LDAP server is configured in settings');
    }

    $this->ldapServer = $entityTypeManager->getStorage('ldap_server')->load($serverId);
    if (!$this->ldapServer) {
      throw new Exception("LDAP server '$serverId' was not found");
    }

    $ldapBridge->setServerById($serverId);
    if (!$ldapBridge->bind()) {
      throw new Exception("Cannot bind to LDAP server '$serverId'");
    }

    $this->ldap = $ldapBridge->get();

    $this->userPageAttributes = $config->get('user_page_attributes');
    if (empty($this->userPageAttributes)) {
      throw new Exception("No configured 'user_page_attributes'");
    }

    $this->externalAuth = $externalAuth;
  }

  /**
   * Queries user attributes for the indicated user.
   *
   * @param \Drupal\user\UserInterface $user
   *
   * @return array
   */
  public function query(UserInterface $user) : array {
    // Verify that the user account is connected to an LDAP account via
    // externalauth.

    $userName = $this->externalAuth->get($user->id(),'ldap_user');
    if (!$userName) {
      throw new Exception('The specified user does not map to LDAP');
    }

    // Try each configured base DN in order to query the data.

    foreach ($this->ldapServer->getBaseDn() as $baseDn) {
      $result = $this->queryUserInfo($userName,$baseDn);
      if (is_array($result)) {
        return $result;
      }
    }

    throw new Exception("Unable to query user information");
  }

  private function queryUserInfo(string $userName,string $baseDn) {
    // Prepare query using attributes configured on the LDAP server.
    $query = sprintf(
      '(%s=%s)',
      $this->ldapServer->getAuthenticationNameAttribute(),
      $this->ldapEscapeFilter($userName)
    );

    $options['filter'] = $this->grabAttributes();

    try {
      $response = $this->ldap->query($baseDn,$query,$options)->execute();
    } catch (LdapException $ex) {
      return false;
    }

    if ($response->count() < 1) {
      return false;
    }

    $entry = $response[0];
    $attributes = $entry->getAttributes();

    $result = [];
    foreach ($this->userPageAttributes as $attribute) {
      $name = $attribute['attribute_name'];
      $label = $attribute['attribute_label'];
      $value = $attributes[$name][0] ?? null;

      $result[$name] = [
        'label' => $label,
        'value' => $value,
      ];
    }

    return $result;
  }

  private function grabAttributes() : array {
    return array_column($this->userPageAttributes,'attribute_name');
  }
}
