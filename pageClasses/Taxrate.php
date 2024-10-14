<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

class Taxrate extends Page
{
  use MagicPage;

  const tpl = 'rockcommerce_taxrate';
  const prefix = 'rockcommerce_taxrate_';

  const field_taxrate = self::prefix . 'taxrate';
  const field_isdefault = self::prefix . 'isdefault';

  public function label(): string
  {
    return $this->title . ' (' . $this->taxrate() . '%)';
  }

  public function taxrate(): float
  {
    return $this->getFormatted(self::field_taxrate);
  }

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $taxfield = self::field_taxrate;
    $rm->migrate([
      'fields' => [
        self::field_taxrate => [
          'type' => 'decimal',
          'label' => 'Taxrate',
          'icon' => 'percent',
          'notes' => 'Example: For 20% VAT enter 20',
        ],
        self::field_isdefault => [
          'type' => 'checkbox',
          'label' => 'Set as default for new website visitors',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            self::field_taxrate => [
              'columnWidth' => 30,
            ],
            'title' => [
              'label' => 'Description',
              'required' => false,
              'columnWidth' => 70,
            ],
            self::field_isdefault,
          ],
          'parentTemplates' => [
            Taxrates::tpl,
          ],
          'noUnpublish' => '1', // Don't allow unpublished pages
          'pageLabelField' => "{{$taxfield}}% ({title})",
        ],
      ],
    ]);
  }
}
