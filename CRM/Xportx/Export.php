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
 * Represents one Export process
 */
class CRM_Xportx_Export {

  protected $configuration;
  protected $modules;
  protected $exporter;

  /**
   * Create an export object with the given configuration data. Format is:
   * 'configuration'  => {...}
   * 'modules'  => [{
   *   'class'  => 'CRM_Xportx_Module_ContactBase',
   *   'prefix' => '',
   *   'config' => {},
   *  }, {...}],
   * 'exporter' => {
   *   'class'  => 'CRM_Xportx_Exporter_CSV',
   *   'config' => {},
   *  },
   */
  public function __construct($config) {
    // set configuration
    if (!isset($config['configuration']) || !is_array($config['configuration'])) {
      throw new Exception("XPortX: Export configuration has no 'configuration' section.");
    }
    $this->configuration = $config['configuration'];

    // get modules
    if (!isset($config['modules']) || !is_array($config['modules'])) {
      throw new Exception("XPortX: Export configuration has no 'modules' section.");
    }
    $this->modules = array();
    foreach ($config['modules'] as $module_spec) {
      $module = $this->getInstance($module_spec['class'], $module_spec['config']);
      if ($module) {
        $this->modules[] = $module;
      }
    }
    if (empty($this->modules)) {
      throw new Exception("XPortX: No modules selected.");
    }

    // get exporter
    if (!isset($config['exporter']) || !is_array($config['exporter'])) {
      throw new Exception("XPortX: Export configuration has no 'exporter' section.");
    }
    $this->exporter = $this->getInstance($config['exporter']['class'], $config['exporter']['config']);
    if (empty($this->exporter)) {
      throw new Exception("XPortX: No exporter selected.");
    }
  }

  /**
   * This function runs the generated SQL
   */
  public function generateSelectSQL($contact_ids) {
    // collect
    $selects = array();
    $joins   = array();
    $wheres  = array();
    foreach ($this->modules as $module) {
      $module->addJoins($joins);
      $module->addSelects($selects);
      $module->addWheres($wheres);
    }

    // add the contact ID list
    $contact_list = implode(',', $contact_ids);
    $wheres[] = ("contact.id IN ({$contact_list})");

    $sql = 'SELECT ' . implode(', ', $selects);
    $sql .= ' FROM civicrm_contact contact ';
    $sql .= implode(' ', $joins);
    $sql .= ' WHERE (' . implode(') AND (', $wheres) . ')';
    $sql .= ' GROUP BY contact.id;';
    return $sql;
  }

  /**
   * Run the export and write the result to the PHP out stream
   */
  public function writeToStream($contact_ids) {
    // WRITE HTML download header
    header('Content-Type: ' . $this->exporter->getMimeType());
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    $isIE = strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE');
    if ($isIE) {
      header("Content-Disposition: inline; filename=" . $this->exporter->getFileName());
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
    } else {
      header("Content-Disposition: attachment; filename=" . $this->exporter->getFileName());
      header('Pragma: no-cache');
    }

    // get the data
    $sql = $this->generateSelectSQL($contact_ids);
    //CRM_Core_ERROR::debug_log_message($sql);
    $data = CRM_Core_DAO::executeQuery($sql);

    // make the exporter write it to the stream
    $this->exporter->writeToFile($data, "php://output");

    // and end.
    CRM_Utils_System::civiExit();
  }

  /**
   * Get a module/exporter instance
   */
  protected function getInstance($class_name, $configuration) {
    if (class_exists($class_name)) {
      $instance = new $class_name();
      $instance->init($configuration, $this);
      return $instance;
    } else {
      return NULL;
    }
  }

  /**
   * get the unique ID of the module instance in this export context
   * currently implemented as index in the modules list
   */
  public function getModuleID($module) {
    for ($i=0; $i < count($this->modules); $i++) {
      if ($this->modules[$i] === $module) {
        return $i;
      }
    }
    // fallback: use the object pointer
    return (int) $module;
  }

  /**
   * Get a list of all fields.
   *
   * @return array(array('key' => key, 'label' -> 'header'),...)
   */
  public function getFieldList() {
    $field_list = array();

    for ($i=0; $i < count($this->modules); $i++) {
      $module = $this->modules[$i];
      $key_prefix = "module{$i}_";
      $module_fields = $module->getFieldList();
      foreach ($module_fields as $module_field) {
        $module_field['key'] = $key_prefix . $module_field['key'];
        $field_list[] = $module_field;
      }
    }
    return $field_list;
  }

  /**
   * Get the value for the given key form the given record
   */
  public function getFieldValue($record, $field) {
    $key = $field['key'];
    $prefix_length = strpos($key, '_', 6);
    $module_index  = substr($key, 6, $prefix_length - 6);
    if (isset($this->modules[$module_index])) {
      return $this->modules[$module_index]->getFieldValue($record, substr($key, $prefix_length + 1), $field);
    } else {
      return 'ERROR';
    }
  }


  /**
   * Create an export object using a stored configuration
   */
  public static function createByStoredConfig($config_name) {
    // TODO
  }
}
