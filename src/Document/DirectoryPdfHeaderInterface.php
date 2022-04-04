<?php

/**
 * DirectoryPdfHeaderInterface.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Document;

/**
 * Provides an interface to customize the header region of the directory PDF
 * generation.
 */
interface DirectoryPdfHeaderInterface {
  /**
   * Provides the path to an image file to use in the PDF header.
   *
   * @param string $file
   *  The path to the image resource in the file system.
   */
  public function setHeaderImage(string $file) : void;

  /**
   * Provides the title text for the PDF header.
   *
   * @param string $title
   *  The title text.
   */
  public function setHeaderTitle(string $title) : void;
}
