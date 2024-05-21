<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\FieldtypeRockGrid;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
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
  const field_discounts             = self::_prefix . "discounts";

  public function _init(): void
  {
    wire()->addHookAfter('ProcessPageEdit::buildForm', $this, '_hookBuildForm');
  }

  protected function _hookBuildForm(HookEvent $event): void
  {
    return;
    $form = $event->return;
    $pricing = $form->get(self::field_pricing);
    $p = $event->process->getPage();
    if (!$p->isProduct) return;

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
        'grid' => 'RockCommerce\SelectVariations',
      ],
      self::field_variationcomment => [
        'type' => 'textarea',
        'label' => 'Comment',
        'rows' => 5,
        'icon' => 'comment-o',
      ],
      self::field_variationchangesprice => [
        'type' => 'checkbox',
        'label' => 'Changes Price',
      ],
      self::field_variations => [
        'label' => 'Variations',
        'icon' => 'cubes',
        'type' => 'FieldtypeRepeater',
        'fields' => [
          self::field_variation => [
            'columnWidth' => 100,
          ],
          self::field_variationchangesprice => [
            'columnWidth' => 100,
          ],
          self::field_variationcomment,
        ],
        'repeaterTitle' => '{' . self::field_variationcomment . '}',
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

      self::field_discounts => [
        'type' => 'text',
        'label' => 'Discounts',
        'icon' => 'percent',
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
      if ($k == self::field_variationchangesprice) continue;

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
