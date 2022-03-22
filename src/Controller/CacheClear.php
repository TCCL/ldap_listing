<?php

/**
 * CacheClear.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\PathValidator;
use Drupal\ldap_listing\DirectoryQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class CacheClear extends ControllerBase {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('path.validator'));
  }

  /**
   * The Path Validator service.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  private $pathValidator;

  /**
   * Creates a new CacheClear instance.
   *
   * @param \Drupal\Core\Path\PathValidator $pathValidator
   *  The Path Validator service.
   */
  public function __construct(PathValidator $pathValidator) {
    $this->pathValidator = $pathValidator;
  }

  /**
   * Clears the DirectoryQuery cache.
   */
  public function clearCache(Request $request) {
    Cache::invalidateTags([DirectoryQuery::CACHE_TAG]);

    $this->messenger()->addMessage($this->t('The directory query cache has been invalidated.'));

    $route = 'ldap_listing.settings_page';
    $referer = $request->headers->get('referer');
    if ($referer) {
      $baseUrl = Request::createFromGlobals()->getSchemeAndHttpHost();
      if (substr($referer,0,strlen($baseUrl)) == $baseUrl) {
        $path = substr($referer,strlen($baseUrl));
        $url = $this->pathValidator->getUrlIfValid($path);
        if ($url) {
          $route = $url->getRouteName();
        }
      }
    }

    return $this->redirect($route);
  }
}
