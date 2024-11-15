<?php

namespace RockCommerce;

use ProcessWire\FieldsetPage;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RepeaterPageArray;
use ProcessWire\RockCommerce;
use ProcessWire\RockCommerceFields;
use ProcessWire\WireData;
use RockGrid\RockGridField;
use RockMoney\Money;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;
use function ProcessWire\wire;

trait Product
{
  public $isProduct = true;
  const _prefix = "rc_product_";

  const field_rctab                 = self::_prefix . "rctab";
  const field_variation             = self::_prefix . "variation";
  const field_variations            = self::_prefix . "variations";
  const field_maxoptions            = self::_prefix . "maxoptions";
  const field_minoptions            = self::_prefix . "minoptions";
  const field_variationcomment      = self::_prefix . "variationcomment";
  const field_price                 = self::_prefix . "price";

  public function _init(): void
  {
    wire()->addHookAfter('ProcessPageEdit::buildForm',      $this, '_hookBuildForm');
    wire()->addHookProperty('Page::variationRepeaterLabel', $this, 'variationRepeaterLabel');
    wire()->addHookAfter('Inputfield::render',              $this, 'preloadRockGrid');
  }

  protected function _hookBuildForm(HookEvent $event): void
  {
    $form = $event->return;
    if (!$form instanceof InputfieldWrapper) return;

    $p = $event->process->getPage();
    if (!$p->isProduct) return;

    // if ($f = $form->get('rc_product_variations')) {
    //   $form->insertAfter([
    //     'type' => 'markup',
    //     'label' => 'Accessories',
    //     'icon' => 'plus',
    //     'value' => 'TBD',
    //     'collapsed' => Inputfield::collapsedBlank,
    //   ], $f);
    // }
  }

  public function custom(): FieldsetPage
  {
    return $this->getFormatted(RockCommerce::field_productfields);
  }

  public function maxamount(): int
  {
    return rockcommerce()->maxamount;
  }

  public function _migrate(): void
  {
    $rm = rockmigrations();

    // product fields
    $fields = [
      self::field_rctab => [
        'type' => 'FieldsetTabOpen',
        'label' => 'Shop',
        'collapsed' => Inputfield::collapsedYesAjax,
      ],
      self::field_price => [
        'type' => 'RockMoney',
        'label' => 'Net Price',
        'icon' => 'money',
      ],
      self::field_variation => [
        'type' => 'RockGrid',
        'label' => 'Variations',
        'grid' => 'RockCommerce\ProductVariations',
        'notes' => 'You can use "p" in the price column, eg 2500-p',
      ],
      self::field_variationcomment => [
        'type' => 'textarea',
        'label' => 'Comment',
        'rows' => 5,
        'icon' => 'comment-o',
        'collapsed' => Inputfield::collapsedBlank,
      ],
      self::field_minoptions => [
        'type' => 'integer',
        'label' => 'Minimum number of selected options',
        'icon' => 'list-ol',
        'collapsed' => Inputfield::collapsedBlank,
        'columnWidth' => 50,
        'notes' => 'Empty means no minimum',
      ],
      self::field_maxoptions => [
        'type' => 'integer',
        'label' => 'Maximum number of selectable options',
        'icon' => 'list-ol',
        'collapsed' => Inputfield::collapsedBlank,
        'columnWidth' => 50,
        'notes' => 'Empty means 1',
      ],
      self::field_variations => [
        'label' => 'Variations',
        'icon' => 'cubes',
        'type' => 'FieldtypeRepeater',
        'fields' => [
          self::field_variationcomment,
          self::field_variation,
          self::field_minoptions,
          self::field_maxoptions,
        ],
        'repeaterTitle' => '{variationRepeaterLabel}',
        'familyFriendly' => 1,
        'repeaterDepth' => 0,
        'repeaterCollapse' => '0', // New items open, existing items collapsed (recommended for most cases)
        'repeaterAddLabel' => 'Add New Variation',
        'columnWidth' => 100,
      ],
    ];
    foreach ($fields as &$v) $v['tags'] = "RockCommerce";
    $rm->createFields($fields);

    $fieldnames = [];
    foreach ($fields as $k => $v) {
      // don't add some fields to the template
      if ($k == self::field_variation) continue;
      if ($k == self::field_maxoptions) continue;
      if ($k == self::field_minoptions) continue;
      if ($k == self::field_variationcomment) continue;

      $rm->addFieldToTemplate($k, $this);
      $fieldnames[] = $k;
    }
    $rm->setFieldOrder($fieldnames, $this);
  }

  public function minamount(): int
  {
    return rockcommerce()->minamount;
  }

  /**
   * // TODO Replace this with a proper solution
   * See https://processwire.com/talk/topic/30045-inputfieldrenderready/
   * @param HookEvent $event
   * @return void
   */
  protected function preloadRockGrid(HookEvent $event): void
  {
    $f = $event->object;
    if ($f->name !== self::field_variations) return;
    // bd($f->name, 'preload rockgrid');
    foreach ($f->hasField->fields as $fname) {
      $field = wire()->fields->get($fname);
      $inputfield = $field->getInputfield($event->process->getPage());
      $inputfield->renderReady();
    }
  }

  public function price()
  {
    return $this->getFormatted(self::field_price);
  }

  public function ___priceNet($variationString = ''): Money
  {
    // get base price
    $price = $this->getFormatted(self::field_price);

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
      $options = $variation->getFormatted(self::field_variation);
      // $str .= ':' . trim(json_encode($options->selected), "[]");
      $arr[] = $str;
    }
    return implode("|", $arr);
  }

  public function rcAttributes(): string
  {
    return rockcommerce()->attrs('RcProduct', "
      x-data='RcProduct'
      rc-pid=$this
      rc-price='{$this->price()->getFloat()}'
      rc-minamount='{$this->minamount()}'
      rc-maxamount='{$this->maxamount()}'
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
      $grid = $variation->getFormatted(self::field_variation);
      $optionPrices->setArray($grid->json->prices ?? []);
      $data->set($variation->id, $optionPrices);
    }
    return $data;
  }

  protected function variationRepeaterLabel(HookEvent $event): void
  {
    /** @var Page $page */
    $page = $event->object;
    try {
      $var = $page->getFormatted(self::field_variation);
      if (!$var instanceof RockGridField) return;
      $options = $var->getSelectedPages();
      if (!$options->count()) return;
      $option = $options->first();
      $variation = $option->getForPage();
      $all = $variation->getFormatted(Variation::field_options)->count();
      $event->return = $variation->title . " ({$options->count()}/$all)";
    } catch (\Throwable $th) {
      $event->return = $th->getMessage();
    }
  }

  public function variations(): RepeaterPageArray
  {
    return $this->getFormatted(self::field_variations);
  }
}
