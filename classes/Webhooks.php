<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

class Webhooks extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_webhooks";
  const prefix = "rockcommerce_webhooks_";

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title',
          ],
          'childTemplates' => [
            Webhook::tpl,
          ],
          'sortfield' => '-id',
          'noChildren' => true,
          'icon' => 'anchor',
        ],
      ],
    ]);
  }
}
