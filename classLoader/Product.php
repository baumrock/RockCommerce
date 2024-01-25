<?php

namespace RockCommerce;

use ProcessWire\FieldtypeFile;
use ProcessWire\FieldtypePage;
use ProcessWire\FieldtypeTextarea;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldFieldsetOpen;
use ProcessWire\InputfieldMarkup;
use ProcessWire\InputfieldSelect;
use ProcessWire\PageArray;
use ProcessWire\RepeaterPage;
use ProcessWire\WireInput;
use RockMoney\Money;
use stdClass;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;
use function ProcessWire\rockmoney;
use function ProcessWire\wire;

trait Product
{
  public $isProduct = true;

  private $frontendPricing = false;
  private $priceTo;
  private $priceFrom;
  private $variationsArray;

  const _prefix = "rc_product_";

  const acc_custom = 1;
  const acc_product = 0;

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
  const field_accimages = self::_prefix . "accimages";
  const field_accbody = self::_prefix . "accbody";
  const field_acctype = self::_prefix . "acctype";
  const field_relatedproducts = self::_prefix . "relatedproducts";
  const field_optionstab = self::_prefix . "optionstab";
  const field_acctab = self::_prefix . "acctab";
  const field_pricingtab = self::_prefix . "pricingtab";
  const field_vartab = self::_prefix . "vartab";

  public function _init(): void
  {
    wire()->addHookBefore("InputfieldFloat::render", $this, "_hookTaxrate");
    wire()->addHookAfter("Pages::saveReady", $this, "_hookSaveReady");
    wire()->addHookAfter("ProcessPageEdit::buildForm", $this, "_hookBuildForm");
    wire()->addHookAfter("Field::getInputfield", $this, "_hookLimitProductAutocomplete");
    wire()->addHookAfter("ProcessPageListRender::getPageLabel", $this, "addShoppingBag");
  }

  /** Frontend API */

  public function frontendPrice($type = null): string
  {
    if (!$type) $type = rockcommerce()->priceType();
    $price = $this->priceFrom();
    if ($type == "net") return $price->net;
    if ($type == "gross") return $price->gross;
  }

