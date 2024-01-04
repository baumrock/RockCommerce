<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldMarkup;
use ProcessWire\InputfieldSelect;
use ProcessWire\PageArray;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;
use function ProcessWire\wire;

trait Product
{
  public $isProduct = true;

  private $priceTo;
  private $priceFrom;
  private $variationsArray;

  const _prefix = "rc_product_";

  const field_variations = self::_prefix . "variations";
  const field_options = self::_prefix . "options";
  const field_rctab = self::_prefix . "rctab";
  const field_net = self::_prefix . "net";
  const field_taxrate = self::_prefix . "taxrate";
  const field_gross = self::_prefix . "gross";
  const field_pricing = self::_prefix . "pricing";
  const field_customvariations = self::_prefix . "customvariations";
  const field_customoptions = self::_prefix . "customoptions";
  const field_simplepricing = self::_prefix . "simplepricing";
  const field_tonet = self::_prefix . "tonet";
  const field_togross = self::_prefix . "togross";
  const field_available = self::_prefix . "available";
  const field_searchable = self::_prefix . "searchable";
  const field_accessories = self::_prefix . "accessories";

  public function _init(): void
  {
    wire()->addHookBefore("InputfieldFloat::render", $this, "hookTaxrate");
    wire()->addHookAfter("Pages::saveReady", $this, "_hookSaveReady");
    wire()->addHookAfter("ProcessPageEdit::buildForm", $this, "_hookBuildForm");
  }

  /** Frontend API */

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

  public function simplepricing(): bool
  {
    return !!$this->getFormatted(self::field_simplepricing);
  }

  public function taxrate(): float
  {
    return $this->getFormatted(self::field_taxrate) ?: 0;
  }

  public function variations(): PageArray
  {
    return $this->getFormatted(self::field_variations . "[]");
  }

  /** Backend */

  public function customVariations(): array
  {
    $arr = [];
    $lines = explode("\n", (string)$this->getFormatted(self::field_customvariations));
    $catname = false;
    $sanitizer = wire()->sanitizer;
    foreach ($lines as $line) {
      $line = trim($line);

      // skip empty lines
      if (!$line) continue;

      // new category?
      if (str_starts_with($line, "# ")) {
        $str = trim($line, "# ");
        $parts = explode(":", $str, 2);
        $catname = $sanitizer->pageName(@$parts[0]);
        if (!$catname) continue;
        $arr[$catname] = [];
        continue;
      }

      // populate values
      if (!$catname) continue;
      if (!array_key_exists($catname, $arr)) continue;
      $parts = explode(":", $line, 2);
      $key = $sanitizer->pageName(@$parts[0]);
      $val = trim((string)@$parts[1]);
      $arr[$catname][$key] = $val;
    }

    // bd($str);
    return $arr;
  }

  /**
   * Get data array
   * This array is used in tabulator to populate rows.
   * It holds all possible combinations of variation values.
   * Thank you ChatGPT :)
   */
  public function getDataArray(): array
  {
    $rows = [[]];
    $variations = $this->getVariationsArray();

    foreach ($variations as $name => $options) {
      $temp = [];
      foreach ($rows as $row) {
        foreach ($options as $k => $value) {
          $temp[] = array_merge($row, [$name => $k]);
        }
      }
      $rows = $temp;
    }

    return $rows;
  }

  /**
   * Get variations array. This array is used in tabulator to create columns.
   *
   * Example return:
   * array
   *  'auszug-hinten' => array
   *    'ohne' => 'Ohne Auszug'
   *    '41x91' => 'Heckauszug 41 x 91 cm'
   *  'auszug-seitlich' => array
   *    'ohne' => 'Ohne Auszug'
   *    '81x61' => 'Auszug 81 x 61 cm'
   */
  public function getVariationsArray(): array
  {
    if ($this->variationsArray) return $this->variationsArray;
    $arr = [];

    // custom variations
    foreach ($this->customVariations() as $name => $options) {
      $arr[$name] = $options;
    }

    // global variations
    foreach ($this->variations() as $variation) {
      if (!$variation instanceof Variation) continue;
      $arr[$variation->name] = $variation->optionsArray();
    }

    return $this->variationsArray = $arr;
  }

  /**
   * Convert variations array to string used in textarea
   * Helpful for importing custom variations via API.
   *
   * Example array to feed:
   * array
   *   'versions: Versionen' => array
   *     't903s' => 'Travel 903 S'
   *     't903l' => 'Travel 903 L'
   */
  public function getVariationsString(array $variations): string
  {
    $variations = $this->sanitizeVariations($variations);
    $str = "";
    foreach ($variations as $name => $options) {
      $str .= "# $name\n";
      foreach ($options as $k => $v) {
        $str .= "$k: $v\n";
      }
    }
    return $str;
  }

