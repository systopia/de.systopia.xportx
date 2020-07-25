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
class CRM_Xportx_Module_ContactBase extends CRM_Xportx_Module
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
        return 'cbase';
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
        $contact_alias = $this->getAlias('contact');
        $joins[] = "LEFT JOIN civicrm_contact {$contact_alias} ON {$contact_alias}.id = {$contact_id}";

        // join prefix option group if needed
        foreach ($this->config['fields'] as $field_spec) {
            if ($field_spec['key'] == 'prefix') {
                $prefix_alias = $this->getAlias('prefix');
                $joins[]      = $this->generateOptionValueJoin(
                    'individual_prefix',
                    "{$contact_alias}.prefix_id",
                    $prefix_alias
                );
                break;
            }
        }
    }

    /**
     * add this module's select clauses to the list
     * they can only refer to the main contact table
     * "contact" or this module's joins
     */
    public function addSelects(&$selects)
    {
        $contact_alias = $this->getAlias('contact');
        $value_prefix  = $this->getValuePrefix();

        foreach ($this->config['fields'] as $field_spec) {
            $field_name = $field_spec['key'];
            switch ($field_name) {
                // process exeptions...
                case 'prefix':
                    $prefix_alias = $this->getAlias('prefix');
                    $selects[]    = "{$prefix_alias}.label AS {$value_prefix}prefix";
                    break;

                default:
                    // the default ist a column from the contact table
                    $selects[] = "{$contact_alias}.{$field_name} AS {$value_prefix}{$field_name}";
                    break;
            }
        }
    }
}
