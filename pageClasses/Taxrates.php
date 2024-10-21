<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
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
    // if default is set we return it
    $id = (int)$this->getFormatted(self::field_default . '.first')?->id;
    if ($id) return $id;

    // otherwise we return the first taxrate that we find
    $id = (int)$this->children()->first()->id;
    if ($id) return $id;

    // otherwise we return 0
    return 0;
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
