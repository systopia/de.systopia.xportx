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

  /** @var CRM_Xportx_Export */
  protected $export;
  protected $config;
  protected $tmp_store;
  protected $_filter_cache;


  public function init($config, $export) {
    $this->export = $export;
    $this->config = $config;
    $this->tmp_store = [];
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
    return $this->export->getFieldValue($record, $field);
  }

  /**
   * Internal function to return the final value, after filters etc.
   */
  protected function getExportFieldValue($record, $field) {
    $value = $this->getFieldValue($record, $field);

    // apply filters
    $filters = $this->getFiltersForField($field);
    foreach ($filters as $filter) {
      switch ($filter['type']) {
        case 'preg_replace': // regex replace on the final value
          $value = preg_replace($filter['pattern'], $filter['replacement'], $value);
          break;

        case 'mapping':     // map the final value
          if (array_key_exists($value, $filter['mapping'])) {
            $value = $filter['mapping'][$value];
          }
          break;

        case 'keep_regex':  // clear value, unless it matches a certain regex (even from another field)
          $source = $value;
          if (!empty($filter['source'])) {
            // a different source is defined
            $source = $this->getTempValue($filter['source']);
          }
          if (!preg_match($filter['pattern'], $source)) {
            // doesn't match -> clear value
            $value = NULL;
          }
          break;

        default:
          throw new Exception("XPortX: Unknown filter type '{$filter['type']}'");
      }
    }

    // store to temps if requested
    if (!empty($field['tmp_store'])) {
      $this->setTempValue($field['tmp_store'], $value);
    }

    return $value;
  }

  /**
   * Get the list of filters that should be applied to the given field
   * @param $field array field spec
   * @return array list of filter objects
   */
  protected function getFiltersForField($field) {
    $field_label = $field['label'];
    if (!isset($this->_filter_cache[$field_label])) {
      $filters = [];

      // add filters in the exporter config
      if (!empty($this->config['filters']) && is_array($this->config['filters'])) {
        foreach ($this->config['filters'] as $filter) {
          if ($filter['field_label'] == $field_label || $filter['field_label'] == '*') {
            $filters[] = $filter;
          }
        }
      }

      // add filters in the field spec
      if (!empty($field['filters'])) {
        foreach ($field['filters'] as $filter) {
          $filters[] = $filter;
        }
      }

      $this->_filter_cache[$field_label] = $filters;
    }
    return $this->_filter_cache[$field_label];
  }

  /**
   * Temporary values can be used to pass data within one row.
   *  A field value gets automatically stored to the temp values,
   *  if the "tmp_store" property is set in the field
   *
   * @param $name   string tmp name
   * @param $value  string value
   */
  public function setTempValue($name, $value) {
    $this->tmp_store[$name] = $value;
  }

  /**
   * Temporary values can be used to pass data within one row.
   *  A field value gets automatically stored to the temp values,
   *  if the "tmp_store" property is set in the field
   *
   * @param $name        string tmp name
   * @return string|null the value
   */
  public function getTempValue($name) {
    return CRM_Utils_Array::value($name, $this->tmp_store, NULL);
  }

  /**
   * Temporary values can be used to pass data within one row.
   *  A field value gets automatically stored to the temp values,
   *  if the "tmp_store" property is set in the field
   */
  public function resetTmpStore() {
    $this->tmp_store = [];
  }
}
