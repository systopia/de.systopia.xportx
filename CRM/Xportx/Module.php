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
 * Base class for all data modules
 */
abstract class CRM_Xportx_Module {

  protected $config;
  protected $export;

  public function init($config, $export) {
    $this->export = $export;
    $this->config = $config;
  }

  /**
   * Get this module's preferred alias.
   * Must be all lowercase chars: [a-z]+
   */
  public abstract function getPreferredAlias();

  /**
   * add this module's select clauses to the list
   * they can only refer to the main contact table
   * "contact" or this module's joins
   */
  public function addSelects(&$selects) {
    // override if needed
  }

  /**
   * add this module's joins clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addJoins(&$joins) {
    // override if needed
  }

  /**
   * add this module's where clauses to the list
   * they can only refer to the main contact table
   * "contact" or other joins from within the module
   */
  public function addWheres(&$wheres) {
    // override if needed
  }

  /**
   * Get a numeric of this instance
   * (within its export context)
   */
  public function getID() {
    return $this->export->getModuleID($this);
  }

  /**
   * Get a uniqe alias for the given (internal) name
   * $name must be all lowercase chars: [a-z]+
   */
  public function getAlias($name) {
    return $this->getPreferredAlias() . $this->getID() . '_' . $name;
  }
}
