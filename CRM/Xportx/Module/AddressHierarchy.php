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
 * Find and provide address data with a given search hierarchy
 */
class CRM_Xportx_Module_AddressHierarchy extends CRM_Xportx_Module {

  protected $hierarchy = NULL;

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
    return 'address_hierarchy';
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // join all items from the list
    $contact_id = $this->getContactIdExpression();
    $hierarchy = $this->getHierarchy();
    $index = 0;
    foreach ($hierarchy as $location_type) {
      $address_alias = $this->getAlias("location{$index}");
      $base_join = "LEFT JOIN civicrm_address {$address_alias} ON {$address_alias}.contact_id = {$contact_id}";
      if ($location_type == 'primary') {
        $base_join .= " AND {$address_alias}.is_primary = 1";
      } elseif ($location_type == 'billing') {
        $base_join .= " AND {$address_alias}.is_billing = 1";
      } else {
        $base_join .= " AND {$address_alias}.location_type_id = " . (int) $location_type;
      }
      $joins[] = $base_join;
      $index++;
    }

    // join country if needed
    foreach ($this->config['fields'] as $field_spec) {
      if ($field_spec['key'] == 'country') {
        $index = 0;
        foreach ($hierarchy as $location_type) {
          $address_alias = $this->getAlias("location{$index}");
          $country_alias = $this->getAlias("country{$index}");
          $joins[] = "LEFT JOIN civicrm_country {$country_alias} ON {$country_alias}.id = {$address_alias}.country_id";
          $index++;
        }
        break;
      }
    }
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $value_prefix  = $this->getValuePrefix();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name = $field_spec['key'];
      switch ($field_name) {
        // process exceptions...
        case 'country':
          // the default ist a column from the contact table
          $select = $this->getHierarchySelect('name', 'country');
          $selects[] = "{$select} AS {$value_prefix}{$field_name}";
          break;

        default:
          // the default ist a column from the contact table
          $select = $this->getHierarchySelect($field_name);
          $selects[] = "{$select} AS {$value_prefix}{$field_name}";
          break;
      }
    }
  }


  /**
   * Get the address location type IDs to look for
   */
  protected function getHierarchy() {
    if ($this->hierarchy === NULL) {
      $this->hierarchy = array('primary'); // default
      if (!empty($this->config['params']['hierarchy'])) {
        if (is_array($this->config['params']['hierarchy'])) {
          $this->hierarchy = $this->config['params']['hierarchy'];
        } else {
          throw new Exception("AddressHierarchy: hierarchy has to be an array!");
        }
      }
    }
    return $this->hierarchy;
  }

  /**
   * Get the address location type IDs to look for
   */
  protected function getHierarchySelect($field_name, $alias = 'location', $index = 0) {
    $hierarchy = $this->getHierarchy();
    if ($index + 1 > count($hierarchy)) {
      return NULL;
    }

    $lower_value = $this->getHierarchySelect($field_name, $alias, $index+1);
    $location_alias = $this->getAlias("{$alias}{$index}");
    if ($lower_value) {
      return "IF({$location_alias}.id IS NOT NULL, {$location_alias}.{$field_name}, {$lower_value})";
    } else {
      return "{$location_alias}.{$field_name}";
    }
  }
}