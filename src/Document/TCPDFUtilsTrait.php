<?php

/**
 * TCPDFUtilsTrait.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Document;

/**
 * Provides extra utility functionality to TCPDF subclass.
 */
trait TCPDFUtilsTrait {
  private $fontHash = ':8.0:helvetica';
  private $fontStack = [];

  private function drawText($text,$align = 'L',$rightMargin = 0.1,$border = 0) {
    list($style,$size,$family) = $this->parseFontHash();

    $wd = $this->GetStringWidth($text,'',$style);
    $this->drawTextHavingWidth($text,$wd,$align,$rightMargin,$border);

    return $wd + $rightMargin;
  }

  private function drawTextHavingWidth($text,$width,$align = 'L',$rightMargin = 0.1,$border = 0) {
    $this->Cell($width,0,$text,$border,0,$align,false,'',0,false,'T','C');
    $this->SetX($this->GetX() + $rightMargin);
  }

  private function drawTextHavingDims($text,$width,$height,$align = 'L',$rightMargin = 0.1,$border = 0) {
    $this->Cell($width,$height,$text,$border,0,$align,false,'',0,false,'T','C');
    $this->SetX($this->GetX() + $rightMargin);
  }

  private function drawTextRemainingWidth($text,$align = 'L',$rightMargin = 0.0,$border = 0) {
    $margins = $this->getMargins();
    $wd = $this->getPageWidth() - $this->GetX() - $margins['right'];
    $this->drawTextHavingWidth($text,$wd,$align,$rightMargin,$border);
    return $wd + $rightMargin;
  }

  private function drawBlank($length,$ln = 0) {
    if (isset($this->fontHash)) {
      list($_,$size,$_) = $this->parseFontHash();
    }
    else {
      $size = 8.0;
    }
    $this->pushFont('B',$size + 0.1);
    $this->Cell($length,0,'','B',$ln);
    $this->popFont();
  }

  private function drawBlankWithText($text,$length = null,$align = 'C',$ln = 0) {
    list($style,$size,$family) = $this->parseFontHash();

    if (!isset($length)) {
      $length = $this->GetStringWidth($text,'',$style);
    }
    $x = $this->GetX();
    $this->Cell($length,0,$text,'',0,$align);
    $this->SetX($x);
    if (isset($this->fontHash)) {
      list($_,$size,$_) = $this->parseFontHash();
    }
    else {
      $size = 8.0;
    }
    $this->pushFont('B',$size + 0.1);
    $this->Cell($length,0,'','B',$ln,$align);
    $this->popFont();
    return $length;
  }

  private function fitText($text,$length) {
    list($style,$size,$family) = $this->parseFontHash();

    $length -= 0.1;
    $cand = $text;
    $nrm = 0;
    while ($nrm < strlen($text)-1) {
      $x = $this->GetStringWidth($cand,'',$style);
      if ($x <= $length) {
        break;
      }
      $nrm += 1;

      $cand = substr($text,0,strlen($text)-$nrm) . '...';
    }

    return $cand;
  }

  private function seekX($amt) {
    $this->SetX($this->GetX() + $amt,true);
  }

  private function seekY($amt) {
    $this->SetY($this->GetY() + $amt,true);
  }

  private function seekXAbs($amt) {
    $this->SetAbsX($this->GetX() + $amt,true);
  }

  private function seekYAbs($amt) {
    $y = $this->GetY();
    $this->SetY($y,true); // reset X
    $this->SetAbsY($y + $amt);
  }

  private function calculateWidthsToFit(&$widths,array $text,$availWidth,$rightMargin = 0.1) {
    $widths = [];
    foreach ($text as $key => $item) {
      $widths[$key] = $this->GetStringWidth($item,'','');
      $availWidth -= $widths[$key];
    }
    $availWidth -= $rightMargin * count($text);
    return $availWidth;
  }

  private function setFontEx($style = '',$size = 8.0,$family = 'helvetica') {
    $hash = "$style:$size:$family";
    if ($hash != $this->fontHash) {
      $this->SetFont($family,$style,$size);
      $this->fontHash = $hash;
    }
  }

  private function parseFontHash($fontHash = null) {
    $hash = $fontHash ?? $this->fontHash;
    return explode(':',$hash);
  }

  private function pushFont(...$args) {
    array_push($this->fontStack,$this->fontHash);
    $this->setFontEx(...$args);
  }

  private function popFont() {
    $next = array_pop($this->fontStack);
    if (isset($next)) {
      $args = $this->parseFontHash($next);
      $this->setFontEx(...$args);
    }
  }
}
