<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\NullPage;
use ProcessWire\Page;
use RockMigrations\MagicPage;

class Taxrates extends Page
{
  use MagicPage;

  const tpl = 'rockcommerce_taxrates';
  const prefix = 'rockcommerce_taxrates_';

  const field_default = self::prefix . "default";


  public function getDefaultTaxID(): int
  {
    return $this->getDefaultTaxrate()->id;
  }

  public function getDefaultTaxrate(): Taxrate|NullPage
  {
    // if default is set we return it
    $taxrate = $this->getFormatted(self::field_default . '.first');
    if ($taxrate) return $taxrate;

    // otherwise we return the first taxrate that we find
    $taxrate = $this->children()->first();
    if ($taxrate->id) return $taxrate;

    // otherwise we return 0
    return new NullPage();
  }

  public function getPageListLabel()
  {
    $label = $this->getDefaultTaxrate()->id
      ? $this->getDefaultTaxrate()->labelLong()
      : '';
    return $this->title . ', ' . $label;
  }

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $rm->migrate([
      'fields' => [
        self::field_default => [
          'type' => 'page',
          'label' => 'Default Taxrate',
          'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
          'inputfield' => 'InputfieldSelect',
          'findPagesSelector' => 'template=' . Taxrate::tpl,
          'labelFieldName' => 'title',
          'icon' => 'percent',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title',
            self::field_default,
          ],
          'childTemplates' => [
            Taxrate::tpl,
          ],
          'noParents' => 1,
          'icon' => 'percent',
        ],
      ],
    ]);
  }
}
