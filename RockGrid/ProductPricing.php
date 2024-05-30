<?php

namespace RockCommerce;

use RockGrid\Grid;

class ProductPricing extends Grid
{
  public function getData()
  {
    return $this->wire->pages->findRaw("id>0, limit=10", [
      'id',
      'title',
      'modified',
    ], [
      'nulls' => true,
      'indexed' => false,
    ]);
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
