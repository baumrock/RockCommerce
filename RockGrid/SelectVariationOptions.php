<?php

namespace RockCommerce;

use RockGrid\Grid;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

class SelectVariationOptions extends Grid
{
  public function getData()
  {
    $arr = wire()->pages->findRaw(
      [
        'parent' => rockcommerce()->variationsPage(),
        // 'id' => wire()->input->get->var,
      ],
      [
        'title',
        Variation::field_options . '.title',
      ],
      [
        'nulls' => true,
      ]
    );
    return $arr;
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
