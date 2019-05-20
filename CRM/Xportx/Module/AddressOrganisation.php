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
 * This module will identify the organisation responsible for the exported
 *  contact via the master address
 *
 * The logic is as follows:
 * 1) if the exported contact itself is an organisation, export the main contact's fields
 * 2) if the address master's contact is an organisation, export that contact's fields
 * 3) if neither is the case, the fields will be empty
 *
 * This module also accommodates custom code for a two-part organisation name
 *  in the custom group table  civicrm_value_organisation_name (HBS)
 */
class CRM_Xportx_Module_AddressOrganisation extends CRM_Xportx_Module {

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
    return 'hbsorg';
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // join contact table anyway
    $contact_id = $this->getContactIdExpression();
    $address_alias = $this->getAlias('address');
    $address_join = "LEFT JOIN civicrm_address {$address_alias} ON {$address_alias}.contact_id = {$contact_id}";
    if (!empty($this->config['params']['location_type_id'])) {
      $address_join .= " AND {$address_alias}.location_type_id = " . (int) $this->config['params']['location_type_id'];
    }
    if (!empty($this->config['params']['primary'])) {
      $address_join .= " AND {$address_alias}.is_primary = 1";
    }
    $joins[] = $address_join;

    // join master
    $master_alias = $this->getAlias('master');
    $joins[] = "LEFT JOIN civicrm_address {$master_alias} ON {$master_alias}.id = {$address_alias}.master_id";

    // then join the master contact
    $master_contact_alias = $this->getAlias('master_contact');
    $joins[] = "LEFT JOIN civicrm_contact {$master_contact_alias} ON {$master_contact_alias}.id = {$master_alias}.contact_id";

    // also join the main contact (again)
    $main_contact_alias   = $this->getAlias('main_contact');
    $joins[] = "LEFT JOIN civicrm_contact {$main_contact_alias} ON {$main_contact_alias}.id = {$contact_id}";

    // custom code for HBS:
    foreach ($this->config['fields'] as $field) {
      if ($field['key'] == 'organisation_name_1' || $field['key'] == 'organisation_name_2') {
        // finally join the civicrm_value_organisation_name table:
        //  once for the address master:
        $orgname_alias = $this->getAlias('masterorg');
        $joins[] = "LEFT JOIN civicrm_value_organisation_name {$orgname_alias} ON {$orgname_alias}.entity_id = {$master_contact_alias}.id";

        $mainorg_alias = $this->getAlias('mainorg');
        $joins[] = "LEFT JOIN civicrm_value_organisation_name {$mainorg_alias} ON {$mainorg_alias}.entity_id = {$main_contact_alias}.id";

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
    $main_contact_alias   = $this->getAlias('main_contact');
    $master_contact_alias = $this->getAlias('master_contact');
    $orgname_alias = $this->getAlias('masterorg');
    $mainorg_alias = $this->getAlias('mainorg');
    $value_prefix  = $this->getValuePrefix();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name = $field_spec['key'];
      switch ($field_name) {
        case 'display_name':
          $selects[] = "IF({$main_contact_alias}.contact_type = 'Organization', {$main_contact_alias}.display_name, IF({$master_contact_alias}.contact_type = 'Organization', {$master_contact_alias}.display_name, '')) AS {$value_prefix}{$field_name}";
          break;

        case 'organisation_name_1':
          $selects[] = "IF({$main_contact_alias}.contact_type = 'Organization', {$mainorg_alias}.row_1, IF({$master_contact_alias}.contact_type = 'Organization', {$orgname_alias}.row_1, '')) AS {$value_prefix}{$field_name}";
          break;

        case 'organisation_name_2':
          $selects[] = "IF({$main_contact_alias}.contact_type = 'Organization', {$mainorg_alias}.row_2, IF({$master_contact_alias}.contact_type = 'Organization', {$orgname_alias}.row_2, '')) AS {$value_prefix}{$field_name}";
          break;

        default:
          // there's no default here
          break;
      }
    }
  }
}
