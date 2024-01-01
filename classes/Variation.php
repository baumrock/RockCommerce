<?php

namespace RockCommerce;

use ProcessWire\HookEvent;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use RockMigrations\MagicPage;

class Variation extends Page
{
  use MagicPage;

  const tpl = "variation";
  const prefix = "variation_";

  const field_options = self::prefix . "options";
  const field_id = self::prefix . "id";

  public function init(): void
  {
    $this->wire->addHookAfter("Pages::saveReady", $this, "hookRepeaterSave");
  }

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    if ($name = $form->get('_pw_page_name')) {
      $name->label = "Variation Name (ID)";
      $name->appendMarkup = "<style>#Inputfield__pw_page_name_path {display:none;}</style>";
      $name->columnWidth = 50;
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
    return $this->title ?: $this->getFormatted(self::field_id);
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
            self::field_id,
            'title' => [
              'label' => 'Label',
              'notes' => 'Human readable label',
              'required' => false,
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
