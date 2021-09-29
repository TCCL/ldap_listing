<?php

/**
 * DirectoryQuery.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ldap_listing\Form\SettingsForm;
use Drupal\ldap_servers\LdapBridgeInterface;
use Symfony\Component\Ldap\Entry;

class DirectoryQuery {
  const CACHE_TAG = 'ldap_listing_directory_query';

  /**
   * Invalidates the directory query cache if the configured invalidation
   * interval has elapsed.
   */
  public static function invalidateIfElapsed() {
    $state = \Drupal::state();
    $config = \Drupal::config(SettingsForm::CONFIG_OBJECT);

    $amount = $config->get('invalidate_time');
    if ($amount <= 0) {
      // Special case: non-positive amount means do not invalidate.
      return;
    }
    $lastRun = $state->get('ldap_listing_last_cache_invalidate',0);
    $moment = time() - $amount;

    if ($moment >= $lastRun) {
      Cache::invalidateTags([DirectoryQuery::CACHE_TAG]);
    }
  }

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
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
   * Queries all directory listing sections. The query is cached for later
   * lookup so a future call to this method may retrieve cached data.
   *
   * @param int $time
   * @param bool $forceInvalidate
   *
   * @return array
   */
  public function queryAllCached(&$time,bool $forceInvalidate = false) : array {
    if (!$forceInvalidate) {
      $sections = $this->getCached($time);
    }

    if (!isset($sections)) {
      // Query data from LDAP if nothing was pulled from cache.
      $sections = $this->queryAll();
      $this->setCached($time,$sections);
    }

    return $sections;
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

  /**
   * Queries directory section information. If the information is in the cache,
   * then the cached version is returned. Otherwise the information is queried
   * directly from the LDAP server.
   *
   * @param string $sectionId
   * @param bool $forceInvalidate
   *
   * @return array
   */
  public function querySectionCached(string $sectionId,
                                     bool $forceInvalidate = false) : array
  {
    // See if we have the section in the cache.
    if (!$forceInvalidate) {
      $sections = $this->getCached($time);
      if (isset($sections) && is_array($sections)) {
        $ids = array_column($sections,'id');
        $key = array_search($sectionId,$ids);
        if ($key !== false) {
          return $sections[$key];
        }
      }
    }

    return $this->querySection($sectionId);
  }

  private function getCached(&$time) : ?array {
    $cache = \Drupal::cache();
    $cid = self::makeCacheId();

    // Attempt pull from cache if we are not invalidating.
    $bucket = $cache->get($cid);

    if (isset($bucket) && is_array($bucket)) {
      // Pull data from cache bucket.
      $time = (int)$bucket->created;
      return $bucket->data;
    }

    return null;
  }

  private function setCached(&$time,array $sections) : void {
    $cache = \Drupal::cache();
    $cid = self::makeCacheId();
    $time = time();

    $cache->set($cid,$sections,Cache::PERMANENT,[DirectoryQuery::CACHE_TAG]);

    $state = \Drupal::state();
    $state->set('ldap_listing_last_cache_invalidate',$time);
  }

  private function doQuery(string $baseDN,string $filter,array $options = []) : array {

    // Prepare attribute filter.
    $attrs = [
      'name_attr' => 'name',
      'email_attr' => 'email',
      'title_attr' => 'title',
      'phone_attr' => 'phone',
    ];

    $optionalAttrs = [
      'manager_attr' => 'manager',
      'reports_attr' => 'reports',
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
    foreach ($optionalAttrs as $key => $name) {
      $attr = $this->config->get($key);
      if (empty($attr)) {
        continue;
      }
      $attrMap[$attr] = $name;
      $options['filter'][] = $attr;
    }

    $entries = $this->ldapBridge
             ->get()
             ->query($baseDN,$filter,$options)
             ->execute()
             ->toArray();

    $group = [];

    $result = array_map(
      function(Entry $entry) use($attrMap,&$group) {
        $attributes = [];
        foreach ($entry->getAttributes() as $name => $values) {
          if (count($values) == 1) {
            $attributes[$attrMap[$name]] = $values[0];
          }
          else {
            $attributes[$attrMap[$name]] = $values;
          }
        }

        if (!empty($attributes['email'])) {
          $emailLink = "mailto:{$attributes['email']}";
        }
        else {
          $emailLink = null;
        }

        $dn = $entry->getDn();
        $group[$dn] = true;

        return [
          'dn' => $dn,
          'emailLink' => $emailLink,
          'rank' => 0,

        ] + $attributes;
      },
      $entries
    );

    if (!empty($optionalAttrs)) {
      array_walk($result,function(array &$entry) use($optionalAttrs,$group) {
        $manager = $entry['manager'] ?? null;
        $reports = $entry['reports'] ?? [];

        foreach ($optionalAttrs as $name) {
          unset($entry[$name]);
        }

        if (!is_array($reports)) {
          $reports = [$reports];
        }

        if (!empty($manager)) {
          $entry['rank'] -= array_key_exists($manager,$group);
        }

        if (!empty($reports)) {
          $reportsInGroup = array_reduce(
            $reports,
            function($carry,$item) use($group) {
              return $carry + array_key_exists($item,$group);
            },
            0
          );

          $entry['rank'] += ( $reportsInGroup > 0 );
        }
      });

      usort($result,function(array $a,array $b) {
        if ($a['rank'] != $b['rank']) {
          return $b['rank'] - $a['rank'];
        }

        return strcmp($a['name'],$b['name']);
      });
    }

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

  private static function makeCacheId() : string {
    static $cid;
    if (!isset($cid)) {
      $cid = 'ldap_listing:directory_query:' . \Drupal::languageManager()
           ->getCurrentLanguage()
           ->getId();
    }

    return $cid;
  }
}
