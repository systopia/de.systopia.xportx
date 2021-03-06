<?php
/*-------------------------------------------------------+
| SYSTOPIA EXTENSIBLE EXPORT EXTENSION                   |
| Copyright (C) 2020 SYSTOPIA                            |
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
 * Provides contact groups
 */
class CRM_Xportx_Module_Groups extends CRM_Xportx_Module
{

    /**
     * This module can do with any base_table
     * (as long as it has a contact_id column)
     */
    public function forEntity()
    {
        return 'Entity';
    }

    /**
     * Get this module's preferred alias.
     * Must be all lowercase chars: [a-z]+
     */
    public function getPreferredAlias()
    {
        return 'groups';
    }

    /**
     * add this module's joins clauses to the list
     * they can only refer to the main contact table
     * "contact" or other joins from within the module
     */
    public function addJoins(&$joins)
    {
        // join contact table anyway
        $contact_id = $this->getContactIdExpression();
        $groups_alias = $this->getAlias('groups');
        $group_name_alias = $this->getAlias('group_name');
        $joins[]    = "LEFT JOIN civicrm_group_contact {$groups_alias} ON {$groups_alias}.contact_id = {$contact_id}";
        $joins[]    = "LEFT JOIN civicrm_group {$group_name_alias} ON {$group_name_alias}.id = {$groups_alias}.group_id";
    }

    /**
     * add this module's select clauses to the list
     * they can only refer to the main contact table
     * "contact" or this module's joins
     */
    public function addSelects(&$selects)
    {
        $group_name_alias = $this->getAlias('group_name');
        $value_prefix = $this->getValuePrefix();

        foreach ($this->config['fields'] as $field_spec) {
            $field_name = $field_spec['key'];
            switch ($field_name) {
                case 'group_list':
                    $selects[] = "GROUP_CONCAT({$group_name_alias}.title) AS {$value_prefix}{$field_name}";
                    break;

                default:
                    throw new Exception(E::ts("Unknown field key %1.%2!", [1 => 'Groups', 2 => $field_name]));
            }
        }
    }
}
