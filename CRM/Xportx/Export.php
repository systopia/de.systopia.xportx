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
  protected $entity;
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
    $this->entity = CRM_Utils_Array::value('entity', $config, 'Contact');

    // get modules
    if (!isset($config['modules']) || !is_array($config['modules'])) {
      throw new Exception("XPortX: Export configuration has no 'modules' section.");
    }
    $this->modules = array();
    foreach ($config['modules'] as $module_spec) {
      $module = $this->getInstance($module_spec['class'], $module_spec['config']);
      if ($module) {
        $module_entity = $module->forEntity();
        if (   $module_entity == 'Entity' // i.e. *any* entity
            || $module_entity == $this->entity) {
          // this is o.k., add it
          $this->modules[] = $module;
        } else {
          throw new Exception("XPortX: Incompatible! Module '{$module_spec['class']}' only processes [{$module_entity}], but this exporter is for [{$this->entity}]. Adjust your configuration!");
        }
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
   * Get the alias of the base table,
   *  in most cases this would be 'contact' referring to
   *  the civicrm_contact table. You can be sure that
   *  {base_alias].contact_id exists.
   */
  public function getBaseAlias() {
    return strtolower($this->entity);
  }

  /**
   * Get the base table of the query, i.e.
   * the one that the provided IDs refer to
   * In most cases this would be civicrm_contact
   */
  protected function getBaseTable() {
    // this should work for now:
    return 'civicrm_' . strtolower($this->entity);
  }

  /**
   * Get the SQL expression to be used
   *  to identify the contact ID
   *
   * @return string SQL expression
   */
  public function getContactIdExpression() {
    if ($this->entity == 'Contact') {
      return $this->getBaseAlias() . '.id';
    } else {
      return $this->getBaseAlias() . '.contact_id';
    }
  }

  /**
   * This function runs the generated SQL
   *
   * @param $entity_ids array of entity IDs (most likely contact IDs)
   *
   * @return string a generated SQL query
   */
  public function generateSelectSQL($entity_ids) {
    // collect SQL bits
    $selects = array();
    $joins   = array();
    $wheres  = array();
    foreach ($this->modules as $module) {
      $module->addJoins($joins);
      $module->addSelects($selects);
      $module->addWheres($wheres);
    }

    // get some basic stuff
    $base_alias = $this->getBaseAlias();
    $base_table = $this->getBaseTable();

    // add the contact ID list
    $entity_id_list = implode(',', $entity_ids);
    $wheres[] = ("{$base_alias}.id IN ({$entity_id_list})");

    $sql = 'SELECT ' . implode(', ', $selects);
    $sql .= " FROM {$base_table} {$base_alias} ";
    $sql .= implode(' ', $joins);
    $sql .= ' WHERE (' . implode(') AND (', $wheres) . ')';
    $sql .= " GROUP BY {$base_alias}.id;";
    return $sql;
  }

  /**
   * Run the export and write the result to the PHP out stream
   *
   * @param $entity_ids array of entity IDs (most likely contact IDs)
   */
  public function writeToStream($entity_ids) {
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
    $sql = $this->generateSelectSQL($entity_ids);
    CRM_Core_ERROR::debug_log_message($sql);
    $data = CRM_Core_DAO::executeQuery($sql);

    // make the exporter write it to the stream
    $this->exporter->writeToFile($data, "php://output");

    // and end.
    CRM_Utils_System::civiExit();
  }

  /**
   * Get a module/exporter instance
   *
   * @return CRM_Xportx_Module module instance
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

  /**
   * Get the list of absolute paths where resources can be found
   *
   * @return array absolute folder paths
   */
  public static function getXportxLocations() {
    $locations = array();
    // TODO: find all other paths

    // add the folder in this extension
    $locations[] = __DIR__ . '/../../xportx_configurations';

    // add xportx_configurations in upload dir
    $config = CRM_Core_Config::singleton();
    $locations[] = $config->uploadDir .'xportx_configurations';

    return $locations;
  }

  /**
   * Find an XPortX resource in the known locations
   *
   * @param $path string relative path or file name
   * @return string absolute path to the resource
   */
  public static function getXportxResource($path) {
    $locations = self::getXportxLocations();
    foreach ($locations as $location) {
      $file_path = $location . DIRECTORY_SEPARATOR . $path;
      if (file_exists($file_path)) {
        return $file_path;
      }
    }

    return NULL;
  }

  /**
   * get all currently stored export configurations
   */
  public static function getExportConfigurations($entity = 'Contact') {
    // find all export configurations in folder 'xportx_configurations'
    $configurations = array();

    $locations = self::getXportxLocations();
    foreach ($locations as $folder) {
      $files = scandir($folder);
      foreach ($files as $file) {
        if (preg_match("#[a-z0-9_]+[.]json#", $file)) {
          // this is a json file
          $content = file_get_contents($folder . DIRECTORY_SEPARATOR . $file);
          $config = json_decode($content, TRUE);
          if ($config) {
            // check if the entity match:
            $exporter_entity = CRM_Utils_Array::value('entity', $config, 'Contact');
            if ($exporter_entity == $entity) {
              $configurations[$file] = $config;
            }
          }
        }
      }
    }

    return $configurations;
  }
}
