<?php
/*-------------------------------------------------------+
| SYSTOPIA EXTENSIBLE EXPORT EXTENSION                   |
| Copyright (C) 2019 SYSTOPIA                            |
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
 * Extracts the location type from entities such as "Email", "Address", "Phone", etc.
 * It also includes parent/child entities, i.e. for entity "Address" it
 *  will include some level of shared entities
 */
class CRM_Xportx_Module_LocationType extends CRM_Xportx_Module {

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
    return 'location_type';
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // join main entity table
    $entity = $this->getEntity();
    $table_name = $this->getTableName($entity);
    $contact_id = $this->getContactIdExpression();
    $location_type_alias = $this->getAlias('location_entity');
    $base_join = "LEFT JOIN {$table_name} {$location_type_alias} ON {$location_type_alias}.contact_id = {$contact_id}";

    // more conditions
    if (!empty($this->config['params']['primary'])) {
      $base_join .= " AND {$location_type_alias}.is_primary = 1";
    }
    $joins[] = $base_join;

    // add joins for parent/child objects
    $include_parent_levels = (int) CRM_Utils_Array::value('include_parent_levels', $this->config['params'], 0);
    $last_level_alias = $location_type_alias;
    for ($i = 1; $i <= $include_parent_levels; $i++) {
      $new_level_alias = $this->getAlias("parent{$i}");
      $this->addLevelJoin($joins, $last_level_alias, $new_level_alias, TRUE);
      $last_level_alias = $new_level_alias;
    }

    $include_child_levels  = (int) CRM_Utils_Array::value('include_child_levels',  $this->config['params'], 0);
    $last_level_alias = $location_type_alias;
    for ($i = 1; $i <= $include_child_levels; $i++) {
      $new_level_alias = $this->getAlias("child{$i}");
      $this->addLevelJoin($joins, $last_level_alias, $new_level_alias, FALSE);
      $last_level_alias = $new_level_alias;
    }
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $value_prefix  = $this->getValuePrefix();

    // add the main entity table
    $main_alias = $this->getAlias('location_entity');
    $selects[] = "{$main_alias}.location_type_id AS {$value_prefix}location_type";

    // add selects for parent/child objects
    $include_parent_levels = (int) CRM_Utils_Array::value('include_parent_levels', $this->config['params'], 0);
    for ($i = 1; $i <= $include_parent_levels; $i++) {
      $level_alias = $this->getAlias("parent{$i}");
      $selects[] = "GROUP_CONCAT({$level_alias}.location_type_id) AS {$value_prefix}location_type_parent{$i}";
    }

