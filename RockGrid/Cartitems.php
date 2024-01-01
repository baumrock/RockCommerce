<?php

namespace RockGrid;

use ProcessWire\WireException;
use RockCommerce\Cart;
use RockCommerce\Cartitem;

class Cartitems extends Grid
{

  public function info()
  {
    return parent::info()->setArray([
      'title' => 'Grid Cartitems',
      'icon' => 'check',
      'help' => true, // you can remove this after setup
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
    return parent::getButtonsLeft();
  }

  public function getData()
  {
    // build selector
    $selector = ['template' => Cartitem::tpl];
    $page = $this->wire->pages->get($this->wire->input->get('id', 'int'));
    if ($page instanceof Cart) $selector['parent'] = $page;
    else {
      // we only allow this grid on page edit
      // for superusers we also allow the debug mode
      if (!$this->wire->user->isSuperuser()) throw new WireException("No Access");
    }

    // we use findRaw in this example because it is shipped with the core
    // my recommendation is to use RockFinder3 as it provides a lot more
    // and customized options for working with PW page data in grids
    $data = $this->findRaw($selector, [
      'id',
      Cartitem::field_product . ".title" => 'item',
      Cartitem::field_amount => 'amount',
      'created' => 'added',
    ]);

    // populate item column from page field's title value
    foreach ($data as $row) {
      if (!property_exists($row, Cartitem::field_product)) continue;
      foreach ($row->{Cartitem::field_product} as $id => $item) {
        $row->itemID = $id;
        $row->item = $item['title'];
      }
      unset($row->{Cartitem::field_product});
    }
    bdb($data);

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
