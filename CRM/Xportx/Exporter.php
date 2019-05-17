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
abstract class CRM_Xportx_Exporter {

  protected $config;
  protected $export;
  protected $_filter_cache;

  public function init($config, $export) {
    $this->export = $export;
    $this->config = $config;
    $this->_filter_cache = [];
  }

  /**
   * get the mime type created by this exporter
   */
  abstract public function getMimeType();

  /**
   * get the proposed file name
   */
  abstract public function getFileName();

  /**
   * write all data to the given file
   */
  abstract public function writeToFile($data, $file_name);

  /**
   * Get the value for the given key form the given record,
   *
   * applying any filters specified in the export configuration
   */
  public function getFieldValue($record, $field) {
    $value = $this->export->getFieldValue($record, $field);

    // apply filters
    $filters = $this->getFiltersForField($field['label']);
    foreach ($filters as $filter) {
      switch ($filter['type']) {
        case 'preg_replace':
          $value = preg_replace($filter['pattern'], $filter['replacement'], $value);
          break;

        case 'mapping':
          if (array_key_exists($value, $filter['mapping'])) {
            $value = $filter['mapping'][$value];
          }
          break;

        default:
          throw new Exception("XPortX: Unknown filter type '{$filter['type']}'");
      }
    }

    return $value;
  }

  /**
   * Get the list of filters that should be applied to the given field
   * @param $field_label string field label
   * @return array list of filter objects
   */
  protected function getFiltersForField($field_label) {
    if (!isset($this->_filter_cache[$field_label])) {
      $filters = [];
      if (!empty($this->config['filters']) && is_array($this->config['filters'])) {
        foreach ($this->config['filters'] as $filter) {
          if ($filter['field_label'] == $field_label || $filter['field_label'] == '*') {
            $filters[] = $filter;
          }
        }
      }
      $this->_filter_cache[$field_label] = $filters;
    }
    return $this->_filter_cache[$field_label];
  }
}
