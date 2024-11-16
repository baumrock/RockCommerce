<?php

namespace RockCommerce;

use ProcessWire\Page;
use ProcessWire\RockCommerce;
use RockMigrations\MagicPage;

class Webhooks extends Page
{
  use MagicPage;

  /**
   * Populate defaults on page creation
   */
  public function onCreate(): void
  {
    $this->set(RockCommerce::field_max, '50');
  }
}
