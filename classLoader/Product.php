<?php

namespace RockCommerce;

use ProcessWire\FieldtypeFieldsetOpen;
use ProcessWire\FieldtypeRockGrid;
use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use RockGrid\RockGridField;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;
use function ProcessWire\wire;

trait Product
{
  public $isProduct = true;
  const _prefix = "rc_product_";

  const field_rctab                 = self::_prefix . "rctab";
  const field_canbebought           = self::_prefix . "canbebought";
  const field_variation             = self::_prefix . "variation";
  const field_variations            = self::_prefix . "variations";
  const field_maxoptions            = self::_prefix . "maxoptions";
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

    if ($f = $form->get('rc_product_variations')) {
      $form->insertAfter([
        'type' => 'markup',
        'label' => 'Accessories',
        'icon' => 'plus',
        'value' => 'TBD',
        'collapsed' => Inputfield::collapsedBlank,
      ], $f);
    }
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
      self::field_maxoptions => [
        'type' => 'integer',
        'label' => 'Maximum number of selectable options',
        'icon' => 'list-ol',
        'collapsed' => Inputfield::collapsedBlank,
      ],
      self::field_variations => [
        'label' => 'Variations',
        'icon' => 'cubes',
        'type' => 'FieldtypeRepeater',
        'fields' => [
          self::field_variationcomment,
          self::field_variation,
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
      if ($k == self::field_variationcomment) continue;
      if ($k == self::field_maxoptions) continue;

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
}