  public function frontendPricing(): string
  {
    if ($this->frontendPricing) return $this->frontendPricing;

    $pricing = [
      'variations' => [],
      'accessories' => [],
    ];

    // variations
    $variations = $this->pricingSleepValue(true);
    foreach ($variations as $variation => $net) {
      $net = rockmoney()->parse($net);
      $gross = $this->getGross($net);
      $pricing['variations'][$variation] = (object)[
        'net' => $net->getString(),
        'gross' => $gross->getString(),
        '_net' => $net->format(),
        '_gross' => $gross->format(),
      ];
    }

    // accessories
    foreach ($this->accessories() as $acc) {
      $net = $acc->net();
      $gross = $acc->gross();
      $pricing['accessories'][$acc->id] = (object)[
        'net' => $net->getString(),
        'gross' => $gross->getString(),
        '_net' => $net->format(),
        '_gross' => $gross->format(),
      ];
    }

    return $this->frontendPricing = json_encode($pricing);
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

  public function related(): PageArray
  {
    return $this->getFormatted(self::field_relatedproducts);
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

  public function addShoppingBag(HookEvent $event): void
  {
    $page = $event->arguments('page');
    if (!$page->isProduct) return;
    $options = $event->arguments(1);

    // are we in a menu or such?
    $noTags = $options && is_array($options) && array_key_exists('noTags', $options) && $options['noTags'];
    if ($noTags) return;

    /** @var ProductPage $page */
    if (!$page->available(1)) return;

    // regular pagelist --> modify label
    $icon = '<svg style="margin-top:-3px;margin-right:5px;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M6.331 8H17.67a2 2 0 0 1 1.977 2.304l-1.255 8.152A3 3 0 0 1 15.426 21H8.574a3 3 0 0 1-2.965-2.544l-1.255-8.152A2 2 0 0 1 6.331 8z"/><path d="M9 11V6a3 3 0 0 1 6 0v5"/></g></svg>';
    $event->return = $icon . $event->return;
  }

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
   * Get calculated gross value from net value using current taxrate
   */
  public function getGross(Money|string $net): Money
  {
    if (is_string($net)) $net = rockmoney()->parse($net);
    $vat = $net->times($this->taxrate() / 100);
    return $net->plus($vat);
  }

  public function getNet(Money|string $gross): Money
  {
    if (is_string($gross)) $gross = rockmoney()->parse($gross);
    return $gross->by(1 + ($this->taxrate() / 100));
  }

  /**
   * Get price editor row for this product
   * This data is returned by AJAX requests coming from Price Editor Tabulator.
   */
  public function getPriceEditorRow(): stdClass
  {
    return $this::priceEditorRow($this->getRowObject());
  }

  /**
   * Get pricing array having variation as keys and price as values
   *
   * Example:
   * [
   *   'size:s|color:red' => 10,
   *   'size:m|color:red' => 15,
   *   'size:l|color:red' => 20,
   * ]
   */
  public function getPricingArray(): array
  {
    $json = $this->pricing(2);
    return (array)json_decode($json);
  }

  /**
   * Get data array
   * This array is used in tabulator to populate rows.
   * It holds all possible combinations of variation values.
   * Thank you ChatGPT :)
   */
  public function getPricingDataArray($flat = false): array
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

    if ($flat) {
      $data = [];
      foreach ($rows as $row => $options) {
        $row = [];
        foreach ($options as $k => $v) $row[] = "$k:$v";
        $data[implode("|", $row)] = "";
      }
      return $data;
    }

    return $rows;
  }

  /**
   * Get row object for the Price Editor Tabulator
   */
  public function getRowObject(): stdClass
  {
    $price = $this->priceFrom();
    return (object)[
      'id' => $this->id,
      'taxrate' => $this->taxrate(2),
      'available' => $this->available(2),
      'net' => $price->net->getString(),
      'gross' => $price->gross->getString(),
      'pricing' => $this->pricing(2),
      '_children' => false,
    ];
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
  public function getCustomVariationsString(array $variations): string
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
   * Get variation-string from input data used for Cartitems
   *
   * Returns something like "size:s|version:x"
   */
  public function getVariationString(WireInput $input): string
  {
    $vars = [];
    foreach ($this->getVariationsArray() as $variation => $options) {
      $allowed = array_keys($options);
      $value = $input->get($variation, $allowed);
      $vars[] = "$variation:$value";
    }
    return implode("|", $vars);
  }

  public function _hookBuildForm(HookEvent $event): void
  {
    $form = $event->return;
    $page = $event->object->getPage();
    if (!$page->isProduct) return;

    // pricing preview
    $tab = $form->get(self::field_pricingtab);
    if ($tab) {
      /** @var InputfieldFieldsetOpen $tab */
      $preview = new InputfieldMarkup();
      $preview->name = "rockcommerce-preview";
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
      $form->insertAfter($preview, $form->get(self::field_taxrate));

      // pricing
      $fields = [
        self::field_simplepricing => ['columnWidth' => 25],
        self::field_taxrate => ['columnWidth' => 25],
        "rockcommerce-preview" => ['columnWidth' => 50],

        self::field_net => [
          'showIf' => self::field_simplepricing . "=1",
        ],
      ];
      if (rockcommerce()->noComplexPricing) {
        $new = [
          self::field_simplepricing => [
            'collapsed' => Inputfield::collapsedHidden,
          ],
          self::field_net => [
            'columnWidth' => 33,
            'showIf' => "",
          ],
          self::field_taxrate => ['columnWidth' => 33],
          "rockcommerce-preview" => ['columnWidth' => 33],
        ];
        // first merge for field order, second and third for data
        $fields = array_merge($new, $fields, $new);
      }
      foreach ($fields as $name => $settings) {
        $field = $form->get($name);
        if (!$field) continue;
        foreach ($settings as $k => $v) $field->set($k, $v);
      }
    }

    // set collapsed state of options
    if ($f = $form->get(self::field_optionstab)) {
      $f->collapsed = $page->customoptions(1) . $page->options(1)
        ? Inputfield::collapsedNo
        : Inputfield::collapsedYes;
    }

    // set collapsed state of accessories
    if ($f = $form->get(self::field_acctab)) {
      $f->collapsed = $page->accessories(1) . $page->relatedproducts(1)
        ? Inputfield::collapsedNo
        : Inputfield::collapsedYes;
    }
  }

  /**
   * Hook the related product select field to only return actual products
   */
  public function _hookLimitProductAutocomplete(HookEvent $event): void
  {
    $field = $event->object;
    $inputfield = $event->return;
    if ($field->name !== self::field_relatedproducts) return;
    $inputfield->findPagesSelector .= ",template=" . rockcommerce()->productTemplates;
  }

  public function _hookSaveReady(HookEvent $event): void
  {
    $page = $event->arguments('page');
    if ($page instanceof RepeaterPage) {
      $this->_hookSaveReadyRepeater($event);
      return;
    }
    if (!$page->isProduct) return;
    /** @var ProductPage $page */

    // update all prices
    $page->set(self::field_net, $page->priceFrom(false)->net);
    $page->set(self::field_gross, $page->priceFrom(false)->gross);
    $page->set(self::field_tonet, $page->priceTo(false)->net);
    $page->set(self::field_togross, $page->priceTo(false)->gross);

    if ($page->simplepricing()) {
      // reset variations so that price editor does not show old data
      $page->set(self::field_customvariations, '');
      $page->set(self::field_variations, '');
      $page->set(self::field_pricing, '');
    } else {
      $oldPricing = $page->getUnformatted(self::field_pricing);
      $page->set(self::field_pricing, $page->pricingSleepValue());

      // save old pricing if variations changed
      if (
        $page->isChanged(self::field_variations)
        or $page->isChanged(self::field_customvariations)
      ) {
        $page->meta('rc-old-pricing', $oldPricing);
      } else $page->meta()->remove('rc-old-pricing');
    }
  }

  /**
   * Get pricing json with currently set variations
   */
  public function pricingSleepValue($returnArray = false): string|array
  {
    $current = $this->getPricingArray();
    $data = $this->getPricingDataArray(true);
    foreach ($data as $k => &$price) {
      if (!array_key_exists($k, $current)) continue;
      // use rockmoney to make sure we don't get floating comma issues
      $p = rockmoney()->parse($current[$k]);
      $price = $p->getString();
    }
    if ($returnArray) return $data;
    return json_encode($data);
  }

  /**
   * Save actions for accessories repeater
   */
  public function _hookSaveReadyRepeater(HookEvent $event): void
  {
    /** @var RepeaterPage $page */
    $page = $event->arguments('page');
    $field = $page->getForField();
    if ($field->name !== self::field_accessories) return;

    // calc net + gross for this item
    $product = $page->getForPage();
    $net = $page->getFormatted(self::field_net);
    $page->set(self::field_gross, $product->getGross($net));
  }

  /**
   * Turn taxrate field into a select field
   */
  public function _hookTaxrate(HookEvent $event): void
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
   * Return an array of prices ready to be json_encoded
   */
  public function jsonPrices($prices): array
  {
    $money = rockmoney();
    foreach ($prices as &$price) $price = $money->parse($price)->getString();
    return $prices;
  }

  public function _migrate(): void
  {
    $rm = rockmigrations();
    $rm->deleteField(self::_prefix . "accproduct", true);

    // repeater fields
    $rFields = [
      self::field_acctype => [
        'type' => 'toggle',
        'label' => 'Type',
        'formatType' => 0, // integer 0/1
        'labelType' => 100, // custom
        'inputfieldClass' => 0, // toggle buttons
        'defaultOption' => 'yes',
        'yesLabel' => 'Custom',
        'noLabel' => 'Product',
        'columnWidth' => 30,
      ],
      self::field_accbody => [
        'type' => 'textarea',
        'inputfieldClass' => 'InputfieldTinyMCE',
        'contentType' => FieldtypeTextarea::contentTypeHTML,
        'label' => 'Description',
        'rows' => 5,
        'icon' => 'align-left',
        'inlineMode' => true,
        // 'rpb-nolabel' => true, // hide label in backend
        'settingsFile' => '/site/modules/RockMigrations/TinyMCE/simple.json',
        'collapsed' => Inputfield::collapsedBlank,
        'showIf' => '',
      ],
      self::field_accimages => [
        'type' => 'image',
        'label' => 'Images',
        'maxFiles' => 0,
        'descriptionRows' => 1,
        'extensions' => 'jpg jpeg gif png',
        'maxSize' => 3, // max 3 megapixels
        'icon' => 'picture-o',
        'outputFormat' => FieldtypeFile::outputFormatSingle,
        'gridMode' => 'grid', // left, list
        'collapsed' => Inputfield::collapsedBlank,
        'showIf' => '',
      ],
    ];
    foreach ($rFields as &$v) $v['tags'] = "RockCommerce";
    $rm->createFields($rFields);

    // product fields
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

      // pricing tab
      self::field_pricingtab => [
        'type' => 'FieldsetOpen',
        'label' => 'Pricing',
        'icon' => 'money',
      ],
      self::field_simplepricing => [
        'type' => 'toggle',
        'label' => 'Pricing',
        'formatType' => 1, // boolean true/false
        'labelType' => 100, // custom
        'inputfieldClass' => 0, // toggle buttons
        'defaultOption' => 'yes',
        'yesLabel' => 'Simple',
        'noLabel' => 'Advanced',
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
        'icon' => '',
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
        'icon' => 'filter',
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
      self::field_pricingtab . "_END" => [
        'type' => 'FieldsetClose',
      ],

      // variations tab
      self::field_vartab => [
        'type' => 'FieldsetOpen',
        'label' => 'Variations',
        'icon' => 'cubes',
      ],
      self::field_vartab . "_END" => [
        'type' => 'FieldsetClose',
      ],

      // options
      self::field_optionstab => [
        'type' => 'FieldsetOpen',
        'label' => 'Options',
        'icon' => 'cubes',
        'description' => 'Options do not modify the price of the product.',
      ],
      self::field_customoptions => [
        'type' => 'textarea',
        'label' => 'Custom (only for this product)',
        'rows' => 5,
        'icon' => '',
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
        'icon' => 'filter',
        'columnWidth' => 50,
        'notes' => 'Can be filtered.',
      ],
      self::field_optionstab . "_END" => [
        'type' => 'FieldsetClose',
      ],

      // accessories
      self::field_acctab => [
        'type' => 'FieldsetOpen',
        'label' => 'Accessories + Related',
        'icon' => 'cubes',
      ],
      self::field_accessories => [
        'label' => 'Custom',
        'description' => '',
        'notes' => 'Not buyable as single product.',
        'type' => 'FieldtypeRepeater',
        'fields' => [
          'title' => [
            'columnWidth' => 100,
            'icon' => 'comment-o',
            'required' => false,
            'showIf' => '',
          ],
          self::field_net => [
            'columnWidth' => 50,
            'icon' => 'money',
            'required' => false,
            'showIf' => '',
          ],
          self::field_gross => [
            'columnWidth' => 50,
            'icon' => 'money',
            'collapsed' => Inputfield::collapsedNo,
            'showIf' => '',
          ],
          self::field_accbody,
          self::field_accimages,
        ],
        'repeaterTitle' => '{label}',
        'familyFriendly' => 1,
        'repeaterDepth' => 0,
        'columnWidth' => 50,
        'icon' => '',
        'collapsed' => Inputfield::collapsedNo,
      ],
      self::field_relatedproducts => [
        'type' => 'page',
        'label' => 'Related Products',
        'derefAsPage' => FieldtypePage::derefAsPageArray,
        'inputfield' => 'InputfieldPageAutocomplete',
        // this selector will be refined via hook!
        'findPagesSelector' => 'id>0,template!=admin',
        'labelFieldName' => 'title',
        'columnWidth' => 50,
        'showIf' => '',
        'icon' => 'link',
      ],
      self::field_acctab . "_END" => [
        'type' => 'FieldsetClose',
      ],
    ];
    foreach ($fields as &$v) $v['tags'] = "RockCommerce";
    $rm->createFields($fields);
    $fieldnames = array_keys($fields);
    $rm->addFieldsToTemplate($fieldnames, $this);
    $rm->setFieldOrder($fieldnames, $this);
  }

  /**
   * Convert a row object into a row for the Price Editor Tabulator
   */
  public static function priceEditorRow(stdClass $row): stdClass
  {
    $row->available = (int)$row->available;
    $children = false;
    if ($row->pricing and $row->pricing != "[]") {
      $children = [];
      $data = json_decode($row->pricing);
      foreach ($data as $k => $v) {
        $net = rockmoney()->parse($v);
        $taxrate = $row->taxrate ?: 0;
        $gross = $net->times(1 + ($taxrate / 100));
        $children[] = (object)[
          'pageid' => $row->id,
          'variation' => $k,
          'title' => str_replace("|", " | ", $k),
          'net' => $net->getString(),
          'gross' => $gross->getString(),
          'taxrate' => "",
        ];
      }
      if (count($children)) {
        $row->net = "";
        $row->gross = "";
      }
    }
    $row->_children = $children;
    return $row;
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

  public function setAvailable(bool $status): void
  {
    $this->setAndSave(self::field_available, $status);
  }

  public function setComplexPrice(float|Money $net, string $key): array
  {
    $pricing = $this->getPricingArray();
    $pricing[$key] = rockmoney()->parse($net)->getString();
    $newPrices = $this->setPrices($pricing);
    $net = rockmoney()->parse($newPrices[$key]);
    $gross = $this->getGross($net);
    return [
      'net' => $net->getString(),
      'gross' => $gross->getString(),
    ];
  }

  /**
   * Set all prices of complex pricing field for this product and save it
   *
   * This will wipe all all non-existing prices!
   *
   * Example array to feed:
   * [
   *   'size:s' => 600,
   *   'size:m' => 800,
   *   'size:l' => 1200,
   * ]
   */
  public function setPrices(array $prices, float $taxrate = null): array
  {
    $this->of(false);
    $this->set(self::field_simplepricing, 0);
    if ($taxrate !== null) $this->set(self::field_taxrate, $taxrate);
    $newPrices = $this->jsonPrices($prices);
    $this->set(self::field_pricing, json_encode($newPrices));
    $this->save();
    return $newPrices;
  }

  /**
   * Set simple price for this product and save it
   */
  public function setSimplePrice(float|Money $net, float $taxrate = null): array
  {
    $net = rockmoney()->parse($net);
    $this->of(false);
    $this->set(self::field_simplepricing, 1);
    if ($taxrate !== null) $this->set(self::field_taxrate, $taxrate);
    $this->set(self::field_net, $net);
    $this->save();
    return [
      'net' => $net->getString(),
      'gross' => $this->getGross($net)->getString(),
    ];
  }

  public function setTaxrate($taxrate): void
  {
    $this->of(false);
    $this->set(self::field_taxrate, $taxrate);
    $this->save();
  }
}
