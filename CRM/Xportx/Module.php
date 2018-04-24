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
 * Base class for all data modules
 */
abstract class CRM_Xportx_Module {

  protected $config;
  protected $export;

  public function init($config, $export) {
    $this->export = $export;
    $this->config = $config;
  }

  /**
   * Get this module's preferred alias.
   * Must be all lowercase chars: [a-z]+
   */
  public abstract function getPreferredAlias();

  /**
   * Get a list of all fields.
   *
   * @return array(array('key' => key, 'label' -> 'header'),...)
   */
  public function getFieldList() {
    // override if necessary
    return $this->config['fields'];
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    // override if needed
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // override if needed
  }

  /**
   * add this module's where clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addWheres(&$wheres) {
    // override if needed
  }

  /**
   * Get a numeric of this instance
   * (within its export context)
   */
  public function getID() {
    return $this->export->getModuleID($this);
  }

  /**
   * Get a uniqe alias for the given (internal) name
   * $name must be all lowercase chars: [a-z]+
   */
  protected function getAlias($name) {
    return $this->getPreferredAlias() . $this->getID() . '_' . $name;
  }

  /**
   * Get a uniqe alias for the given (internal) name
   * $name must be all lowercase chars: [a-z]+
   */
  protected function getValuePrefix() {
    return $this->getAlias('val_');
  }



  /**
   * Get the value for the given key form the given record
   */
  public function getFieldValue($record, $key) {
    // generic function, override for custom functionality
    $value_key = $this->getValuePrefix() . $key;
    if (property_exists($record, $value_key)) {
      return $record->$value_key;
    } else {
      return 'ERROR';
    }
  }


  /**
   * helper function to generate join of OptionValues
   *
   * @param $option_group_name   the name of the option group
   * @param $option_value_source SQL term containing the value
   * @param $alias               alias to be used for the civicrm_option_value
   */
  protected function generateOptionValueJoin($option_group_name, $option_value_source, $alias) {
    // get group id (TODO: cache?)
    $group_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name = %1",
      array(1 => array($option_group_name, 'String')));
    if (empty($group_id)) {
      throw new Exception(E::ts("Unknown option group '%1'!", array(1 => $option_group_name)));
    }
    return "LEFT JOIN civicrm_option_value {$alias} ON {$alias}.value = {$option_value_source} AND option_group_id = {$group_id}";
  }
}
