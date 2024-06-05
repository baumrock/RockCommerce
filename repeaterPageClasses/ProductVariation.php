<?php

namespace RockCommerce;

use ProcessWire\RepeaterPage;
use ProcessWire\RepeaterPageArray;
use RockGrid\RockGridField;

class ProductVariation extends RepeaterPage
{
  const field = 'rc_product_variations';

  public $variationTitle = 'foo bar';

  public function options()
  {
    $grid = $this->getFormatted(ProductPage::field_variation);
    if (!$grid instanceof RockGridField) return;
    return $grid->getSelectedPages();
  }

  public function title()
  {
    $grid = $this->getFormatted(ProductPage::field_variation);
    if (!$grid instanceof RockGridField) return;
    $first = $grid->getSelectedPages()->first();
    if (!$first instanceof RepeaterPage) return;
    return $first->getForPage()->title;
  }
}
