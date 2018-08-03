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
 * Specialised module for HBS' two-line organisation name
 * @todo move to HBS extension
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

    // then join the contact
    $contact_alias = $this->getAlias('contact');
    $joins[] = "LEFT JOIN civicrm_contact {$contact_alias} ON {$contact_alias}.id = {$master_alias}.contact_id";

    // finally join the civicrm_value_organisation_name table:
    //  once for the address master:
    $orgname_alias = $this->getAlias('masterorg');
    $joins[] = "LEFT JOIN civicrm_value_organisation_name {$orgname_alias} ON {$orgname_alias}.entity_id = {$contact_alias}.id";

    // once for the organisation itself
    $selforg_alias = $this->getAlias('selforg');
    $joins[] = "LEFT JOIN civicrm_value_organisation_name {$selforg_alias} ON {$selforg_alias}.entity_id = {$contact_id}";
  }

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    $contact_alias = $this->getAlias('contact');
    $orgname_alias = $this->getAlias('masterorg');
    $selforg_alias = $this->getAlias('selforg');
    $value_prefix  = $this->getValuePrefix();

    foreach ($this->config['fields'] as $field_spec) {
      $field_name = $field_spec['key'];
      switch ($field_name) {
        // process exeptions...
        case 'display_name':
          $selects[] = "IF(contact.contact_type = 'Organization', contact.display_name, {$contact_alias}.{$field_name}) AS {$value_prefix}{$field_name}";
          break;

        case 'organisation_name_1':
          $selects[] = "IF(contact.contact_type = 'Organization', {$selforg_alias}.row_1, {$orgname_alias}.row_1) AS {$value_prefix}{$field_name}";
          break;

        case 'organisation_name_2':
          $selects[] = "IF(contact.contact_type = 'Organization', {$selforg_alias}.row_2, {$orgname_alias}.row_2) AS {$value_prefix}{$field_name}";
          break;

        default:
          // there's no default here
          break;
      }
    }
  }
}
