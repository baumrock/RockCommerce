<?php

namespace RockCommerce;

use RockGrid\Grid;

class ProductItems extends Grid
{
  public function buildUI(): void
  {
    $this->buttonsLeft->add([
      'label' => 'foo',
      'class' => 'uk-button-primary',
    ])->add([
      'icon' => 'plus',
      'class' => 'uk-button-secondary',
    ]);
    $this->buttonsRight->add([
      'name' => 'refresh',
      'icon' => 'refresh',
    ]);
    $this->buttonsRight->add([
      'name' => 'trash',
      'icon' => 'trash',
      'tooltip' => 'Trash selected rows',
      'class' => '',
    ]);
  }

  public function getData()
  {
    $data = $this->wire->pages->findRaw("id>0", [
      'id',
      'title',
      'modified',
    ], [
      'nulls' => true,
      'indexed' => false,
    ]);
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
