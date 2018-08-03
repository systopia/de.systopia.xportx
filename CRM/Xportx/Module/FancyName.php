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
 * Provides 'fancy names' for contacts, i.e.
 * the names are calculated based on e.g. the contact_type
 *
 * @warning this is still an untested prototype
 */
class CRM_Xportx_Module_FancyName extends CRM_Xportx_Module {

//{
//"class": "CRM_Xportx_Module_FancyName",
//"config": {
//"params": {
//"name_fields": 2,
//"Individual": "addressee",
//"Organization": "addressee",
//"Household": "family2"
//},
//"fields": [
//          {
//            "key": "fancyname1",
//            "label": "Name1"
//          },
//          {
//            "key": "fancyname2",
//            "label": "Name2"
//          }
//        ]
//      }
//    },
//
//

  /**
   * This module can do with any base_table
   * (as long as it has a contact_id column)
   */
  public function forEntity() {
    return 'Entity';
  }

  /**
   * Get this module's preferred alias.
   * Must be all lowercase chars: [a-z]+
   */
  public function getPreferredAlias() {
    return 'fancyname';
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // join membership table (with parameters)
    $contact_id = $this->getContactIdExpression();
    $membership_alias = $this->getAlias('membership');
    $membership_join = "LEFT JOIN civicrm_membership {$membership_alias} ON {$membership_alias}.contact_id = {$contact_id}";
    if (!empty($this->config['params']['membership_status_ids'])) {
      $status_ids = implode(',', $this->config['params']['membership_status_ids']);
      $membership_join .= " AND {$membership_alias}.status_id IN ({$status_ids})";
    }
    if (!empty($this->config['params']['membership_type_ids'])) {
      $status_ids = implode(',', $this->config['params']['membership_type_ids']);
      $membership_join .= " AND {$membership_alias}.membership_type_id IN ({$status_ids})";
    }
    $joins[] = $membership_join;


    // now find custom fields
    $custom_groups = $this->getCustomGroups();

    // now join the groups
    foreach (array_keys($custom_groups) as $group_name) {
      $group_alias = $this->getAlias("custom_{$group_name}");
      $table_name  = civicrm_api3('CustomGroup', 'getvalue', array('name' => $group_name, 'return' => 'table_name'));
      $joins[] = "LEFT JOIN {$table_name} {$group_alias} ON {$group_alias}.entity_id = {$membership_alias}.id";
    }
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $contact_alias = $this->getAlias('contact');
    $value_prefix  = $this->getValuePrefix();
    $custom_groups = $this->getCustomGroups();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name = $field_spec['key'];
      if (preg_match('/^custom_(?P<group_name>\w+)__(?P<field_name>\w+)$/', $field_name, $match)) {
        // this is a custom field
        $cfield_name = $match['field_name'];
        $cgroup_name = $match['group_name'];
        $group_alias = $this->getAlias("custom_{$cgroup_name}");
        $cfield_column = civicrm_api3('CustomField', 'getvalue', array('name' => $cfield_name, 'return' => 'column_name'));
        $selects[] = "{$group_alias}.{$cfield_column} AS {$value_prefix}{$field_name}";
      } else {
        // this is a base field
        switch ($field_name) {
          // process exeptions...
//          case 'XXX':
//            $prefix_alias = $this->getAlias('prefix');
//            $selects[] = "{$prefix_alias}.label AS {$value_prefix}prefix";
//            break;

          default:
            // the default ist a column from the contact table
            $selects[] = "{$contact_alias}.{$field_name} AS {$value_prefix}{$field_name}";
            break;
        }
      }
    }
  }

  /**
   * Get a structure of custom_group_name => [custom_field_names]
   * of all the custom fields in use
   *
   * @return array custom_group_name => [custom_field_names]
   */
  protected function getCustomGroups() {
    $custom_groups = array();
    foreach ($this->config['fields'] as $field_spec) {
      if (preg_match('/^custom_(?P<group_name>\w+)__(?P<field_name>\w+)$/', $field_spec['key'], $match)) {
        // this is a custom field
        $custom_groups[$match['group_name']][] = $match['field_name'];
      }
    }
    return $custom_groups;
  }
}
