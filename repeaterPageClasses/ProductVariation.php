<?php

namespace RockCommerce;

use ProcessWire\RepeaterPage;
use ProcessWire\WireArray;
use ProcessWire\WireData;
use RockGrid\RockGridField;

use function ProcessWire\rockcommerce;

class ProductVariation extends RepeaterPage
{
  const field = 'rc_product_variations';

  public function comment(): string
  {
    return $this->getFormatted(ProductPage::field_variationcomment);
  }

  public function minoptions(): int
  {
    return (int)$this->getFormatted(ProductPage::field_minoptions);
  }

  public function maxoptions(): int
  {
    return (int)$this->getFormatted(ProductPage::field_maxoptions);
  }

  public function options()
  {
    $grid = $this->getFormatted(ProductPage::field_variation);
    if (!$grid instanceof RockGridField) return;
    $options = [];
    $json = $grid->json ?: new WireData();
    $prices = $json->prices;
    if (!is_array($prices)) $prices = [];

    $sorted = false;
    foreach ($json->selected as $id) {
      $p = new VariationOption($id);
      $p->product = $this;
      $p->title = $p->option->title;
      $p->setPrice($id, $prices);

      // get sort order from variation repeater options
      if (!$sorted) $sorted = $p->variation->options()->each('id');

      $key = array_search($id, $sorted);
      $options[$key] = $p;
    }
    ksort($options);
    return $this->wire(new WireArray())->import($options);
  }

  /**
   * AlpineJS data attributes
   * @return string
   */
  public function rcAttributes(): string
  {
    return rockcommerce()->attrs('rcvariation', "
      rc-pid={$this}
      rc-minoptions={$this->minoptions()}
      rc-maxoptions={$this->maxoptions()}
    ");
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
