<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\FieldtypeRockGrid;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\RepeaterPage;
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
  const field_items                 = self::_prefix . "items";
  const field_discounts             = self::_prefix . "discounts";

  public function _init(): void
  {
    wire()->addHookAfter('ProcessPageEdit::buildForm',      $this, '_hookBuildForm');
    wire()->addHookProperty('Page::variationRepeaterLabel', $this, 'variationRepeaterLabel');
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

  protected function _hookBuildForm(HookEvent $event): void
  {
    $form = $event->return;
    $pricing = $form->get(self::field_pricing);
    $p = $event->process->getPage();
    if (!$p->isProduct) return;
    return;

    $pricing->add([
      'type' => 'markup',
      'label' => 'Base Price Min',
      'value' => 'NET: ' . (new RockMoney())->parse(100)
        . '<br>GROSS: ' . (new RockMoney())->parse(100)->times(1 + $p->taxrate()),
      'columnWidth' => 33,
    ]);
    $pricing->add([
      'type' => 'markup',
      'label' => 'Base Price Max',
      'value' => (new RockMoney())->parse(250),
      'columnWidth' => 33,
    ]);
    $pricing->add([
      'type' => 'markup',
      'label' => 'Rendered Price',
      'value' => 'bar',
      'columnWidth' => 34,
    ]);
    $pricing->add([
      'type' => 'textarea',
      'label' => 'Price Editor',
      'value' => 'bar',
      'icon' => 'table',
    ]);
  }

  public function _migrate(): void
  {
    $rm = rockmigrations();

    // $rm->removeFieldFromTemplate(self::field_variation, $this);

    $f = wire()->fields->get(self::field_variation);
    if ($f->id && !$f->type instanceof FieldtypeRockGrid) {
      $rm->deleteField($f);
    }
    $rm->deleteField(self::field_variationchangesprice);
    $rm->deleteField(self::field_discounts);

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

      self::field_pricing => [
        'type' => 'FieldsetOpen',
        'label' => 'Pricing',
        'icon' => 'money',
      ],

      self::field_items => [
        'type' => 'RockGrid',
        'label' => 'Items',
        'grid' => 'RockCommerce\ProductItems',
      ],

      self::field_pricing . "_END" => [
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

  public function taxrate(): float
  {
    return rockcommerce()->taxrate($this);
  }
}
