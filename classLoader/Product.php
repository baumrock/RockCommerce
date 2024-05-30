<?php

namespace RockCommerce;

use ProcessWire\FieldtypeFieldsetOpen;
use ProcessWire\FieldtypeRockGrid;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\RepeaterPage;
use ProcessWire\RepeaterPageArray;
use ProcessWire\RockMoney;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;
use function ProcessWire\wire;

trait Product
{
  public $isProduct = true;
  const _prefix = "rc_product_";

  const field_rctab                 = self::_prefix . "rctab";
  const field_canbebought           = self::_prefix . "canbebought";
  const field_variations            = self::_prefix . "variations";
  const field_variation             = self::_prefix . "variation";
  const field_variationcomment      = self::_prefix . "variationcomment";
  const field_variationchangesprice = self::_prefix . "variationchangesprice";
  const field_pricing               = self::_prefix . "pricing";
  const field_pricingset            = self::_prefix . "pricingset";
  const field_discounts             = self::_prefix . "discounts";
  const field_pricingvars           = self::_prefix . "pricingvars";

  public function _init(): void
  {
    wire()->addHookAfter('ProcessPageEdit::buildForm',      $this, '_hookBuildForm');
    wire()->addHookProperty('Page::variationRepeaterLabel', $this, 'variationRepeaterLabel');
    wire()->addHookAfter('Inputfield::render',              $this, 'preloadRockGrid');
  }

  protected function _hookBuildForm(HookEvent $event): void
  {
    $form = $event->return;
    $p = $event->process->getPage();
    if (!$p->isProduct) return;
    $config = $this->wire->config;

    $pricingvars = $form->get(self::field_pricingvars);
    if ($config->rockcommerceDebug) {
      $pricingvars->prependMarkup("
        <div class='uk-margin-small'>
          <div>productOptionsString: {$p->productOptionsString()}</div>
          <div>pricingOptionsString: ...</div>
        </div>
      ");
    }

    $pricing = $form->get(self::field_pricing);
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

  public function _migrate(): void
  {
    $rm = rockmigrations();

    // $rm->removeFieldFromTemplate(self::field_variation, $this);

    $f = wire()->fields->get(self::field_variation);
    if ($f && !$f->type instanceof FieldtypeRockGrid) {
      $rm->deleteField($f);
    }
    $rm->deleteField(self::field_variationchangesprice);
    $rm->deleteField(self::field_discounts);
    $f = wire()->fields->get(self::field_pricing);
    if ($f && $f->type instanceof FieldtypeFieldsetOpen) {
      $rm->deleteField(self::field_pricing);
      $rm->deleteField(self::field_pricing . '_END');
      $rm->deleteField(self::_prefix . 'items');
    }

    // product fields
    $fields = [
      self::field_rctab => [
        'type' => 'FieldsetTabOpen',
        'label' => 'Shop',
        'collapsed' => Inputfield::collapsedYesAjax,
      ],

      self::field_canbebought => [
        'type' => 'checkbox',
        'label' => 'Product can be bought',
      ],

      self::field_variation => [
        'type' => 'RockGrid',
        'label' => 'Variations',
        'grid' => 'RockCommerce\ProductVariations',
      ],
      self::field_variationcomment => [
        'type' => 'textarea',
        'label' => 'Comment',
        'rows' => 5,
        'icon' => 'comment-o',
        'collapsed' => Inputfield::collapsedBlank,
      ],
      self::field_variations => [
        'label' => 'Variations',
        'icon' => 'cubes',
        'type' => 'FieldtypeRepeater',
        'fields' => [
          self::field_variationcomment,
          self::field_variation,
        ],
        'repeaterTitle' => '{variationRepeaterLabel}',
        'familyFriendly' => 1,
        'repeaterDepth' => 0,
        'repeaterCollapse' => '0', // New items open, existing items collapsed (recommended for most cases)
        'repeaterAddLabel' => 'Add New Variation',
        'columnWidth' => 100,
      ],
      self::field_pricingset => [
        'type' => 'FieldsetOpen',
        'label' => 'Pricing',
        'icon' => 'money',
      ],
      self::field_pricingvars => [
        'type' => 'text',
        'label' => 'Variations changing the price',
        'icon' => 'check-square-o',
      ],
      self::field_pricing => [
        'type' => 'RockGrid',
        'label' => 'Pricing',
        'grid' => 'RockCommerce\ProductPricing',
        'icon' => 'table',
      ],
      self::field_pricingset . "_END" => [
        'type' => 'FieldsetClose',
      ],
    ];
    foreach ($fields as &$v) $v['tags'] = "RockCommerce";
    $rm->createFields($fields);

    $fieldnames = [];
    foreach ($fields as $k => $v) {
      // don't add some fields to the template
      if ($k == self::field_variation) continue;
      if ($k == self::field_variationcomment) continue;

      $rm->addFieldToTemplate($k, $this);
      $fieldnames[] = $k;
    }
    $rm->setFieldOrder($fieldnames, $this);
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
    foreach ($f->hasField->fields as $fname) {
      $field = wire()->fields->get($fname);
      $inputfield = $field->getInputfield($event->process->getPage());
      $inputfield->renderReady();
    }
  }

  public function taxrate(): float
  {
    return rockcommerce()->taxrate($this);
  }

  protected function variationRepeaterLabel(HookEvent $event): void
  {
    $page = $event->object;
    try {
      $variations = json_decode($page->get(self::field_variation));
      if (!is_object($variations)) return;
      if (!is_array($variations->selected)) return;
      if (!$cnt = count($variations->selected)) return;
      $first = $variations->selected[0];
      $option = wire()->pages->get($first);
      if (!$option instanceof RepeaterPage) return;
      $event->return = $option->getForPage()->title . " ({$cnt}x)";
    } catch (\Throwable $th) {
      $event->return = $th->getMessage();
    }
  }

  public function variations($raw = false): RepeaterPageArray
  {
    $arr = $this->getFormatted(self::field_variations);
    if ($raw) return $arr;
    $vars = new VariationsArray(
      $arr->getForPage(),
      $arr->getForField()
    );
    $vars->import($arr);
    $vars->raw = $arr;
    return $vars;
  }
}
