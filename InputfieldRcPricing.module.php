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

  /**
   * Render the Inputfield
   * @return string
   */
  public function ___render()
  {
    $page = $this->wire->pages->get($this->wire->input->get->id);
    $variations = json_encode($page->getVariationsArray());
    $data = json_encode($page->getDataArray());

    $out = parent::___render();
    $grid = "grid_{$this->name}";
    return "
      <div id='set-prices' class='uk-margin-small uk-flex uk-flex-middle uk-flex-right'>
        <span class='uk-margin-small-right'>Set price on all visible rows</span>
        <input type='text' class='uk-input uk-form-small' style='width: 100px;'>
      </div>
      <div id='$grid'></div>
      <script>RcPricing.init('#$grid', $variations, $data)</script>
      <div hidden>$out</div>";
  }

  public function renderReady(?Inputfield $parent = null, $renderValueMode = false)
  {
    rockmigrations()->addScripts(__DIR__ . "/../RockGrid/tabulator/tabulator.min.js");
    rockmigrations()->addStyles(__DIR__ . "/../RockGrid/tabulator/tabulator.min.css");
    parent::renderReady($parent, $renderValueMode);
  }
}
