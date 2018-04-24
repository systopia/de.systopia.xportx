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
 * Provides contact custom group data
 */
class CRM_Xportx_Module_CustomGroup extends CRM_Xportx_Module {

  /**
   * Get this module's preferred alias.
   * Must be all lowercase chars: [a-z]+
   */
  public function getPreferredAlias() {
    return 'custom';
  }

  /**
   * get the custom group entity data
   */
  protected function getCustomGroup() {
    // TODO: cache
    return civicrm_api3('CustomGroup', 'getsingle', array(
      'name' => $this->config['params']['group_name']
    ));
  }

  /**
   * get the custom field entity data
   */
  protected function getCustomFields() {
    // TODO: cache
    $custom_group = $this->getCustomGroup();

    // gather field names
    $field_names = array();
    foreach ($this->config['fields'] as $field_spec) {
      $field_names[] = $field_spec['key'];
    }

    // load all fields
    $field_data = civicrm_api3('CustomField', 'get', array(
      'custom_group_id' => $custom_group['id'],
      'name'            => array('IN' => $field_names),
      'option.limit'    => 0
    ));

    // compile result set
    $fields = array();
    foreach ($field_data['values'] as $field_entity) {
      $fields[$field_entity['name']] = $field_entity;
    }

    return $fields;
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // join custom group table
    $custom_group = $this->getCustomGroup();
    $custom_alias = $this->getAlias('custom');
    $joins[] = "LEFT JOIN {$custom_group['table_name']} {$custom_alias} ON {$custom_alias}.entity_id = contact.id";
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $custom_alias  = $this->getAlias('custom');
    $value_prefix   = $this->getValuePrefix();
    $field_entities = $this->getCustomFields();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name   = $field_spec['key'];
      $field_entity = $field_entities[$field_name];
      $selects[] = "{$custom_alias}.{$field_entity['column_name']} AS {$value_prefix}{$field_name}";
    }
  }
}
