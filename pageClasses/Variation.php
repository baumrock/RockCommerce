<?php

namespace RockCommerce;

use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use RockMigrations\MagicPage;

class Variation extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_variation";
  const prefix = "rockcommerce_variation_";

  const field_options = self::prefix . "options";

  private $optionsArray;

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    // add API hint to title field
    if ($f = $form->get('title')) {
      $f->notes = "API: \$rockcommerce->variations('{$this->name}')->title";
      $f->columnWidth = 50;
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

  /** backend */

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $rm->deleteField("rockcommerce_variation_id", true);
    $rm->migrate([
      'fields' => [
        self::field_options => [
          'label' => 'Options',
          'type' => 'FieldtypeRepeater',
          'fields' => [
            'title' => [
              'label' => '',
              'notes' => '',
              'required' => true,
              'columnWidth' => 100,
              'rpb-nolabel' => true, // hide label in backend
            ],
          ],
          'repeaterTitle' => '{title}',
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
              'columnWidth' => 100,
            ],
            self::field_options,
          ],
          'noChildren' => 1,
          'noUnpublish' => 1,
          'parentTemplates' => [
            Variations::tpl,
          ],
          'nameContentTab' => 0,
          'noSettings' => 1,
          'icon' => 'cube',
        ],
      ],
    ]);
  }
}
