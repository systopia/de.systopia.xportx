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
 * Provides contact base data
 */
class CRM_Xportx_Module_ContactBase extends CRM_Xportx_Module {

  /**
   * Get this module's preferred alias.
   * Must be all lowercase chars: [a-z]+
   */
  public function getPreferredAlias() {
    return 'cbase';
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // TODO: generate this based on $this->config
    $contact_alias = $this->getAlias('contact');
    $joins[] = "LEFT JOIN civicrm_contact {$contact_alias} ON {$contact_alias}.id = contact.id";

    $prefix_alias = $this->getAlias('prefix');
    $joins[] = $this->generateOptionValueJoin('individual_prefix', "{$contact_alias}.prefix_id", $prefix_alias);
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    // TODO: generate this based on $this->config
    $contact_alias = $this->getAlias('contact');
    $value_prefix  = $this->getValuePrefix();
    $selects[] = "{$contact_alias}.first_name AS {$value_prefix}first_name";
    $selects[] = "{$contact_alias}.last_name  AS {$value_prefix}last_name";

    $prefix_alias = $this->getAlias('prefix');
    $selects[] = "{$prefix_alias}.label AS {$value_prefix}prefix";
  }

  /**
   * Get a list of all fields.
   *
   * @return array(array('key' => key, 'label' -> 'header'),...)
   */
  public function getFieldList() {
    // TODO: generate this based on $this->config
    return array(
      array(
        'key'   => 'first_name',
        'label' => ts('First Name'),
      ),
      array(
        'key'   => 'last_name',
        'label' => ts('Last Name'),
      ),
      array(
        'key'   => 'prefix',
        'label' => ts('Prefix'),
      ),
    );
  }
}
