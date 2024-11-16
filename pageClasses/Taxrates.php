<?php

namespace RockCommerce;

use ProcessWire\NullPage;
use ProcessWire\Page;
use ProcessWire\RockCommerce;
use RockMigrations\MagicPage;

class Taxrates extends Page
{
  use MagicPage;

  public function getDefaultTaxID(): int
  {
    return $this->getDefaultTaxrate()->id;
  }

  public function getDefaultTaxrate(): Taxrate|NullPage
  {
    // if default is set we return it
    $taxrate = $this->getFormatted(
      RockCommerce::field_defaulttaxrate . '.first'
    );
    if ($taxrate) return $taxrate;

    // otherwise we return the first taxrate that we find
    $taxrate = $this->children()->first();
    if ($taxrate && $taxrate->id) return $taxrate;

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
}
