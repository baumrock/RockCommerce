<?php

namespace RockCommerce;

use RockGrid\Grid;

use function ProcessWire\rockcommerce;

class SelectVariations extends Grid
{
  public function getData()
  {
    return rockcommerce()->findRawVariationOptions();
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
