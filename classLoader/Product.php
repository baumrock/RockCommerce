<?php

namespace RockCommerce;

use ProcessWire\FieldsetPage;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\Page;
use ProcessWire\RepeaterPageArray;
use ProcessWire\RockCommerce;
use ProcessWire\WireData;
use RockGrid\RockGridField;
use RockMoney\Money;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

trait Product
{
  public $isProduct = true;

  public function _init(): void
  {
    wire()->addHookProperty('Page::variationRepeaterLabel', $this, 'variationRepeaterLabel');
    wire()->addHookAfter('ProcessPageEdit::buildForm',      $this, '_buildForm');
  }

  protected function _buildForm(HookEvent $event): void
  {
    $form = $event->return;

    // are variations disabled? then hide the field
    if ($f = $form->get(RockCommerce::field_variations)) {
      $f->collapsed = rockcommerce()->noComplexPricing
        ? Inputfield::collapsedHidden
        : Inputfield::collapsedNo;
    }
  }

  public function discountPage(): DiscountPage|false
  {
    return rockcommerce()->discounts()->getActiveDiscountPage($this);
  }

  public function ___maxamount(): int
  {
    return 10;
  }

  public function ___minamount(): int
  {
    return 1;
  }

  public function price()
  {
    return $this->getFormatted(RockCommerce::field_net);
  }

  public function ___priceNet($variationString = ''): Money
  {
    // get base price
    $price = $this->getFormatted(RockCommerce::field_net);

    // get variation prices
    // this is the data selected in the RockGrid by page admins
    $prices = $this->variationPriceData();
    // bdb($prices);

    // add selected variation options to base price
    // this is the data selected by the user
    $chosen = rockcommerce()->variationArray($variationString);
    // bdb($chosen);
    foreach ($chosen as $varID => $options) {
      $optionPrices = $prices->get($varID);
      foreach ($options as $optionID) {
        $price = $price->plus($optionPrices->get($optionID));
      }
    }

    return $price;
  }

  public function productOptionsString(): string
  {
    $arr = [];
    foreach ($this->variations(true) as $variation) {
      if (!$variation instanceof ProductVariation) continue;
      $str = $variation->id;
      $options = $variation->getFormatted(RockCommerce::field_variation);
      // $str .= ':' . trim(json_encode($options->selected), "[]");
      $arr[] = $str;
    }
    return implode("|", $arr);
  }

  public function rcAttributes(): string
  {
    $discountAttrs = '';
    $dp = $this->discountPage();
    if ($dp) $discountAttrs = $dp->attrs();
    return rockcommerce()->attrs('RcProduct', "
      x-data='RcProduct'
      rc-pid=$this
      rc-price='{$this->price()->getFloat()}'
      rc-minamount='{$this->minamount()}'
      rc-maxamount='{$this->maxamount()}'
      $discountAttrs
    ");
  }

  public function taxrate(): float
  {
    return rockcommerce()->taxrate($this);
  }

  /**
   * Get array of all available variation prices
   *
   * Example:
   * [
   *   1234 => 100,
   *   1235 => 200,
   *   1236 => 300,
   * ]
   *
   * Where 1234, 1235, 1236 are variation ids and values are net prices
   */
  public function variationPriceData(): WireData
  {
    $data = new WireData();
    foreach ($this->variations() as $variation) {
      if (!$variation instanceof ProductVariation) continue;
      $optionPrices = new WireData();
      $grid = $variation->getFormatted(RockCommerce::field_variation);
      $optionPrices->setArray($grid->json->prices ?? []);
      $data->set($variation->id, $optionPrices);
    }
    return $data;
  }

  /**
   * Add selected options to repeater label
   * eg "Colour (3/10)"
   */
  protected function variationRepeaterLabel(HookEvent $event): void
  {
    /** @var Page $page */
    $page = $event->object;
    try {
      $var = $page->getFormatted(RockCommerce::field_variation);
      if (!$var instanceof RockGridField) return;

      $forField = wire()->fields->get(RockCommerce::field_variationoptions)->id;
      $hasParent = wire()->pages->get(2)->url . "repeaters/for-field-$forField";
      $options = $var->getSelectedPages([
        'has_parent' => $hasParent,
        'include' => 'all',
      ]);
      if (!$options->count()) return;
      $option = $options->first();
      $variation = $option->getForPage();
      $all = $variation->getFormatted(RockCommerce::field_variationoptions)->count();
      $event->return = $variation->title . " ({$options->count()}/$all)";
    } catch (\Throwable $th) {
      $event->return = $th->getMessage();
    }
  }

  /**
   * Returns null if variations feature is disabled or variations field is not
   * added to the product template.
   */
  public function variations(): RepeaterPageArray|array
  {
    return $this->getFormatted(RockCommerce::field_variations) ?: [];
  }
}
