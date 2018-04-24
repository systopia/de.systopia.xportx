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
class CRM_Xportx_Form_Task_Export extends CRM_Contact_Form_Task {

  /**
   * Compile task form
   */
  function buildQuickForm() {
    // init export object
    $configuration_list = array();
    $configurations = $this->getExportConfigurations();
    foreach ($configurations as $key => $config) {
      $configuration_list[$key] = $config['title'];
    }

    // add the config selector
    $this->addElement('select',
                      'export_configuration',
                      E::ts("Configuration"),
                      $configuration_list,
                      array('class' => 'huge'),
                      TRUE);

    // now build the form
    CRM_Utils_System::setTitle(E::ts('Export %1 Contacts',
      array(1 => count($this->_contactIds))));

    CRM_Core_Form::addDefaultButtons(E::ts("Export"));
  }


  function postProcess() {
    $values = $this->exportValues();
    $selected_config = $values['export_configuration'];
    $configurations = $this->getExportConfigurations();

    if (empty($configurations[$selected_config])) {
      throw new Exception("No configuration found");
    }

    // run export
    $configuration = $configurations[$selected_config];
    $export = new CRM_Xportx_Export($configuration);
    $export->writeToStream($this->_contactIds);
  }

  /**
   * get all currently stored export configurations
   */
  protected function getExportConfigurations() {
    // find all export configurations in folder 'export_configurations'
    $configurations = array();

    $folder = __DIR__ . '/../../../../export_configurations';
    $files = scandir($folder);
    foreach ($files as $file) {
      if (preg_match("#[a-z0-9_]+[.]json#", $file)) {
        // this is a json file
        $content = file_get_contents($folder . DIRECTORY_SEPARATOR . $file);
        $config = json_decode($content, TRUE);
        if ($config) {
          $configurations[$file] = $config;
        }
      }
    }

    return $configurations;
  }
}
