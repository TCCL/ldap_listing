<?php

/**
 * DirectoryQuery.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ldap_listing\Form\SettingsForm;
use Drupal\ldap_servers\LdapBridgeInterface;
use Symfony\Component\Ldap\Entry;

class DirectoryQuery {
  private $config;

  /**
   * @var \Drupal\ldap_servers\LdapBridgeInterface
   */
  private $ldapBridge;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $storage;

  /**
   * @var \Drupal\ldap_servers\ServerInterface
   */
  private $ldapServer;

  /**
   * Creates a new DirectoryQuery instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LdapBridgeInterface $ldapBridge)
  {
    $this->ldapBridge = $ldapBridge;
    $this->entityTypeManager = $entityTypeManager;
    $this->storage = $entityTypeManager->getStorage('ldap_listing_directory_section');

    $this->config = \Drupal::config(SettingsForm::CONFIG_OBJECT);
    $serverId = $this->config->get('ldap_server');
    $this->ldapServer = $entityTypeManager
                      ->getStorage('ldap_server')
                      ->load($serverId);
  }

  /**
   * Determines if an LDAP server has been configured for the service to use for
   * the querying.
   *
   * @return bool
   */
  public function hasLDAPServerConfigured() : bool {
    return isset($this->ldapServer);
  }

  /**
   * Ensures that the LDAP bridge is set to the LDAP server used for directory
   * listing queries and that a connection could be bound.
   */
  public function bind() : void {
    $this->ldapBridge->setServerById($this->ldapServer->get('id'));
    if (!$this->ldapBridge->bind()) {
      throw new Exception('Cannot bind to LDAP server');
    }
  }

  /**
   * Queries all directory listing sections. Sections are ordered by their
   * configured weighting.
   *
   * @return array
   */
  public function queryAll() : array {
    $sections = $this->storage->getQuery()->execute();

    $results = [];
    foreach ($sections as $sectionId) {
      $results[] = $this->querySection($sectionId);
    }

    // Sort by configured weighting.
    usort(
      $results,
      function(array $a,array $b) {
        return $a['weight'] - $b['weight'];
      }
    );

    return $results;
  }

  /**
   * Performs an LDAP query to obtain the information for the indicated section.
   *
   * @param string $sectionId
   *
   * @return array
   */
  public function querySection(string $sectionId) : array {
    $section = $this->storage->load($sectionId);
    if (!isset($section)) {
      throw new Exception("Section '$sectionId' was not defined");
    }

    // Get base DN and filter string. The filter string is generated by
    // formatting the section group DN using the configured filter format.
    $baseDN = $this->config->get('base_dn');
    $filterFormat = $this->config->get('filter');
    $filter = sprintf($filterFormat,$section->get('group_dn'));

    if (empty($baseDN) || empty($filter)) {
      throw new Exception(
        "Cannot query directory info: base_dn/filter not configured"
      );
    }

    $body = $this->doQuery($baseDN,$filter);
    $header = $section->get('header_entries');
    $footer = $section->get('footer_entries');

    // Sort body elements by name.
    usort($body,function(array $a,array $b) {
      return strcmp($a['name'],$b['name']);
    });

    self::padLists($header);
    self::padLists($footer);

    return [
      'id' => $section->get('id'),
      'label' => $section->get('label'),
      'abbrev' => $section->get('abbrev'),
      'header' => $header,
      'body' => $body,
      'footer' => $footer,
      'weight' => $section->getWeight(),
    ];
  }

  private function doQuery(string $baseDN,string $filter,array $options = []) : array {

    // Prepare attribute filter.
    $attrs = [
      'name_attr' => 'name',
      'email_attr' => 'email',
      'title_attr' => 'title',
      'phone_attr' => 'phone',
    ];

    $attrMap = [];

    $options['filter'] = [];
    foreach ($attrs as $key => $name) {
      $attr = $this->config->get($key);
      if (empty($attr)) {
        throw new Exception("Attribute '$key' is not configured");
      }
      $attrMap[$attr] = $name;
      $options['filter'][] = $attr;
    }

    $entries = $this->ldapBridge
             ->get()
             ->query($baseDN,$filter,$options)
             ->execute()
             ->toArray();

    $result = array_map(
      function(Entry $entry) use($attrMap) {
        $attributes = [];
        foreach ($entry->getAttributes() as $name => list($value)) {
          $attributes[$attrMap[$name]] = $value;
        }

        return [
          'dn' => $entry->getDn(),
          'emailLink' => "mailto:{$attributes['email']}",

        ] + $attributes;
      },
      $entries
    );

    return $result;
  }

  private static function padLists(array &$list) : void {
    $max = 0;
    foreach ($list as $sublist) {
      if (count($sublist) > $max) {
        $max = count($sublist);
      }
    }

    foreach ($list as &$sublist) {
      while (count($sublist) < $max) {
        $sublist[] = null;
      }
    }
    unset($sublist);
  }
}
