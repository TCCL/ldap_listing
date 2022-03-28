<?php

/**
 * DirectoryPdfInterface.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Document;

interface DirectoryPdfInterface {
  /**
   * Gets the file name to use for the generated document.
   *
   * @return string
   */
  public function getFileName() : string;

  /**
   * Generates the PDF document (but does not output it).
   */
  public function generate() : void;

  /**
   * Outputs the PDF file.
   *
   * @param string $file
   *  Optional file to which output is streamed. If omitted, then the file is
   *  streamed to standard output (i.e. the request body stream).
   */
  public function output(string $file = '') : void;
}
