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

  private function oldPricing($page): string
  {
    $oldPricing = json_decode($page->meta('rc-old-pricing') ?: "", true);
    if (!$oldPricing) return "";

    $table = "
      <style>
      #rc-old-prices td { padding: 2px 10px; white-space: nowrap; }
      </style>
      <table id='rc-old-prices' class='uk-table uk-table-small uk-table-striped uk-margin-small-top'>";
    foreach ($oldPricing as $k => $v) {
      $vars = explode("|", $k);
      $vars = implode("</td><td>", $vars);
      $table .= "<tr><td>$vars</td><td class='uk-width-expand'>$v</td></tr>";
    }
    $table .= "</table>";
    return "<div id='notFound' class='uk-alert uk-alert-warning uk-text-bold'>
        Warning: Variations have been changed - pricing has been reset!
      </div>
      <div>You can restore old prices manually. Note that this table will only be shown until the next save happens.</div>
      $table";
  }

  /**
   * Render the Inputfield
   * @return string
   */
  public function ___render()
  {
    $page = $this->wire->pages->get($this->wire->input->get->id);
    $variations = json_encode($page->getVariationsArray());
    $data = json_encode($page->getPricingDataArray());

    $out = parent::___render();
    $grid = "grid_{$this->name}";
    return $this->oldPricing($page) . "
      <div id='set-prices' class='uk-margin-small-bottom uk-flex uk-flex-middle uk-flex-right'>
        <span class='uk-margin-small-right'>Set price on all visible rows</span>
        <input type='text' name='net' placeholder='net' class='uk-input uk-form-small' style='width: 100px;'>
        <input type='text' name='gross' placeholder='gross' class='uk-input uk-form-small' style='width: 100px;'>
      </div>
      <div id='$grid'></div>
      <script>RcPricing.init('#$grid', $variations, $data)</script>
      <div hidden>$out</div>";
  }

  public function renderReady(?Inputfield $parent = null, $renderValueMode = false)
  {
    rockmigrations()->addScripts(__DIR__ . "/../RockGrid/tabulator/tabulator.min.js");
    rockmigrations()->addStyles(__DIR__ . "/../RockGrid/tabulator/tabulator.min.css");
    $this->wire->config->js('RockCommerce', rockcommerce()->getJsConfig());
    parent::renderReady($parent, $renderValueMode);
  }
}