    $include_child_levels  = (int) CRM_Utils_Array::value('include_child_levels',  $this->config['params'], 0);
    for ($i = 1; $i <= $include_child_levels; $i++) {
      $level_alias = $this->getAlias("child{$i}");
      $selects[] = "GROUP_CONCAT({$level_alias}.location_type_id) AS {$value_prefix}location_type_child{$i}";
    }
  }

  /**
   * Get the value for the given key form the given record
   */
  public function getFieldValue($record, $key, $field) {
    $value_prefix  = $this->getValuePrefix();
    if ($key == 'location_type' || $key == 'location_type_id') {
      // NOW: gather all location types (main, parent, children)
      $location_type_ids = [];

      // first: the main one
      $value_key = $this->getValuePrefix() . $key;
      $location_type_ids[] = [$record->$value_key];

      // add parent levels
      $include_parent_levels = (int) CRM_Utils_Array::value('include_parent_levels',$this->config['params'], 0);
      for ($i = 1; $i <= $include_parent_levels; $i++) {
        $value_key = "{$value_prefix}location_type_parent{$i}";
        if (!empty($record->$value_key)) {
          $parent_location_type_ids = explode(',', $record->$value_key);
          $location_type_ids[] = $parent_location_type_ids;
        }
      }

      // add child levels
      $include_child_levels  = (int) CRM_Utils_Array::value('include_child_levels',  $this->config['params'], 0);
      for ($i = 1; $i <= $include_child_levels; $i++) {
        $value_key = "{$value_prefix}location_type_child{$i}";
        if (!empty($record->$value_key)) {
          $child_location_type_ids = explode(',', $record->$value_key);
          $location_type_ids[] = $child_location_type_ids;
        }
      }

      // now we have all location type IDs
      $location_type_id = $this->pickLocationTypeId($location_type_ids);

      if ($key == 'location_type') {
        return $this->resolveLocationTypeId($location_type_id);
      }  else {
        return $location_type_id;
      }

    } else {
      return parent::getFieldValue($record, $key, $field);
    }
  }

  /**
   * Add joins of the parent/child related entities (e.g. shared addresses)
   *
   * @param $joins            array    join statements
   * @param $last_level_alias string   name of the entity of the last level
   * @param $new_level_alias  string   name of the entity of the new level
   * @param $parent           boolean  is this a parent
   */
  protected function addLevelJoin(&$joins, $last_level_alias, $new_level_alias, $parent) {
    $entity = $this->getEntity();
    $table_name = $this->getTableName($entity);
    if ($entity == 'Address') {
      if ($parent) {
        // join the parent address
        $joins[] = "LEFT JOIN {$table_name} {$new_level_alias} ON {$new_level_alias}.id = {$last_level_alias}.master_id";
      } else {
        $joins[] = "LEFT JOIN {$table_name} {$new_level_alias} ON {$new_level_alias}.master_id = {$last_level_alias}.id";
      }
    } else {
      // TODO: implement
      throw new Exception("Not implemented!");
    }
  }

  /**
   * Selects the main location type ID according to the configuration
   *
   * @param $location_type_id_sets array of arrays of IDs
   * @return int|null location_type_id
   */
  protected function pickLocationTypeId($location_type_id_sets) {
    $mode = CRM_Utils_Array::value('pick_mode', $this->config['params'], 'first_valid');
    $priority = CRM_Utils_Array::value('pick_priority', $this->config['params'], range(1,10));

    switch ($mode) {
      case 'first_valid':  // pick the first location type id that's in the priority list
        foreach ($location_type_id_sets as $location_type_ids) {
          foreach ($location_type_ids as $location_type_id) {
            $location_type_id = (int) $location_type_id;
            if ($location_type_id && in_array($location_type_id, $priority)) {
              return $location_type_id;
            }
          }
        }
        break;

      case 'priority': // find the location type with the highest priority
        $highest = PHP_INT_MAX;
        foreach ($location_type_id_sets as $location_type_ids) {
          foreach ($location_type_ids as $location_type_id) {
            $location_type_id = (int) $location_type_id;
            if ($location_type_id) {
              $location_type_priority = array_search($location_type_id, $priority);
              if ($location_type_priority !== false) {
                if ($location_type_priority < $highest) {
                  $highest = $location_type_priority;
                }
              }
            }
          }
        }
        if ($highest < PHP_INT_MAX) {
          return (int) $priority[$highest];
        }
        break;

      default:
        throw new Exception("Invalid pick mode '{$mode}'");
    }
    return NULL;
  }

  /**
   * Get the entity this module is configured for
   *
   * @return string entity name
   */
  protected function getEntity() {
    if (empty($this->config['params']['entity'])) {
      return 'Address';
    } else {
      return $this->config['params']['entity'];
    }
  }

  /**
   * Get the table name of the entity
   *
   * @param string $entity the entity
   * @return string civicrm table name
   */
  protected function getTableName($entity) {
    // TODO: is this enough?
    return 'civicrm_' . strtolower($entity);
  }

  /**
   * Get the name of a location type
   *
   * @param $location_type_id int location type id
   */
  protected function resolveLocationTypeId($location_type_id) {
    $location_type_id = (int) $location_type_id;
    if ($location_type_id) {
      static $location_type_names = NULL;
      if ($location_type_names === NULL) {
        $location_type_names = [];
        $query = civicrm_api3('LocationType', 'get', ['option.limit' => 0, 'return' => 'id,name,display_name']);
        foreach ($query['values'] as $location_type) {
          $location_type_names[$location_type['id']] = $location_type['display_name'];
        }
      }
      return CRM_Utils_Array::value($location_type_id, $location_type_names, NULL);
    } else {
      return NULL;
    }
  }
}
