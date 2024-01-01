<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 01.01.2024
 * @license COMMERCIAL DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 */
class InputfieldRcPricing extends InputfieldTextarea implements InputfieldHasTextValue
{

  public static function getModuleInfo()
  {
    return [
      'title' => 'RockCommerce Pricing',
      'version' => '0.0.1',
      'summary' => 'Inputfield to manage variation pricing',
      'icon' => 'cubes',
      'requires' => [
        'RockCommerce',
      ],
    ];
  }

  public function getPricingArray(): array
  {
    return [];
  }

  /**
   * Render the Inputfield
   * @return string
   */
  public function ___render()
  {
    $out = parent::___render();
    $grid = "grid_{$this->name}";
    return "<div id='$grid'></div>
      <script>RcVariations.init('#$grid')</script>
      <div hidden>$out</div>";
  }

  public function renderReady(?Inputfield $parent = null, $renderValueMode = false)
  {
    rockmigrations()->addScripts(__DIR__ . "/../RockGrid/tabulator/tabulator.min.js");
    rockmigrations()->addStyles(__DIR__ . "/../RockGrid/tabulator/tabulator.min.css");
    parent::renderReady($parent, $renderValueMode);
  }

  // /**
  //  * Process the Inputfield's input
  //  * @return $this
  //  */
  // public function ___processInput($input)
  // {
  //   $this->message('process input!');
  //   return false;
  // }
}
