<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

use function ProcessWire\rockmigrations;

class Variation extends Page
{
  use MagicPage;

  const tpl = "variation";
  const prefix = "variation_";

  const field_options = self::prefix . "options";
  const field_id = self::prefix . "id";

  /** magic */

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
            'title',
            self::field_options,
          ],
          'noChildren' => 1,
          'parentTemplates' => [
            Variations::tpl,
          ],
          'icon' => 'cube',
        ],
      ],
    ]);
  }
}
