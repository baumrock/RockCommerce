<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldForm;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

use function ProcessWire\rockcommerce;

class Webhook extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_webhook";
  const prefix = "rockcommerce_webhook_";

  const meta_input = self::prefix . "input";
  const meta_output = self::prefix . "output";

  /** magic */

  public function editFormContent(InputfieldWrapper $form): void
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $input = $this->meta(self::meta_input);
    $form->add([
      'type' => 'markup',
      'label' => 'Input',
      'value' => $rm->renderTable($input),
    ]);
    $form->add([
      'type' => 'markup',
      'label' => 'Output',
      'value' => nl2br($this->meta(self::meta_output)),
    ]);
  }

  public function onCreate(): void
  {
    $this->status = 1;
    $this->title = date("d.m.Y H:i:s");
  }

  /** backend */

  public function getPageListLabel()
  {
    return rockcommerce()->badge($this->title);
  }

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
          ],
          'parentTemplates' => [
            Webhooks::tpl,
          ],
          'noLang' => true, // only english
          'noChildren' => true,
          'noSettings' => true,
          'icon' => 'anchor',
        ],
      ],
    ]);
  }
}
