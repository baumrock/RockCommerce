<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use RockMigrations\MagicPage;

use function ProcessWire\rockmigrations;
use function ProcessWire\wire;

class Settings extends Page
{
  use MagicPage;

  const tpl = 'rockcommerce_settings';
  const prefix = 'rockcommerce_settings_';

  public function editForm(InputfieldWrapper $form): void
  {
    if ($f = $form->get('title')) {
      $f->collapsed = Inputfield::collapsedHidden;
      if (wire()->user->isSuperuser()) {
        $f->collapsed = Inputfield::collapsedNoLocked;
        $f->notes = 'You can add your own settings fields as needed.';
      }
    }
  }

  public function migrate()
  {
    $rm = rockmigrations();
    $rm->migrate([
      'templates' => [
        self::tpl => [
          'icon' => 'cogs',
        ],
      ],
    ]);
    $this->migrateDone();
  }

  /**
   * Hookable method to use to modify the settings page via RockMigrations
   */
  public function ___migrateDone(): void {}
}
