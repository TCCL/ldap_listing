<?php

/**
 * DirectoryPage.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ldap_listing\DirectoryQuery;
use Drupal\ldap_listing\Exception;
use Drupal\ldap_listing\Form\SettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DirectoryPage extends ControllerBase {
  const CACHE_TAG = 'LDAP_LISTING_DIRECTORY_PAGE_CACHE';

  /**
   * Invalidates the directory page render array cache tag if the invalidation
   * period has elapsed.
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
      Cache::invalidateTags([DirectoryPage::CACHE_TAG]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('ldap_listing.query'));
  }

  /**
   * The query service used to fetch directory listing from LDAP.
   *
   * @var \Drupal\ldap_listing\DirectoryQuery
   */
  private $query;

  /**
   * The ldap_listing global settings object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Creates a new DirectoryPage instance.
   *
   * @param \Drupal\ldap_listing\DirectoryQuery $query
   */
  public function __construct(DirectoryQuery $query) {
    $this->query = $query;
    $this->config = \Drupal::config(SettingsForm::CONFIG_OBJECT);
  }

  /**
   * Generates the directory listing page.
   */
  public function getContent() {
    if (!$this->query->hasLDAPServerConfigured()) {
      throw new NotFoundHttpException;
    }

    try {
      $this->query->bind();
      $sections = $this->query->queryAll();

    } catch (Exception $ex) {
      throw new NotFoundHttpException;
    }

    $render = [
      '#theme' => 'ldap_listing_directory_listing',
      '#index' => [
        'sections' => $sections,
        'manifest' => self::createManifestFromSections($sections),
        'lastGeneratedMessage' => (
          date('F dS \a\t g:i A')
        )
      ],
      '#attached' => [
        'library' => ['ldap_listing/directory-listing'],
      ],
      '#cache' => [
        'tags' => [
          self::CACHE_TAG,
        ],
      ],
    ];

    $state = \Drupal::state();
    $state->set('ldap_listing_last_cache_invalidate',time());

    return $render;
  }

  public function getTitle() {
    return $this->config->get('title');
  }

  private static function createManifestFromSections(array $sections) : array {
    $manifest = [];
    foreach ($sections as $section) {
      foreach ($section['body'] as $entry) {
        $manifest[] = [
          'n' => $entry['name'] ?? null,
          'd' => $section['label'],
          'j' => $entry['title'] ?? null,
          'p' => $entry['phone'] ?? null,
        ];
      }
    }

    return $manifest;
  }
}
