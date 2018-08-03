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
        $record[$field['label']] = $this->export->getFieldValue($data, $field);
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
    $pdf_data  = CRM_Utils_PDF_Utils::html2pdf($html_data, $this->getFileName(),TRUE, $this->config['format_id']);
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
}
