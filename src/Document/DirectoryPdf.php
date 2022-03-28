<?php

/**
 * DirectoryPdf.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Document;

use DateTime;
use TCPDF;

class DirectoryPdf extends TCPDF implements DirectoryPdfInterface {
  const PORTRAIT = 'P';
  const LANDSCAPE = 'L';

  /**
   * The timestamp denoting when the instance was created.
   *
   * @var DateTime
   */
  private $directoryPdfTs;

  /**
   * Creates a new DirectoryPdf instance.
   *
   * @param string $orient
   *  Denotes the orientation of the document. Default is portrait.
   */
  public function __construct(string $orient = self::PORTRAIT) {
    parent::__construct($orient,'in','USLETTER',true,'UTF-8');
    $this->directoryPdfTs = new DateTime;
  }

  /**
   * Implements DirectoryPdfInterface::getFileName().
   */
  public function getFileName() : string {
    $ts = $this->directoryPdfTs->format('Ymd');
    $name = "Directory-$ts.pdf";

    return $name;
  }

  /**
   * Implements DirectoryPdfInterface::generate().
   */
  public function generate() : void {

  }

  /**
   * Implements DirectoryPdfInterface::output().
   */
  public function output(string $file = '') : void {
    if (!empty($file)) {
      $this->Output($file,'F');
    }
    else {
      $this->Output($this->getFileName());
    }
  }

  /**
   * Overrides TCPDF::Header().
   */
  public function Header() {

  }

  /**
   * Overrides TCPDF::Footer().
   */
  public function Footer() {

  }
}
