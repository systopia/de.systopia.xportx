<?php
/*-------------------------------------------------------+
| SYSTOPIA EXTENSIBLE EXPORT EXTENSION                   |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Xportx_ExtensionUtil as E;

/**
 * Base class for all exporters
 */
class CRM_Xportx_Exporter_PDF extends CRM_Xportx_Exporter {

  /**
   * get the mime type created by this exporter
   */
  public function getMimeType() {
    return 'application/pdf';
  }

  /**
   * get the proposed file name
   */
  public function getFileName() {
    if (empty($this->config['file_name'])) {
      return 'export.pdf';
    }

    // use file name
    $file_name = $this->config['file_name'];

    // replace tokens
    $file_name = preg_replace("#\{date\}#", date('YmdHis'), $file_name);

    return $file_name;
  }

  /**
   * Write the data DAO to the given file
   */
  public function writeToFile($data, $file_name) {
    $handle = fopen($file_name, 'w');
    $fields = $this->export->getFieldList();

    // collect all records
    $records = array();
    while ($data->fetch()) {
      $record = array();
      foreach ($fields as $field) {
        $record[$field['label']] = $this->getExportFieldValue($data, $field);
      }
      $records[] = $record;
    }

    // get template file URL
    $template_path = CRM_Xportx_Export::getXportxResource($this->config['smarty_template']);
    CRM_Core_Error::debug_log_message("Template is $template_path");

    // create PDF
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('records', $records);
    $smarty->assign('first_record', isset($records[0]) ? $records[0] : array());
    // add params from config?
//    $html_data = $smarty->fetch($this->config['smarty_template']);
    $html_data = $smarty->fetch($template_path);

    if (empty($this->config['enforce_dompdf'])) {
      // use default PDF engine
      $pdf_data  = CRM_Utils_PDF_Utils::html2pdf($html_data, $this->getFileName(),TRUE, $this->config['format_id']);
    } else {
      // enfoce DOMPDF
      $pdf_data  = self::dompdf_html2pdf($html_data, $this->getFileName(),TRUE, $this->config['format_id']);
    }

    // write to stream
    fputs($handle, $pdf_data);
  }

  /**
   * encode values
   */
  protected function encodeRow(&$row) {
    if (!empty($this->config['encoding'])) {
      $encoding = $this->config['encoding'];
      foreach ($row as &$value) {
        $value = mb_convert_encoding($value, $encoding);
      }
    }
  }

  /**
   * get the delimiter
   */
  protected function getDelimiter() {
    if (empty($this->config['delimiter'])) {
      return ',';
    } else {
      return $this->config['delimiter'];
    }
  }

  /**
   * Copied/abridged from CRM_Utils_PDF_Utils::html2pdf to allow enforcing DOMPDF,
   * bypassing $config->wkhtmltopdfPath
   *
   * @param array $text
   *   List of HTML snippets.
   * @param string $fileName
   *   The logical filename to display.
   *   Ex: "HelloWorld.pdf".
   * @param bool $output
   *   FALSE to display PDF. TRUE to return as string.
   * @param null $pdfFormat
   *   Unclear. Possibly PdfFormat or formValues.
   *
   * @return string
   */
  protected static function dompdf_html2pdf(&$text, $fileName = 'civicrm.pdf', $output = FALSE, $pdfFormat = NULL) {
    $pages = array($text);

    // Get PDF Page Format
    $format = CRM_Core_BAO_PdfFormat::getById($pdfFormat);
    CRM_Core_Error::debug_log_message("DOMPDF Format: " . json_encode($format));

    $paperSize = CRM_Core_BAO_PaperSize::getByName($format['paper_size']);
    $paper_width = CRM_Utils_PDF_Utils::convertMetric($paperSize['width'], $paperSize['metric'], 'pt');
    $paper_height = CRM_Utils_PDF_Utils::convertMetric($paperSize['height'], $paperSize['metric'], 'pt');
    // dompdf requires dimensions in points
    $paper_size = array(0, 0, $paper_width, $paper_height);
    $orientation = CRM_Core_BAO_PdfFormat::getValue('orientation', $format);
    $metric = CRM_Core_BAO_PdfFormat::getValue('metric', $format);
    $t = CRM_Core_BAO_PdfFormat::getValue('margin_top', $format);
    $r = CRM_Core_BAO_PdfFormat::getValue('margin_right', $format);
    $b = CRM_Core_BAO_PdfFormat::getValue('margin_bottom', $format);
    $l = CRM_Core_BAO_PdfFormat::getValue('margin_left', $format);

    $stationery_path_partial = CRM_Core_BAO_PdfFormat::getValue('stationery', $format);

    $stationery_path = NULL;
    if (strlen($stationery_path_partial)) {
      $doc_root = $_SERVER['DOCUMENT_ROOT'];
      $stationery_path = $doc_root . "/" . $stationery_path_partial;
    }

    $margins = array($metric, $t, $r, $b, $l);

    $config = CRM_Core_Config::singleton();

    // Add a special region for the HTML header of PDF files:
    $pdfHeaderRegion = CRM_Core_Region::instance('export-document-header', FALSE);
    $htmlHeader = ($pdfHeaderRegion) ? $pdfHeaderRegion->render('', FALSE) : '';

    $html = "
<html>
  <head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>
    <style>@page { margin: {$t}{$metric} {$r}{$metric} {$b}{$metric} {$l}{$metric}; }</style>
    <style type=\"text/css\">@import url({$config->userFrameworkResourceURL}css/print.css);</style>
    {$htmlHeader}
  </head>
  <body>
    <div id=\"crm-container\">\n";

    // Strip <html>, <header>, and <body> tags from each page
    $htmlElementstoStrip = array(
        '@<head[^>]*?>.*?</head>@siu',
        '@<script[^>]*?>.*?</script>@siu',
        '@<body>@siu',
        '@</body>@siu',
        '@<html[^>]*?>@siu',
        '@</html>@siu',
        '@<!DOCTYPE[^>]*?>@siu',
    );
    $htmlElementsInstead = array('', '', '', '', '', '');
    foreach ($pages as & $page) {
      $page = preg_replace($htmlElementstoStrip,
          $htmlElementsInstead,
          $page
      );
    }
    // Glue the pages together
    $html .= implode("\n<div style=\"page-break-after: always\"></div>\n", $pages);
    $html .= "
    </div>
  </body>
</html>";


    return CRM_Utils_PDF_Utils::_html2pdf_dompdf($paper_size, $orientation, $html, $output, $fileName);
  }
}
