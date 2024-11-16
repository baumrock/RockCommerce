<?php

namespace RockCommerce;

use ProcessWire\RepeaterPage;
use ProcessWire\RockCommerce;
use ProcessWire\WireArray;
use ProcessWire\WireData;
use RockGrid\RockGridField;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

class ProductVariation extends RepeaterPage
{
  const field = 'rockcommerce_variations';

  public function comment(): string
  {
    return $this->getFormatted(RockCommerce::field_variationcomment);
  }

  public function minoptions(): int
  {
    return (int)$this->getFormatted(RockCommerce::field_min);
  }

  public function maxoptions(): int
  {
    return (int)$this->getFormatted(RockCommerce::field_max);
  }

  /**
   * Get options of this variation
   * @return WireArray|null
   */
  public function options()
  {
    $grid = $this->getFormatted(RockCommerce::field_variation);
    if (!$grid instanceof RockGridField) return;
    $options = [];
    $json = $grid->json ?: new WireData();

    $sorted = false;
    $selected = $json->selected ?: [];
    foreach ($selected as $id) {
      $p = new VariationOption($id);
      $p->product = $this;
      $p->title = $p->option->title;
      $p->wakeup($json, $id);

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
    return rockcommerce()->attrs('RcVariation', "
      rc-pid={$this}
      rc-minoptions={$this->minoptions()}
      rc-maxoptions={$this->maxoptions()}
    ");
  }

  public function title()
  {
    $grid = $this->getFormatted(RockCommerce::field_variation);
    if (!$grid instanceof RockGridField) return;

    $forField = wire()->fields->get(RockCommerce::field_variationoptions)->id;
    $hasParent = wire()->pages->get(2)->url . "repeaters/for-field-$forField";
    $first = $grid->getSelectedPages([
      'has_parent' => $hasParent,
      'include' => 'all',
    ])->first();
    if (!$first instanceof RepeaterPage) return;

    return $first->getForPage()->title;
  }
}
