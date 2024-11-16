<?php

namespace RockCommerce;

use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockCommerce;
use RockMigrations\MagicPage;

class Variation extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_variation";
  const prefix = "rockcommerce_variation_";

  const field_comment = self::prefix . "comment";
  const field_price   = self::prefix . "price";

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
    $rm->migrate([
      'fields' => [
        self::field_price => [
          'type' => 'RockMoney',
          'label' => 'Price',
          'icon' => 'money',
        ],
        self::field_comment => [
          'type' => 'text',
          'label' => 'Internal Comment',
          'icon' => 'comment-o',
          'textformatters' => [
            'TextformatterEntities',
          ],
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'label' => 'Variation Label',
              'columnWidth' => 50,
            ],
            self::field_comment => [
              'columnWidth' => 50,
            ],
            RockCommerce::field_variationoptions,
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