  public function _hookBuildForm(HookEvent $event): void
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
      "<div id='notFoundWarning' class='uk-margin-remove-top uk-text-bold uk-alert uk-alert-warning' hidden>WARNING: See Pricing Field</div>" .
      "<table id='preview' class='uk-table uk-table-small uk-table-striped uk-margin-remove'>" .
      "<tr><td>Net</td><td class='uk-width-expand'>{$page->price()->renderNet()}</td></tr>" .
      "<tr><td>Gross</td><td class='uk-width-expand'>{$page->price()->renderGross()}</td></tr>" .
      "</table>";
    $form->add($preview);

    // pricing
    rockmigrations()->wrapFields($form, [
      self::field_simplepricing => ['columnWidth' => 25],
      self::field_taxrate => ['columnWidth' => 25],
      "preview" => ['columnWidth' => 50],

      self::field_net => [
        'showIf' => self::field_simplepricing . "=1",
      ],

      self::field_customvariations,
      self::field_variations,
      self::field_pricing,
    ], [
      'label' => 'Pricing',
      'icon' => 'money',
    ]);

    // options
    rockmigrations()->wrapFields($form, [
      self::field_customoptions,
      self::field_options,
    ], [
      'label' => 'Options',
      'icon' => 'cubes',
      'collapsed' => $page->customoptions(1) . $page->options(1)
        ? Inputfield::collapsedNo
        : Inputfield::collapsedYes,
    ]);
  }

  public function _hookSaveReady(HookEvent $event): void
  {
    $page = $event->arguments('page');
    if (!$page->isProduct) return;

    // update all prices
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

  /**
   * Sanitize variations array for further use
   */
  public function sanitizeVariations(array $variations): array
  {
    $new = [];
    foreach ($variations as $key => $options) {
      $parts = explode(":", $key, 2);
      $var = $this->wire->sanitizer->pageNameTranslate($parts[0]);
      if (count($parts) > 1) $var = "$var: " . trim($parts[1]);

      $opts = [];
      foreach ($options as $k => $v) {
        $k = $this->wire->sanitizer->pageNameTranslate($k);
        $opts[$k] = $v;
      }
      $new[$var] = $opts;
    }
    return $new;
  }

  /**
   * Set complex prices for this product and save it
   * Example array to feed:
   * [
   *   'size:s' => 600,
   *   'size:m' => 800,
   *   'size:l' => 1200,
   * ]
   */
  public function setPrices(array $prices, float $taxrate = null): void
  {
    $this->of(false);
    $this->set(self::field_simplepricing, 0);
    if ($taxrate !== null) $this->set(self::field_taxrate, $taxrate);
    $this->set(self::field_pricing, json_encode($prices));
    $this->save();
  }

  /**
   * Set simple price for this product and save it
   */
  public function setSimplePrice(float $net, float $taxrate = null): void
  {
    $this->of(false);
    $this->set(self::field_simplepricing, 1);
    if ($taxrate !== null) $this->set(self::field_taxrate, $taxrate);
    $this->set(self::field_net, $net);
    $this->save();
  }

  public function setAvailable(bool $status): void
  {
    $this->setAndSave(self::field_available, $status);
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

      self::field_available => [
        'type' => 'toggle',
        'label' => 'Product is available to buy',
        'formatType' => 0, // integer 0/1
        'labelType' => 0, // yes/no
        'inputfieldClass' => 0, // toggle buttons
        'defaultOption' => 'yes',
        'columnWidth' => 50,
      ],
      self::field_searchable => [
        'type' => 'toggle',
        'label' => 'List product in search results',
        'formatType' => 0, // integer 0/1
        'labelType' => 0, // yes/no
        'inputfieldClass' => 0, // toggle buttons
        'defaultOption' => 'yes',
        'columnWidth' => 50,
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
        'showIf' => '',
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

      self::field_customvariations => [
        'type' => 'textarea',
        'label' => 'Custom Variations',
        'rows' => 5,
        'icon' => 'cubes',
        'columnWidth' => 50,
        'notes' => 'Only for this product. Can NOT be filtered. Can NOT be multilingual.',
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
        'notes' => 'Can be filtered. Can be multilingual.',
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

      self::field_customoptions => [
        'type' => 'textarea',
        'label' => 'Custom (only for this product)',
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

      self::field_accessories => [
        'label' => 'Accessories',
        'type' => 'FieldtypeRepeater',
        'fields' => [
          'title' => [
            'columnWidth' => 70,
            'icon' => 'comment-o',
          ],
          self::field_net => [
            'columnWidth' => 30,
            'icon' => 'money',
            'required' => true,
          ],
        ],
        'repeaterTitle' => '#n: {title} -- {' . self::field_net . '}',
        'familyFriendly' => 1,
        'repeaterDepth' => 0,
        'repeaterAddLabel' => 'Add New Item',
        'columnWidth' => 100,
        'icon' => 'cubes',
        'collapsed' => Inputfield::collapsedBlank,
      ],
    ];
    foreach ($fields as &$v) $v['tags'] = "RockCommerce";
    $rm->createFields($fields);
    $fieldnames = array_keys($fields);
    $rm->addFieldsToTemplate($fieldnames, $this);
    $rm->setFieldOrder($fieldnames, $this);
  }
}
