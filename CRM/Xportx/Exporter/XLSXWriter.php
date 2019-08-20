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

include_once 'vendor/autoload.php';


/**
 * XLSX Exporter
 *
 * You need to run the composer for this to work.
 *
 * config options:
 *  sheet_name    name of the xls sheet
 *  column_types  mapping column_label => type (e.g. 'string', 'integer', see link below)
 *  file_name     preferred file name
 *
 * @see https://github.com/mk-j/PHP_XLSXWriter
 */
class CRM_Xportx_Exporter_XLSXWriter extends CRM_Xportx_Exporter {

  /**
   * get the mime type created by this exporter
   */
  public function getMimeType() {
    return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  }

  /**
   * get the proposed file name
   */
  public function getFileName() {
    if (empty($this->config['file_name'])) {
      return 'export.xlsx';
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
    $writer = new XLSXWriter();

    // compile header + write
    $sheet_name = CRM_Utils_Array::value('sheet_name', $this->config, 'Sheet1');
    $fields = $this->export->getFieldList();
    $headers = array();
    foreach ($fields as $field) {
      $field_type = 'string';
      if (!empty($this->config['column_types'][$field['label']])) {
        $field_type = $this->config['column_types'][$field['label']];
      }
      $headers[$field['label']] = $field_type;
    }
    $writer->writeSheetHeader($sheet_name, $headers);

    // now run through the fields
    while ($data->fetch()) {
      $row = array();
      foreach ($fields as $field) {
        $row[] = $this->getExportFieldValue($data, $field);
      }
      $writer->writeSheetRow($sheet_name, $row);
    }

    // write to temporary file
    $tmpfile = tempnam(sys_get_temp_dir(), 'xportx_xlsx_');
    $writer->writeToFile($tmpfile);

    // copy to final file
    $raw_data = file_get_contents($tmpfile);
    file_put_contents($file_name, $raw_data);
  }
}
