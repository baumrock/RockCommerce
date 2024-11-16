<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\Page;
use ProcessWire\RockCommerce;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

use function ProcessWire\wire;

class Orders extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_orders";
  const prefix = "rockcommerce_orders_";

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
          ],
          'childTemplates' => [
            RockCommerce::template_order,
          ],
          'noLang' => 1, // single language
          'noChildren' => 1, // add orders only via api
          'noMove' => 1,
          'noChangeTemplate' => 1,
          'noParents' => 1,
          'sortfield' => '-id',
          'icon' => 'check',
        ],
      ],
    ]);
  }

  public function onSaved(): void
  {
    // migrate order (allowed attachment extensions)
    (new Order())->migrate();
  }
}
