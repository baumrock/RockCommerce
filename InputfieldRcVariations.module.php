<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 25.01.2024
 * @license COMMERCIAL DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 */
class InputfieldRcVariations extends InputfieldTextarea
{

  public static function getModuleInfo()
  {
    return [
      'title' => 'RcVariations Inputfield',
      'version' => json_decode(file_get_contents(__DIR__ . '/package.json'))->version,
      'summary' => 'Your module description',
      'icon' => 'cubes',
      'requires' => [
        'RockCommerce',
      ],
      'installs' => [],
    ];
  }

  /**
   * Render the Inputfield
   * @return string
   */
  public function ___render()
  {
    return 'Inputfield RENDER';
  }

  /**
   * Process the Inputfield's input
   * @return $this
   */
  public function ___processInput($input)
  {
    $this->message('process input!');
    return false;
  }
}
