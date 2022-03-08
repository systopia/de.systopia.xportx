<?php
/*-------------------------------------------------------+
| SYSTOPIA EXTENSIBLE EXPORT EXTENSION                   |
| Copyright (C) 2022 SYSTOPIA                            |
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
 * Provides contact phone data
 */
class CRM_Xportx_Module_Phone extends CRM_Xportx_Module
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
        return 'phone';
    }

    /**
     * add this module's joins clauses to the list
     * they can only refer to the main contact table
     * "contact" or other joins from within the module
     */
    public function addJoins(&$joins)
    {
        // join contact table anyway
        $contact_id  = $this->getContactIdExpression();
        $phone_alias = $this->getAlias('phone');
        $base_join   = "LEFT JOIN civicrm_phone {$phone_alias} ON {$phone_alias}.contact_id = {$contact_id}";
        if (!empty($this->config['params']['location_type_id'])) {
            $base_join .= " AND {$phone_alias}.location_type_id = " . (int)$this->config['params']['location_type_id'];
        }
        if (!empty($this->config['params']['primary'])) {
            $base_join .= " AND {$phone_alias}.is_primary = 1";
        }
        $joins[] = $base_join;
    }

    /**
     * add this module's select clauses to the list
     * they can only refer to the main contact table
     * "contact" or this module's joins
     */
    public function addSelects(&$selects)
    {
        $phone_alias  = $this->getAlias('phone');
        $value_prefix = $this->getValuePrefix();

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
                    $selects[] = "{$phone_alias}.{$field_name} AS {$value_prefix}{$field_name}";
                    break;
            }
        }
    }
}
