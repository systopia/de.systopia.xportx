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
class CRM_Xportx_Exporter_CSV extends CRM_Xportx_Exporter {

  /**
   * get the mime type created by this exporter
   */
  public function getMimeType() {
    return 'text/csv';
  }

  /**
   * get the proposed file name
   */
  public function getFileName() {
    if (empty($this->config['file_name'])) {
      return 'export.csv';
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
    $delimiter = $this->getDelimiter();

    // compile header + write
    $fields = $this->export->getFieldList();
    $headers = array();
    foreach ($fields as $field) {
      $headers[] = $field['label'];
    }
    $this->encodeRow($headers);
    fputcsv($handle, $headers, $delimiter);

    // now run through the fields
    while ($data->fetch()) {
      $row = array();
      foreach ($fields as $field) {
        $row[] = $this->getFieldValue($data, $field);
      }
      $this->encodeRow($row);
      fputcsv($handle, $row, $delimiter);
    }
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
