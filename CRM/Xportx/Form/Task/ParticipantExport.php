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

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Xportx_Form_Task_ParticipantExport extends CRM_Event_Form_Task
{

    /**
     * Compile task form
     */
    function buildQuickForm()
    {
        // init export object
        $configuration_list = array();
        $configurations     = CRM_Xportx_Export::getExportConfigurations('Participant');
        foreach ($configurations as $key => $config) {
            $configuration_list[$key] = $config['title'];
        }

        // add the config selector
        $this->addElement(
            'select',
            'export_configuration',
            E::ts("Preset"),
            $configuration_list,
            array('class' => 'huge'),
            true
        );

        // now build the form
        CRM_Utils_System::setTitle(
            E::ts(
                'Export %1 Participants',
                array(1 => count($this->_participantIds))
            )
        );

        CRM_Core_Form::addDefaultButtons(E::ts("Export"));
    }


    function postProcess()
    {
        $values          = $this->exportValues();
        $selected_config = $values['export_configuration'];
        $configurations  = CRM_Xportx_Export::getExportConfigurations('Participant');

        if (empty($configurations[$selected_config])) {
            throw new Exception("No configuration found");
        }

        // run export
        $configuration = $configurations[$selected_config];
        $export        = new CRM_Xportx_Export($configuration);
        $export->writeToStream($this->_participantIds);
    }
}
