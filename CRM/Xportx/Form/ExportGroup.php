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
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Xportx_Form_ExportGroup extends CRM_Core_Form {

  public function buildQuickForm() {
    // get group ID
    $group_id = CRM_Utils_Request::retrieve('group_id', 'Integer', $this);
    if (empty($group_id)) {
      throw new Exception(E::ts("You need to provide a group_id!"));
    }

    // set title
    $group_name = civicrm_api3('Group', 'getvalue', ['id' => $group_id, 'return' => 'title']);
    $group_size = civicrm_api3('GroupContact', 'getcount', ['group_id' => $group_id, 'status' => 'Added']);
    CRM_Utils_System::setTitle(E::ts('Export Group "%1" (%2 Contacts)', [
        1 => $group_name,
        2 => $group_size]));


    // init export object
    $configuration_list = [];
    $configurations = CRM_Xportx_Export::getExportConfigurations('GroupContact');
    foreach ($configurations as $key => $config) {
      $configuration_list[$key] = $config['title'];
    }

    // add the config selector
    $this->addElement('select',
        'export_configuration',
        E::ts("Preset"),
        $configuration_list,
        array('class' => 'huge'),
        TRUE);

    CRM_Core_Form::addDefaultButtons(E::ts("Export"));
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $selected_config = $values['export_configuration'];
    $configurations = CRM_Xportx_Export::getExportConfigurations('GroupContact');

    if (empty($configurations[$selected_config])) {
      throw new Exception("No configuration found");
    }

    // run export
    $configuration = $configurations[$selected_config];
    $export = new CRM_Xportx_Export($configuration);
    $export->writeToStream([$this->get('group_id')]);

    parent::postProcess();
  }

}
