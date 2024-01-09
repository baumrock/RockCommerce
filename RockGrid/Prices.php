<?php

namespace RockGrid;

use RockCommerce\ProductPage;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmoney;

class Prices extends Grid
{

  public function info()
  {
    return parent::info()->setArray([
      'title' => 'Grid Prices',
      'icon' => 'check',
      'stub' => 'minimal', // you can remove this after setup
    ]);
  }

  public function getButtons()
  {
    // add or modify buttons at the top right of the grid
    return parent::getButtons();
  }

  public function getButtonsCenter()
  {
    // add or modify buttons at the top center of the grid
    return parent::getButtonsCenter();
  }

  public function getButtonsLeft()
  {
    // add or modify buttons at the top left of the grid
    $buttons = parent::getButtonsLeft();
    $buttons->prependMarkup = "Changes will instantly be saved. <div id='loading' class='uk-inline' style='display:none;margin-top:-5px;margin-left:5px;'><div uk-spinner='ratio:0.6'></div></div>";
    return $buttons;
  }

  public function getData()
  {
    $tpl = rockcommerce()->productTemplates;
    $data = $this->findRaw("template=$tpl", [
      'id',
      ProductPage::field_available => 'available',
      'title',
      ProductPage::field_taxrate => 'taxrate',
      ProductPage::field_net => 'net',
      ProductPage::field_gross => 'gross',
      ProductPage::field_pricing => 'pricing',
    ]);

    $p = new ProductPage();
    foreach ($data as $row) {
      $row = $p::priceEditorRow($row);
    }

    return $data;
  }

  /**
   * This method defines who can view the grid
   * By default only superusers can view data. Adjust this to your needs.
   * @return bool
   */
  public function isViewable()
  {
    if ($this->wire->user->isSuperuser()) return true;
    // if($this->wire->user->hasRole('foo')) return true;
    return false;
  }
}
