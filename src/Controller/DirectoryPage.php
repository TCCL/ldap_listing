<?php

/**
 * DirectoryPage.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ldap_listing\DirectoryQuery;
use Drupal\ldap_listing\Exception;
use Drupal\ldap_listing\Form\SettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DirectoryPage extends ControllerBase {
  const CACHE_TAG = 'LDAP_LISTING_DIRECTORY_PAGE_CACHE';

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
      $info = $this->query->queryAll();

    } catch (Exception $ex) {
      throw new NotFoundHttpException;
    }

    $render = [
      '#theme' => 'ldap_listing_directory_listing',
      '#index' => $info,
      '#attached' => [
        'library' => ['ldap_listing/directory-listing'],
      ],
      '#cache' => [
        'tags' => [
          self::CACHE_TAG,
        ],
      ],
    ];

    return $render;
  }

  public function getTitle() {
    return $this->config->get('title');
  }
}
