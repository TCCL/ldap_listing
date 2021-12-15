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
use Symfony\Component\Ldap\Exception\LdapException;

class DirectoryQuery {
  const CACHE_TAG = 'ldap_listing_directory_query';
  const MAX_RECURSIVE_DEPTH = 10000;

  private static $ATTRS = [
    'name_attr' => 'name',
    'email_attr' => 'email',
    'title_attr' => 'title',
    'phone_attr' => 'phone',
  ];

  private static $OPTIONAL_ATTRS = [
    'manager_attr' => 'manager',
    'reports_attr' => 'reports',
  ];

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
   * The extra UID attribute to pull for user profile page linking.
   *
   * @var string
   */
  private $uidAttrs = [];

  /**
   * Cached attribute map.
   *
   * @var array
   */
  private $attrMap = [];

  /**
   * Flag indicating whether the query results should include links to user
   * pages.
   *
   * @var bool
   */
  private $linkToUserPage = false;

  /**
   * User mapping manager instance used to map LDAP entries to Drupal users.
   *
   * @var \Drupal\ldap_listing\UserMappingManager
   */
  private $userMappingManager;

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

    // Prepare attribute map.
    foreach (self::$ATTRS as $key => $name) {
      $attr = $this->config->get($key);
      if (empty($attr)) {
        throw new Exception("Attribute '$key' is not configured");
      }
      $this->attrMap[$attr] = $name;
    }
    foreach (self::$OPTIONAL_ATTRS as $key => $name) {
      $attr = $this->config->get($key);
      if (empty($attr)) {
        continue;
      }
      $this->attrMap[$attr] = $name;
    }

    // Pull extra user ID attributes if user profile page linking is enabled.
    if ($this->config->get('link_to_user_page')) {
      $accountNameAttr = $this->ldapServer->getAccountNameAttribute();
      $authAttr = $this->ldapServer->getAuthenticationNameAttribute();

      // Prefer account name over auth name in case they are configured
      // differently.
      if ($accountNameAttr) {
        $this->uidAttrs[] = $accountNameAttr;
      }
      else if ($authAttr) {
        $this->uidAttrs[] = $authAttr;
      }

      $persistUIDAttr = $this->ldapServer->getUniquePersistentAttribute();
      if ($persistUIDAttr) {
        $this->uidAttrs[] = $persistUIDAttr;
      }

      $this->linkToUserPage = true;
      $this->userMappingManager = new UserMappingManager($this->ldapServer);
    }
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
    $groupDN = $section->get('group_dn');
    $filter = sprintf($filterFormat,$groupDN);

    if (empty($baseDN) || empty($filter)) {
      throw new Exception(
        "Cannot query directory info: base_dn/filter not configured"
      );
    }

    // Perform initial query.

    $options['filter'] = array_keys($this->attrMap);
    foreach ($this->uidAttrs as $attr) {
      $options['filter'][] = $attr;
    }

    try {
      $entries = $this->doQuery($baseDN,$filter,$options);

      // Perform recursive queries if configured.

      $depth = $section->get('depth');
      if ($depth < 1) {
        $depth = self::MAX_RECURSIVE_DEPTH;
      }

      $groupBaseDN = $this->config->get('group_base_dn');
      $groupFilterFormat = $this->config->get('group_filter');
      if ($depth > 1 && !empty($groupBaseDN) && !empty($groupFilterFormat)) {
        $subEntries = $this->recurseSubgroups(
          $groupBaseDN,
          $baseDN,
          $groupDN,
          $groupFilterFormat,
          $filterFormat,
          $options,
          $depth
        );

        $entries = array_merge($entries,$subEntries);
      }

      // Format entries; extract and format header/footer information.

      $body = $this->formatUserEntries($entries);
      $header = $section->get('header_entries');
      $footer = $section->get('footer_entries');

      self::padLists($header);
      self::padLists($footer);

      $error = false;

    } catch (LdapException $ex) {
      $body = [];
      $header = [];
      $footer = [];
      $error = true;
    }

    return [
      'id' => $section->get('id'),
      'label' => $section->get('label'),
      'abbrev' => $section->get('abbrev'),
      'error' => $error,
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
    $entries = $this->ldapBridge
             ->get()
             ->query($baseDN,$filter,$options)
             ->execute()
             ->toArray();

    return $entries;
  }

  private function recurseSubgroups(
    string $groupBaseDN,
    string $userBaseDN,
    string $groupDN,
    string $groupFilterFormat,
    string $userFilterFormat,
    array $userOptions,
    int $maxDepth) : array
  {
    $entries = [];

    $depth = 1;
    $stk = [$groupDN];

    while ($depth < $maxDepth && !empty($stk)) {
      // Query subgroups.
      $groupFilter = sprintf($groupFilterFormat,array_pop($stk));
      $subgroups = $this->doQuery($groupBaseDN,$groupFilter);
      if (empty($subgroups)) {
        break;
      }

      foreach ($subgroups as $entry) {
        $dn = $entry->getDn();
        $userFilter = sprintf($userFilterFormat,ldap_escape($dn,'',LDAP_ESCAPE_FILTER));
        $next = $this->doQuery($userBaseDN,$userFilter,$userOptions);
        $entries = array_merge($entries,$next);
        array_push($stk,$dn);
      }

      $depth += 1;
    }

    return $entries;
  }

  private function formatUserEntries(array $entries) {
    $group = [];

    $result = array_map(
      function(Entry $entry) use(&$group) {
        $attributes = [];
        foreach ($entry->getAttributes() as $name => $values) {
          if (!isset($this->attrMap[$name])) {
            continue;
          }

          if (count($values) == 1) {
            $attributes[$this->attrMap[$name]] = $values[0];
          }
          else {
            $attributes[$this->attrMap[$name]] = $values;
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

        // Process uid attributes in order to link to user profile page.
        $userPageLink = false;
        if ($this->linkToUserPage) {
          $account = $this->userMappingManager->mapUserFromLdapEntry($entry);
          if ($account) {
            $userPageLink = $account->toUrl()->toString();
          }
        }

        return [
          'dn' => $dn,
          'emailLink' => $emailLink,
          'userPageLink' => $userPageLink,
          'rank' => 0,

        ] + $attributes;
      },
      $entries
    );

    $keep = array_keys(array_unique(array_column($result,'dn')));
    $result = array_intersect_key($result,$keep);

    array_walk($result,function(array &$entry) use($group) {
      $manager = $entry['manager'] ?? null;
      $reports = $entry['reports'] ?? [];

      foreach (self::$OPTIONAL_ATTRS as $name) {
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
