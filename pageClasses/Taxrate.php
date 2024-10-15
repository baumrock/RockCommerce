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

  public function loaded(): void
  {
    // make all properties of getJsonArray available as properties of the page
    // this is to support selectors like
    // rockcommerce()->taxrates()->get('isDefault=1')
    foreach ($this->getJsonArray() as $k => $v) {
      if ($k === 'id') continue;
      $this->$k = $v;
    }
  }

  public function __debugInfo()
  {
    return $this->getJsonArray();
  }

  public function ___getJsonArray(): array
  {
    return [
      'id' => $this->id,
      'label' => $this->title,
      'labelLong' => $this->labelLong(),
      'percent' => $this->percent(),
      'percentLong' => $this->percentLong(),
      'taxrate' => $this->taxrate(),
    ];
  }

  public function label(): string
  {
    return $this->title;
  }

  public function labelLong(): string
  {
    return $this->title . ' (' . $this->percentLong() . ')';
  }

  public function taxrate(): float
  {
    $val = $this->getFormatted(self::field_taxrate);
    return (float)$val / 100;
  }

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $taxfield = self::field_taxrate;
    $rm->deleteField(self::prefix . 'isdefault', true);
    $rm->migrate([
      'fields' => [
        self::field_taxrate => [
          'type' => 'decimal',
          'label' => 'Taxrate Percentage',
          'icon' => 'percent',
          'notes' => 'Example: For 20% VAT enter 20',
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

  public function percent(): float
  {
    return $this->taxrate() * 100;
  }

  public function percentLong(): string
  {
    return $this->percent() . '%';
  }
}
