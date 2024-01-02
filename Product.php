<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldMarkup;
use ProcessWire\InputfieldSelect;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;
use function ProcessWire\wire;

trait Product
{
  public $isProduct = true;

  private $priceTo;
  private $priceFrom;

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
  const field_simplepricing = self::_prefix . "simplepricing";
  const field_tonet = self::_prefix . "tonet";
  const field_togross = self::_prefix . "togross";

  public function _init(): void
  {
    wire()->addHookBefore("InputfieldFloat::render", $this, "hookTaxrate");
    wire()->addHookAfter("Pages::saveReady", $this, "hookSaveReady");
    wire()->addHookAfter("ProcessPageEdit::buildForm", $this, "hookBuildForm");
  }

  /** Frontend API */

  public function simplepricing(): bool
  {
    return !!$this->getFormatted(self::field_simplepricing);
  }

  public function price(): Price|PriceMulti
  {
    if ($this->simplepricing()) return $this->priceFrom();
    return $this->prices();
  }

  public function priceFrom($cache = true): Price
  {
    if ($cache && $this->priceFrom) return $this->priceFrom;

    if ($this->simplepricing()) {
      $price = new Price($this->net(), $this->taxrate());
      if ($cache) $this->priceFrom = $price;
      return $price;
    }

    $json = $this->getUnformatted(self::field_pricing) ?: "[]";
    $pricing = json_decode($json, true);
    $min = count($pricing) ? min(array_values($pricing)) : 0;

    $price = new Price($min, $this->taxrate());
    if ($cache) $this->priceFrom = $price;
    return $price;
  }

  public function priceTo($cache = true): Price
  {
    if ($cache && $this->priceTo) return $this->priceTo;

    if ($this->simplepricing()) {
      $price = $this->priceFrom($cache);
      if ($cache) $this->priceTo = $price;
      return $price;
    }

    $json = $this->getUnformatted(self::field_pricing) ?: "[]";
    $pricing = json_decode($json, true);
    $max = count($pricing) ? max(array_values($pricing)) : 0;

    $price = new Price($max, $this->taxrate());
    if ($cache) $this->priceTo = $price;
    return $price;
  }

  public function prices(): PriceMulti
  {
    return new PriceMulti(
      $this->priceFrom(),
      $this->priceTo(),
      $this->taxrate()
    );
  }

  public function taxrate(): float
  {
    return $this->getFormatted(self::field_taxrate) ?: 0;
  }

  /** Backend */

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

    // pricing preview
    $preview = new InputfieldMarkup();
    $preview->name = "preview";
    $preview->label = "Rendered Price";
    $preview->icon = "eye";
    $preview->value = "<style>
        #preview td {padding:2px}
        #preview td:first-child {padding-right:20px}
      </style>" .
      "<table id='preview' class='uk-table uk-table-small uk-table-striped uk-margin-remove'>" .
      "<tr><td>Net</td><td class='uk-width-expand'>{$page->price()->renderNet()}</td></tr>" .
      "<tr><td>Gross</td><td class='uk-width-expand'>{$page->price()->renderGross()}</td></tr>" .
      "</table>";
    $form->add($preview);

    // pricing
    rockmigrations()->wrapFields($form, [
      self::field_taxrate => ['columnWidth' => 20],
      self::field_simplepricing => ['columnWidth' => 30],
      "preview" => ['columnWidth' => 50],

      self::field_net,

      self::field_simplevariations,
      self::field_variations,
      self::field_pricing,
    ], [
      'label' => 'Pricing',
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
  }

  public function hookSaveReady(HookEvent $event): void
  {
    $page = $event->arguments('page');
    if (!$page->isProduct) return;

    $page->set(self::field_net, $page->priceFrom(false)->net);
    $page->set(self::field_gross, $page->priceFrom(false)->gross);
    $page->set(self::field_tonet, $page->priceTo(false)->net);
    $page->set(self::field_togross, $page->priceTo(false)->gross);
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
      self::field_simplepricing => [
        'type' => 'toggle',
        'label' => 'Simple Pricing',
        'formatType' => 1, // boolean true/false
        'labelType' => 0, // yes/no
        'inputfieldClass' => 0, // toggle buttons
        'defaultOption' => 'yes',
      ],
      self::field_taxrate => [
        'type' => 'float',
        'label' => 'Taxrate',
        'icon' => 'percent',
        'required' => true,
        'columnWidth' => 100,
      ],
      self::field_net => [
        'type' => 'RockMoney',
        'label' => 'Net Price',
        'columnWidth' => 100,
        'required' => false,
        'showIf' => self::field_simplepricing . "=1",
      ],
      self::field_gross => [
        'type' => 'RockMoney',
        'label' => 'Gross',
        'collapsed' => Inputfield::collapsedHidden,
        'showIf' => self::field_simplepricing . "=1",
      ],

      self::field_tonet => [
        'type' => 'RockMoney',
        'label' => 'Price To Net',
        'collapsed' => Inputfield::collapsedHidden,
      ],
      self::field_togross => [
        'type' => 'RockMoney',
        'label' => 'Price To Gross',
        'collapsed' => Inputfield::collapsedHidden,
      ],

      self::field_simplevariations => [
        'type' => 'textarea',
        'label' => 'Custom Variations',
        'rows' => 5,
        'icon' => 'cubes',
        'columnWidth' => 50,
        'notes' => 'Only for this product. Can NOT be filtered.',
        'showIf' => self::field_simplepricing . "=0",
        'collapsed' => Inputfield::collapsedNo,
      ],
      self::field_variations => [
        'type' => 'page',
        'label' => 'Global Variations',
        'derefAsPage' => FieldtypePage::derefAsPageArray,
        'inputfield' => 'InputfieldAsmSelect',
        'findPagesSelector' => "template=" . Variation::tpl,
        'labelFieldName' => 'title',
        'icon' => 'cubes',
        'columnWidth' => 50,
        'notes' => 'Can be filtered.',
        'showIf' => self::field_simplepricing . "=0",
        'collapsed' => Inputfield::collapsedNo,
      ],

      self::field_pricing => [
        'type' => 'textarea',
        'label' => 'Pricing',
        'inputfieldClass' => 'InputfieldRcPricing',
        'rows' => 5,
        'icon' => 'money',
        'textformatters' => [
          'TextformatterEntities',
        ],
        'showIf' => self::field_simplepricing . "=0",
        'collapsed' => Inputfield::collapsedNo,
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
    ];
    foreach ($fields as $v) $v['tags'] = "RockCommerce";
    $rm->createFields($fields);
    $fieldnames = array_keys($fields);
    $rm->addFieldsToTemplate($fieldnames, $this);
    $rm->setFieldOrder($fieldnames, $this);
  }
}
