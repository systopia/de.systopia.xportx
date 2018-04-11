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
 * Base class for all exporters
 */
abstract class CRM_Xportx_Exporter {

  protected $config;
  protected $export;

  public function init($config, $export) {
    $this->export = $export;
    $this->config = $config;
  }

  /**
   * get the mime type created by this exporter
   */
  abstract public function getMimeType();

  /**
   * get the proposed file name
   */
  abstract public function getFileName();

  /**
   * write all data to the given file
   */
  abstract public function writeToFile($data, $file_name);
}
