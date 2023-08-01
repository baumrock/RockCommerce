<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

class Webhooks extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_webhooks";
  const prefix = "rockcommerce_webhooks_";

  const field_max = self::prefix . "max";

  /** magic */

  /**
   * Populate defaults on page creation
   */
  public function onCreate(): void
  {
    $this->set(self::field_max, '50');
  }

  /** backend */

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $rm->migrate([
      'fields' => [
        self::field_max => [
          'type' => 'integer',
          'label' => 'Maximum number of webhook log pages',
          'icon' => 'list-ol',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title',
            self::field_max,
          ],
          'childTemplates' => [
            Webhook::tpl,
          ],
          'sortfield' => '-id',
          'noChildren' => true,
          'noParents' => 1,
          'icon' => 'anchor',
        ],
      ],
    ]);
  }
}
