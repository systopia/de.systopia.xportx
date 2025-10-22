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
abstract class CRM_Xportx_Exporter
{

    /** @var CRM_Xportx_Export */
    protected $export;
    protected $config;
    protected $tmp_store;
    protected $_filter_cache;


    public function init($config, $export)
    {
        $this->export        = $export;
        $this->config        = $config;
        $this->tmp_store     = [];
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
    public function getFieldValue($record, $field)
    {
        return $this->export->getFieldValue($record, $field);
    }

    /**
     * Internal function to return the final value, after filters etc.
     */
    protected function getExportFieldValue($record, $field)
    {
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

                case 'format_datetime':    // format a time/date value with a date string
                    $time_value = strtotime($value);
                    if ($time_value) {
                        // format with date()
                        $format = $filter['format'] ?? NULL;
                        if ($format) {
                            $value = date($format, $time_value);
                        }
                    } else {
                        $value = ''; // no time entry

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
                        $value = null;
                    }
                    break;

                case 'resolve_option_value':  // replace option values with their respective labels
                    if (!empty($filter['option_group_id'])) {
                        // a different source is defined
                        $value = $this->resolveOptionValue($value, $filter['option_group_id']);
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
     * Run the row filters (if any) to determine, whether
     *  this row should be kept for exporting
     *
     * @param $row_data
     * @return boolean TRUE if it should be exported
     */
    protected function exportRow($row_data)
    {
        $row_filters = CRM_Utils_Array::value("row_filters", $this->config, []);
        foreach ($row_filters as $row_filter) {
            switch ($row_filter['type']) {
                case 'unique_row':
                    // build row key
                    $row_keys_elements = [];
                    $column_list       = (is_array($row_filter['columns'])) ? $row_filter['columns'] : array_keys(
                        $row_data
                    );
                    foreach ($column_list as $column_name) {
                        $row_keys_elements[] = CRM_Utils_Array::value($column_name, $row_data, '');
                    }
                    $row_key = implode("\x01", $row_keys_elements);

                    // check if this key has been used before
                    $known_row_keys = $this->getTempValue('rf_unique_row_keys', []);
                    if (isset($known_row_keys[$row_key])) {
                        // this is a duplicate row (judged by the listed columns)
                        return false;
                    } else {
                        // this is NOT a duplicate, store key and move on
                        $known_row_keys[$row_key] = true;
                        $this->setTempValue('rf_unique_row_keys', $known_row_keys);
                    }
                    break;

                default:
                    throw new Exception("XPortX: Unknown row filter type '{$row_filter['type']}'");
            }
        }
        return true;
    }

    /**
     * Get the list of filters that should be applied to the given field
     * @param $field array field spec
     * @return array list of filter objects
     */
    protected function getFiltersForField($field)
    {
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
     * @param $value  mixed value
     */
    public function setTempValue($name, $value)
    {
        $this->tmp_store[$name] = $value;
    }

    /**
     * Temporary values can be used to pass data within one row.
     *  A field value gets automatically stored to the temp values,
     *  if the "tmp_store" property is set in the field
     *
     * @param $name        string tmp name
     * @param $default     mixed  default value if not set yet
     * @return mixed|null the value
     */
    public function getTempValue($name, $default = null)
    {
        return CRM_Utils_Array::value($name, $this->tmp_store, $default);
    }

    /**
     * Temporary values can be used to pass data within one row.
     *  A field value gets automatically stored to the temp values,
     *  if the "tmp_store" property is set in the field
     */
    public function resetTmpStore()
    {
        $this->tmp_store = [];
    }

    /**
     * Resolve a option value to the respective labels
     *
     * @param string $value
     *      string of option value (key) - potentially packed
     *
     * @param string $option_group_id
     *      option group name or ID
     *
     * @param string $glue
     *      separator for multiple values, default is ', '
     *
     * @return string
     *      values resolved to labels
     */
    protected function resolveOptionValue($value, $option_group_id, $glue = ', ')
    {
        // unpack value
        $values = CRM_Utils_Array::explodePadded($value);
        if (empty($values)) {
            return '';
        }

        // get option group map (cached)
        static $option_groups = [];
        if (!isset($option_groups[$option_group_id])) {
            // load option group
            $option_values = [];
            $query         = civicrm_api3(
                'OptionValue',
                'get',
                [
                    'option_group_id' => $option_group_id,
                    'return'          => 'value,label',
                    'option.limit'    => 0,
                ]
            );
            foreach ($query['values'] as $option_value) {
                $option_values[$option_value['value']] = $option_value['label'];
            }
            $option_groups[$option_group_id] = $option_values;
        }
        $key_map = $option_groups[$option_group_id];

        // map values
        $resolved_values = [];
        foreach ($values as $value) {
            $resolved_values[] = CRM_Utils_Array::value($value, $key_map, $value);
        }

        return implode($glue, $resolved_values);
    }
}
