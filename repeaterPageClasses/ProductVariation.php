<?php

namespace RockCommerce;

use ProcessWire\PageArray;
use ProcessWire\RepeaterPage;
use ProcessWire\WireArray;
use ProcessWire\WireData;
use RockGrid\RockGridField;

class ProductVariation extends RepeaterPage
{
  const field = 'rc_product_variations';

  public function options()
  {
    $grid = $this->getFormatted(ProductPage::field_variation);
    if (!$grid instanceof RockGridField) return;
    $options = $this->wire(new WireArray());
    $json = $grid->json ?: new WireData();
    $prices = $json->prices;
    if (!is_array($prices)) $prices = [];
    foreach ($json->selected as $id) {
      $p = new VariationOption();
      $p->product = $this;
      $p->option = $this->wire->pages->get($id);
      $p->title = $p->option->title;
      $p->setPrice($id, $prices);
      $options->add($p);
    }
    return $options;
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
