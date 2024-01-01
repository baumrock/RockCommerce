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

  public function _init(): void
  {
    wire()->addHookBefore("InputfieldFloat::render", $this, "hookTaxrate");
    wire()->addHookAfter("Pages::saveReady", $this, "hookSaveReady");
    wire()->addHookAfter("ProcessPageEdit::buildForm", $this, "hookBuildForm");
  }

  public function hookBuildForm(HookEvent $event): void
  {
    $form = $event->return;
    $page = $event->object->getPage();
    if (!$page->isProduct) return;

    rockmigrations()->wrapFields($form, [
      self::field_net,
      self::field_taxrate,
      self::field_gross,
    ], [
      'label' => 'Base-Price',
      'icon' => 'money',
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
    $f->value = $inputfield->hasPage->{$inputfield->hasField};
    $f->setOptions(rockcommerce()->getTaxrates());
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
      self::field_variations => [
        'type' => 'page',
        'label' => 'Variations (changing price)',
        'derefAsPage' => FieldtypePage::derefAsPageArray,
        'inputfield' => 'InputfieldAsmSelect',
        'findPagesSelector' => "template=" . Variation::tpl,
        'labelFieldName' => 'title',
        'icon' => 'cubes',
        'columnWidth' => 50,
      ],
      self::field_options => [
        'type' => 'page',
        'label' => 'Options (not changing price)',
        'derefAsPage' => FieldtypePage::derefAsPageArray,
        'inputfield' => 'InputfieldAsmSelect',
        'findPagesSelector' => "template=" . Variation::tpl,
        'labelFieldName' => 'title',
        'icon' => 'cubes',
        'columnWidth' => 50,
      ],
      self::field_pricing => [
        'type' => 'textarea',
        'label' => 'Pricing',
        'inputfieldClass' => 'InputfieldRcPricing',
        'rows' => 1,
        'icon' => 'money',
      ],
    ];
    foreach ($fields as $v) $v['tags'] = "RockCommerce";
    $rm->createFields($fields);
    $fieldnames = array_keys($fields);
    $rm->addFieldsToTemplate($fieldnames, $this);
    $rm->setFieldOrder($fieldnames, $this);
  }
}
