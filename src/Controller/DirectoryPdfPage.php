<?php

/**
 * DirectoryPdfPage.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ldap_listing\Document\DirectoryPdf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DirectoryPdfPage extends ControllerBase {
  const DEFAULT_CLASS = '\Drupal\ldap_listing\Document\DirectoryPdf';

  /**
   * Determines if the directory PDF can be generated.
   *
   * @return bool
   */
  public static function enabled() : bool {
    $interface = '\Drupal\ldap_listing\Document\DirectoryPdfInterface';
    $config = \Drupal::config('ldap_listing.settings');

    if ($config->get('pdf_enabled')) {
      $class = $config->get('pdf_class') ?? self::DEFAULT_CLASS;

      // Check TCPDF for default class to avoid error.
      if ($class == self::DEFAULT_CLASS) {
        if (!class_exists('TCPDF')) {
          return false;
        }
      }

      return class_exists($class) && is_subclass_of($class,$interface);
    }

    return false;
  }

  /**
   * Generates the Directory PDF or throws 404 if it cannot be generated.
   */
  public function getContent(Request $request) {
    // Make sure the base project has installed TCPDF.
    if (!self::enabled()) {
      throw new NotFoundHttpException;
    }

    // Create PDF to generate and output via streamed response.
    $doc = new DirectoryPdf;
    $generateFunc = function() use($doc) {
      $doc->generate();
      $doc->output();
    };

    // Create response to stream PDF generation as a file download.

    $response = new StreamedResponse;

    $fileName = $doc->getFileName();
    $dispos = "attachment; filename=\"$fileName\"";

    $response->headers->set('Content-Disposition',$dispos);
    $response->headers->set('Content-Type','application/pdf');

    $response->setCallback($generateFunc);

    return $response;
  }
}
