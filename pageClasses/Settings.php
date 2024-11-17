<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockCommerce;
use RockMigrations\MagicPage;

use function ProcessWire\wire;

class Settings extends Page
{
  use MagicPage;

  public function editFormContent(InputfieldWrapper $form): void
  {
    if ($f = $form->get('title')) {
      $f->collapsed = Inputfield::collapsedHidden;
    }

    if (wire()->user->isSuperuser()) {
      $url = wire()->pages->get(2)->url;
      $id = wire()->templates->get(RockCommerce::template_settings)->id;
      $form->add([
        'type' => 'markup',
        'label' => 'Help',
        'icon' => 'life-ring',
        'value' => "<p>This settings page can be used to add custom configuration options as needed. RockCommerce will not use this data for anything and will never add or remove any fields. You can edit the template <a href='{$url}setup/template/edit?id={$id}'>rockcommerce_settings</a> to add new fields - either via GUI or via RockMigrations.</p>
        <p>RockCommerce Module Settings can be changed <a href='{$url}module/edit?name=RockCommerce'>here</a>.</p>",
        'notes' => 'This note is only shown to superusers.',
      ]);
    }
  }
}
