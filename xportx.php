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

require_once 'xportx.civix.php';

use CRM_Xportx_ExtensionUtil as E;

/**
 * Add links to groups
 */
function xportx_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values)
{
    if ($objectName == 'Group' && $op == 'group.selector.row') {
        // add rebook link
        $links[] = [
            'name'  => E::ts('Export'),
            'title' => E::ts('Export (Custom Presets)'),
            'url'   => 'civicrm/xportx/group',
            'qs'    => "group_id={$objectId}",
        ];
    }
}

/**
 * Add contact search tasks to submit tax excemption XMLs
 *
 * @param string $objectType specifies the component
 * @param array $tasks the list of actions
 *
 * @access public
 */
function xportx_civicrm_searchTasks($objectType, &$tasks)
{
    if ($objectType == 'contact') {
        $tasks[] = array(
            'title'  => E::ts('Export (Custom Presets)'),
            'class'  => 'CRM_Xportx_Form_Task_Export',
            'result' => false
        );
    } elseif ($objectType == 'event') {
        $tasks[] = array(
            'title'  => E::ts('Export (Custom Presets)'),
            'class'  => 'CRM_Xportx_Form_Task_ParticipantExport',
            'result' => false
        );
    }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function xportx_civicrm_config(&$config)
{
    _xportx_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function xportx_civicrm_install()
{
    _xportx_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function xportx_civicrm_enable()
{
    _xportx_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function xportx_civicrm_entityTypes(&$entityTypes)
{
    _xportx_civix_civicrm_entityTypes($entityTypes);
}
