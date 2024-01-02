<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldSelect;
use RockMoney\Money;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;
use function ProcessWire\wire;

trait Product
{
  public $isProduct = true;

  const _prefix = "rc_product_";

  const field_variations = self::_prefix . "variations";
  const field_options = self::_prefix . "options";
  const field_rctab = self::_prefix . "rctab";
  const field_net = self::_prefix . "net";
  const field_taxrate = self::_prefix . "taxrate";
  const field_gross = self::_prefix . "gross";
  const field_pricing = self::_prefix . "pricing";
  const field_simplevariations = self::_prefix . "simplevariations";
  const field_simpleoptions = self::_prefix . "simpleoptions";

  public function _init(): void
  {
    wire()->addHookBefore("InputfieldFloat::render", $this, "hookTaxrate");
    wire()->addHookAfter("Pages::saveReady", $this, "hookSaveReady");
    wire()->addHookAfter("ProcessPageEdit::buildForm", $this, "hookBuildForm");
  }

  public function getVariationsArray(): array
  {
    return [
      'demo' => [
        'foo' => 'Foo Variation',
        'bar' => 'Bar Variation',
      ],
      'auszug-hinten' => [
        'ohne' => 'Ohne Auszug',
        '41x91' => 'Heckauszug 41 x 91 cm',
      ],
      'auszug-seitlich' => [
        'ohne' => 'Ohne Auszug',
        '81x61' => 'Heckauszug 81 x 61 cm',
      ],
    ];
  }

  public function getDataArray(): array
  {
    return [
      ['demo' => 'foo', 'auszug-hinten' => 'ohne', 'auszug-seitlich' => 'ohne'],
      ['demo' => 'foo', 'auszug-hinten' => 'ohne', 'auszug-seitlich' => '81x61'],
      ['demo' => 'foo', 'auszug-hinten' => '41x91', 'auszug-seitlich' => 'ohne'],
      ['demo' => 'foo', 'auszug-hinten' => '41x91', 'auszug-seitlich' => '81x61'],
      ['demo' => 'bar', 'auszug-hinten' => 'ohne', 'auszug-seitlich' => 'ohne'],
      ['demo' => 'bar', 'auszug-hinten' => 'ohne', 'auszug-seitlich' => '81x61'],
      ['demo' => 'bar', 'auszug-hinten' => '41x91', 'auszug-seitlich' => 'ohne'],
      ['demo' => 'bar', 'auszug-hinten' => '41x91', 'auszug-seitlich' => '81x61'],
    ];
  }

  public function hookBuildForm(HookEvent $event): void
  {
    $form = $event->return;
    $page = $event->object->getPage();
    if (!$page->isProduct) return;

    // base-price
    rockmigrations()->wrapFields($form, [
      self::field_net,
      self::field_taxrate,
      self::field_gross,
    ], [
      'label' => 'Base-Price',
      'icon' => 'money',
    ]);

    // options
    rockmigrations()->wrapFields($form, [
      self::field_simpleoptions,
      self::field_options,
    ], [
      'label' => 'Options',
      'icon' => 'cubes',
      'collapsed' => $page->simpleoptions(1) . $page->options(1)
        ? Inputfield::collapsedNo
        : Inputfield::collapsedYes,
    ]);

    // variations
    rockmigrations()->wrapFields($form, [
      self::field_simplevariations,
      self::field_variations,
      self::field_pricing,
    ], [
      'label' => 'Variations',
      'icon' => 'cubes',
      'collapsed' => $page->simplevariations(1) . $page->variations(1)
        ? Inputfield::collapsedNo
        : Inputfield::collapsedYes,
    ]);
  }

  public function hookSaveReady(HookEvent $event): void
  {
    $page = $event->arguments('page');
    if (!$page->isProduct) return;

    // update gross
    $net = $page->get(self::field_net);
    if ($net && $net instanceof Money) {
      $tax = $page->get(self::field_taxrate);
      $vat = $net->times($tax / 100);
      $gross = $net->plus($vat);
      $page->set(self::field_gross, $gross);
    }
  }

  public function hookTaxrate(HookEvent $event): void
  {
    $inputfield = $event->object;
    if ($inputfield->name != self::field_taxrate) return;

    $f = new InputfieldSelect();
    $f->name = $inputfield->name;
    $options = rockcommerce()->getTaxrates();
    $default = @array_keys($options)[0];
    $f->value = $inputfield->hasPage->{$inputfield->hasField} ?: $default;
    $f->setOptions($options);
    $f->required = true;

    $event->return = $f->render();
    $event->replace = true;
  }

  public function _migrate(): void
  {
    $rm = rockmigrations();
    $fields = [
      self::field_rctab => [
        'type' => 'FieldsetTabOpen',
        'label' => 'Shop',
        'collapsed' => Inputfield::collapsedYesAjax,
      ],
      self::field_net => [
        'type' => 'RockMoney',
        'label' => 'Net',
        'icon' => '',
        'columnWidth' => 33,
        'required' => true,
      ],
      self::field_taxrate => [
        'type' => 'float',
        'label' => 'Taxrate',
        'icon' => 'percent',
        'required' => true,
        'columnWidth' => 33,
      ],
      self::field_gross => [
        'type' => 'RockMoney',
        'label' => 'Gross',
        'icon' => '',
        'collapsed' => Inputfield::collapsedNoLocked,
        'columnWidth' => 33,
      ],

      self::field_simpleoptions => [
        'type' => 'textarea',
        'label' => 'Simple (only for this product)',
        'rows' => 5,
        'icon' => 'cubes',
        'columnWidth' => 50,
        'notes' => 'Can NOT be filtered.',
      ],
      self::field_options => [
        'type' => 'page',
        'label' => 'Global',
        'derefAsPage' => FieldtypePage::derefAsPageArray,
        'inputfield' => 'InputfieldAsmSelect',
        'findPagesSelector' => "template=" . Variation::tpl,
        'labelFieldName' => 'title',
        'icon' => 'cubes',
        'columnWidth' => 50,
        'notes' => 'Can be filtered.',
      ],

      self::field_simplevariations => [
        'type' => 'textarea',
        'label' => 'Simple (only for this product)',
        'rows' => 5,
        'icon' => 'cubes',
        'columnWidth' => 50,
        'notes' => 'Can NOT be filtered.',
      ],
      self::field_variations => [
        'type' => 'page',
        'label' => 'Global',
        'derefAsPage' => FieldtypePage::derefAsPageArray,
        'inputfield' => 'InputfieldAsmSelect',
        'findPagesSelector' => "template=" . Variation::tpl,
        'labelFieldName' => 'title',
        'icon' => 'cubes',
        'columnWidth' => 50,
        'notes' => 'Can be filtered.',
      ],

      self::field_pricing => [
        'type' => 'textarea',
        'label' => 'Pricing',
        'inputfieldClass' => 'InputfieldRcPricing',
        'rows' => 1,
        'icon' => 'money',
        'textformatters' => [
          'TextformatterEntities',
        ],
      ],
    ];
    foreach ($fields as $v) $v['tags'] = "RockCommerce";
    $rm->createFields($fields);
    $fieldnames = array_keys($fields);
    $rm->addFieldsToTemplate($fieldnames, $this);
    $rm->setFieldOrder($fieldnames, $this);
  }
}
