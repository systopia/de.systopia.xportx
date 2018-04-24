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
 * Provides contact email data
 */
class CRM_Xportx_Module_Email extends CRM_Xportx_Module {

  /**
   * Get this module's preferred alias.
   * Must be all lowercase chars: [a-z]+
   */
  public function getPreferredAlias() {
    return 'email';
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // join contact table anyway
    $email_alias = $this->getAlias('email');
    $base_join = "LEFT JOIN civicrm_email {$email_alias} ON {$email_alias}.contact_id = contact.id";
    if (!empty($this->config['params']['location_type_id'])) {
      $base_join .= " AND {$email_alias}.location_type_id = " . (int) $this->config['params']['location_type_id'];
    }
    if (!empty($this->config['params']['primary'])) {
      $base_join .= " AND {$email_alias}.is_primary = 1";
    }
    $joins[] = $base_join;
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $email_alias = $this->getAlias('email');
    $value_prefix  = $this->getValuePrefix();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name = $field_spec['key'];
      switch ($field_name) {
        // process exeptions...
        // case 'country':
        //   $prefix_alias = $this->getAlias('country');
        //   $selects[] = "{$prefix_alias}.name AS {$value_prefix}country";
        //   break;

        default:
          // the default ist a column from the contact table
          $selects[] = "{$email_alias}.{$field_name} AS {$value_prefix}{$field_name}";
          break;
      }
    }
  }
}
