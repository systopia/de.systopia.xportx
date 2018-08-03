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
 * Proveides Participant data
 *
 * This exporter is only available for participant exports
 */
class CRM_Xportx_Module_Participant extends CRM_Xportx_Module {

  /**
   * This module can do with any base_table
   * (as long as it has a contact_id column)
   */
  public function forEntity() {
    return 'Participant';
  }

  /**
   * Get this module's preferred alias.
   * Must be all lowercase chars: [a-z]+
   */
  public function getPreferredAlias() {
    return 'participant';
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // join participant table (strictly speaking not necessary, but we'll do anyway for compatibility)
    $participant_alias = $this->getAlias('participant');
    $base_alias = $this->getBaseAlias(); // this should be an alias of the civicrm_participant table
    $joins[] = "LEFT JOIN civicrm_participant {$participant_alias} ON {$participant_alias}.id = {$base_alias}.id";

    // join participant_roles?
    foreach ($this->config['fields'] as $field_spec) {
      if ($field_spec['key'] == 'role') {
        // join roles table
        $role_alias = $this->getAlias('participant_role');
        $joins[] = $this->generateOptionValueJoin('participant_role', "{$participant_alias}.role_id", $role_alias);
        break;
      }
    }

    // more joins?
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $participant_alias = $this->getAlias('participant');
    $value_prefix  = $this->getValuePrefix();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name = $field_spec['key'];
      switch ($field_name) {
        case 'role':
          $role_alias = $this->getAlias('participant_role');
          $selects[] = "{$role_alias}.label AS {$value_prefix}{$field_name}";
          break;

        default:
          // the default ist a column from the contact table
          $selects[] = "{$participant_alias}.{$field_name} AS {$value_prefix}{$field_name}";
          break;
      }
    }
  }
}
