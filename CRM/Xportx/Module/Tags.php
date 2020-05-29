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
 * Provides contact tags
 */
class CRM_Xportx_Module_Tags extends CRM_Xportx_Module
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
        return 'tags';
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
        $tags_alias = $this->getAlias('tags');
        $tag_name_alias = $this->getAlias('tag_name');
        $joins[]    = "LEFT JOIN civicrm_entity_tag {$tags_alias} ON {$tags_alias}.entity_id = {$contact_id}";
        $joins[]    = "LEFT JOIN civicrm_tag {$tag_name_alias} ON {$tag_name_alias}.id = {$tags_alias}.tag_id";
    }

    /**
     * add this module's select clauses to the list
     * they can only refer to the main contact table
     * "contact" or this module's joins
     */
    public function addSelects(&$selects)
    {
        $tag_name_alias = $this->getAlias('tag_name');
        $value_prefix = $this->getValuePrefix();

        foreach ($this->config['fields'] as $field_spec) {
            $field_name = $field_spec['key'];
            switch ($field_name) {
                case 'tag_list':
                    $selects[] = "GROUP_CONCAT({$tag_name_alias}.name) AS {$value_prefix}{$field_name}";
                    break;

                default:
                    throw new Exception(E::ts("Unknown field key %1.%2!", [1 => 'Email', 2 => $field_name]));
            }
        }
    }
}
