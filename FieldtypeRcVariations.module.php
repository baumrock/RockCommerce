<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 25.01.2024
 * @license COMMERCIAL DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 */
class FieldtypeRcVariations extends FieldtypeTextarea
{

  public static function getModuleInfo()
  {
    return [
      'title' => 'RcVariations',
      'version' => json_decode(file_get_contents(__DIR__ . '/package.json'))->version,
      'summary' => '',
      'icon' => 'cubes',
      'requires' => [
        'RockCommerce',
      ],
      'installs' => [],
    ];
  }

  public function init()
  {
    parent::init();
  }

  /** FIELDTYPE METHODS */

  /**
   * Sanitize value for storage
   *
   * @param Page $page
   * @param Field $field
   * @param string $value
   * @return string
   */
  public function sanitizeValue(Page $page, Field $field, $value)
  {
    return $value;
  }

  /** HELPER METHODS */
}
