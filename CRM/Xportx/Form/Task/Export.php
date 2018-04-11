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

  protected $export;

  /**
   * Compile task form
   */
  function buildQuickForm() {
    // init export object
    // TODO: configuration should come from $_REQUEST
    $configuration = array(
      'configuration' => array(),
      'modules' => array(
        array(
          'class'  => 'CRM_Xportx_Module_ContactBase',
          'config' => array()),
      ),
      'exporter' =>
        array(
          'class'  => 'CRM_Xportx_Exporter_CSV',
          'config' => array(),
      )
    );
    $this->export = new CRM_Xportx_Export($configuration);


    // now build the form
    CRM_Utils_System::setTitle(E::ts('Export %1 Contacts with XPortX',
      array(1 => count($this->_contactIds))));

    CRM_Core_Form::addDefaultButtons(E::ts("Export"));
  }


  function postProcess() {
    $this->export->writeToStream($this->_contactIds);
  }
}
