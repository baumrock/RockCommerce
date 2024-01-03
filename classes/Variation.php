<?php

namespace RockCommerce;

use ProcessWire\HookEvent;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

use function ProcessWire\wire;

class Variation extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_variation";
  const prefix = "rockcommerce_variation_";

  const field_options = self::prefix . "options";
  const field_id = self::prefix . "id";

  private $optionsArray;

  public function init(): void
  {
    wire()->addHookAfter("Pages::saveReady", $this, "hookRepeaterSave");
    wire()->addHookAfter("Inputfield::render", $this, "lockIdField");
  }

  public function lockIdField(HookEvent $event): void
  {
    $inputfield = $event->object;
    if (!$field = $inputfield->hasField) return;
    if ($field->name != self::field_id) return;
    if (!$page = $inputfield->hasPage) return;
    if (!$inputfield->value) return;

    // lock field only if it is not newer than 2 minutes
    if ($page->created > time() - RockMigrations::oneMinute * 2) return;

    // field must not be editable
    $event->return = $inputfield->value .
      "<input type=hidden name={$inputfield->name} value={$inputfield->value} />";
  }

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    if ($name = $form->get('_pw_page_name')) {
      $name->label = "Variation Name (ID)";
      $name->appendMarkup = "<style>#Inputfield__pw_page_name_path {display:none;}</style>";
      $name->columnWidth = 50;
      if ($num = $this->hasReferences) {
        $name->collapsed = Inputfield::collapsedNoLocked;
        $name->notes = "Variation is used by $num pages - ID can't be changed.";
      }
    }

    // add API hint to title field
    if ($f = $form->get('title')) {
      $f->notes = "API: \$rockcommerce->variations('{$this->name}')->title";
      $f->columnWidth = 50;
      $form->remove($f);
      $form->insertBefore($f, $name);
    }
  }

  public function onCreate(): void
  {
    $this->status = 1;
  }

  /** frontend */

  /**
   * Get label for this option
   * Falls back to ID if title is not set.
   */
  public function label(): string
  {
    return $this->title ?: $this->variationID();
  }

  public function variationID(): string
  {
    return $this->getFormatted(self::field_id);
  }

  public function optionsArray(): array
  {
    if ($this->optionsArray) return $this->optionsArray;
    $arr = [];
    foreach ($this->options(1) as $option) {
      $name = $option->getFormatted(self::field_id);
      $arr[$name] = $option->title;
    }
    return $this->optionsArray = $arr;
  }

  /** backend */

  public function hookRepeaterSave(HookEvent $event): void
  {
    $page = $event->arguments('page');
    if ($page->template != "repeater_" . self::field_options) return;
    $page->set(
      self::field_id,
      $this->wire->sanitizer->pageName($page->get(self::field_id))
    );
  }

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $rm->migrate([
      'fields' => [
        self::field_id => [
          'type' => 'text',
          'label' => 'ID',
          'icon' => 'key',
          'textformatters' => [
            'TextformatterEntities',
          ],
          'notes' => 'Unique Identifier, eg red, blue, s, m, l',
          'required' => true,
        ],
        self::field_options => [
          'label' => 'Options',
          'type' => 'FieldtypeRepeater',
          'fields' => [
            self::field_id => [
              'columnWidth' => 30,
            ],
            'title' => [
              'label' => 'Label',
              'notes' => 'Human readable label',
              'required' => false,
              'columnWidth' => 70,
            ],
          ],
          'repeaterTitle' => '{' . self::field_id . '}',
          'familyFriendly' => 1,
          'repeaterDepth' => 0,
          'tags' => 'RockCommerce',
          'repeaterAddLabel' => 'Add New Option',
          'columnWidth' => 100,
          'icon' => 'cubes',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'label' => 'Variation Label',
            ],
            self::field_options,
          ],
          'noChildren' => 1,
          'noUnpublish' => 1,
          'parentTemplates' => [
            Variations::tpl,
          ],
          'nameContentTab' => 1,
          'noSettings' => 1,
          'icon' => 'cube',
        ],
      ],
    ]);
  }
}
