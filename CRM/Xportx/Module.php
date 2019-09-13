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
  /** @var CRM_Xportx_Export */
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
   * add this module's order by statements
   * by default, they're taken from the configuration field 'order_by'
   */
  public function addOrderBys(&$order_bys) {
    if (!empty($this->config['order_by']) && is_array($this->config['order_by'])) {
      foreach ($this->config['order_by'] as $order_by_spec) {
        $alias = $this->getAlias($order_by_spec['alias']);
        $order_bys[] = "{$alias}.{$order_by_spec['field']} {$order_by_spec['sort']}";
      }
    }
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
   * Get the alias of the base table,
   *  in most cases this would be 'contact' referring to
   *  the civicrm_contact table. You can be sure that
   *  {base_alias].contact_id exists.
   */
  public function getBaseAlias() {
    return $this->export->getBaseAlias();
  }


  /**
   * Get the SQL expression to be used
   *  to identify the contact ID
   *
   * @return string SQL expression
   */
  public function getContactIdExpression() {
    return $this->export->getContactIdExpression();
  }

  /**
   * Get a uniqe alias for the given (internal) name
   * $name must be all lowercase chars: [a-z]+
   */
  protected function getValuePrefix() {
    return $this->getAlias('val_');
  }

  /**
   * Get the entity this module can deal with
   * Default is 'Contact'
   * Return 'Entity" for all entities with a contact_id field
   */
  public function forEntity() {
    return 'Contact';
  }


  /**
   * Get the value for the given key form the given record
   */
  public function getFieldValue($record, $key, $field) {
    // generic function, override for custom functionality
    $value_key = $this->getValuePrefix() . $key;
    if (property_exists($record, $value_key)) {
      // get value
      $value = $record->$value_key;

      // translate the value
      if (!empty($field['ts'])) {
        $params = CRM_Utils_Array::value('ts_params', $field, array());
        $value = ts($value, $params);
      }
      return $value;
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

  /********************************************************
   *                 CUSTOM FIELD SUPPORT                 *
   *******************************************************/

  /**
   * Check if the given field specification is a custom field
   *
   * @param $field_spec
   *
   * @return array if this is a custom field, FALSE if not
   */
  protected function isCustomField($field_name) {
    if (preg_match('/^custom_(?P<group_name>\w+)__(?P<field_name>\w+)$/', $field_name, $match)) {
      return $match;
    } else {
      return FALSE;
    }
  }

  /**
   * Get a structure of custom_group_name => [custom_field_names]
   * of all the custom fields in use
   *
   * Format is: key: custom_<group_name>__<field_name>
   *
   * @return array custom_group_name => [custom_field_names]
   */
  protected function getCustomGroups() {
    $custom_groups = array();
    foreach ($this->config['fields'] as $field_spec) {
      $match = $this->isCustomField($field_spec['key']);
      if ($match) {
        // this is a custom field
        $custom_groups[$match['group_name']][] = $match['field_name'];
      }
    }
    return $custom_groups;
  }

  /**
   * Add the necessary joins for the custom fields
   *
   * @param $joins
   */
  protected function addCustomFieldJoins(&$joins, $entity_alias) {
    // get the custom groups used in the specs
    $custom_groups = $this->getCustomGroups();

    // now join the groups
    foreach (array_keys($custom_groups) as $group_name) {
      $group_alias = $this->getAlias("custom_{$group_name}");
      // TODO: caching?
      $table_name  = civicrm_api3('CustomGroup', 'getvalue', array(
          'name'   => $group_name,
          'return' => 'table_name'));

      $joins[] = "LEFT JOIN {$table_name} {$group_alias} ON {$group_alias}.entity_id = {$entity_alias}.id";
    }
  }

  /**
   * Add the necessary selcts for the custom fields
   *
   * @param $selects
   *
   * @return array if this is
   */
  protected function addCustomFieldSelect(&$selects, $field_name) {
    $match = $this->isCustomField($field_name);
    if ($match) {
      // this is a custom field
      $value_prefix = $this->getValuePrefix();
      $cfield_name = $match['field_name'];
      $cgroup_name = $match['group_name'];
      $group_alias = $this->getAlias("custom_{$cgroup_name}");
      // TODO: cache?
      $cfield_column = civicrm_api3('CustomField', 'getvalue', array(
          'name'            => $cfield_name,
          'custom_group_id' => $cgroup_name,
          'return'          => 'column_name'));

      $selects[] = "{$group_alias}.{$cfield_column} AS {$value_prefix}{$field_name}";
    }
  }
}
