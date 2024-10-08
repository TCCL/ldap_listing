<?php

/**
 * DirectoryPage.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Controller;

use Drupal\Core\Url;
use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ldap_listing\DirectoryQuery;
use Drupal\ldap_listing\Exception;
use Drupal\ldap_listing\Form\SettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DirectoryPage extends ControllerBase {
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
      $flag = $this->query->setExcludeFromDirectory(true);
      $sections = $this->query->queryAllCached($time);
      $this->query->setExcludeFromDirectory($flag);

    } catch (Exception) {
      throw new NotFoundHttpException;
    }

    if (empty($sections)) {
      throw new NotFoundHttpException;
    }

    $preambleMessageLines = [];
    $preambleRaw = $this->config->get('preamble');
    $preambleLinesRaw = preg_split('/\r\n|\r|\n/',$preambleRaw);
    foreach (array_filter($preambleLinesRaw) as $line) {
      $preambleMessageLines[] = new HtmlEscapedText($line);
    }

    $pdfEnabled = DirectoryPdfPage::enabled();
    $pdf = [
      'enabled' => $pdfEnabled,
      'action' => null,
    ];
    if ($pdfEnabled) {
      $url = Url::fromRoute('ldap_listing.directory_page_pdf');
      $pdf['action'] = $url->toString();
    }

    $render = [
      '#theme' => 'ldap_listing_directory_listing',
      '#sections' => $sections,
      '#manifest' => self::createManifestFromSections($sections),
      '#preamble_message_lines' => $preambleMessageLines,
      '#last_generated_message' => (
        date('F jS \a\t g:i A',$time)
      ),
      '#pdf' => $pdf,
      '#attached' => [
        'library' => ['ldap_listing/directory-listing'],
      ],
      '#cache' => [
        'tags' => [
          DirectoryQuery::CACHE_TAG,
        ],
      ],
    ];

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
          'e' => $entry['email'] ?? null,
          'l' => $entry['userPageLink'] ?? null,
        ];
      }
    }

    return $manifest;
  }
}
