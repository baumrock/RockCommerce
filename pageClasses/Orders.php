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

  const allowedAttachmentExtensions = 'pdf zip';

  const field_attachmentextensions = self::prefix . "attachmentextensions";

  public function allowedAttachmentExtensions(): string
  {
    return $this->getFormatted(self::field_attachmentextensions);
  }

  public function editForm($form): void
  {
    if ($f = $form->get(self::field_attachmentextensions)) {
      $f->collapsed = wire()->user->isSuperuser()
        ? Inputfield::collapsedNo
        : Inputfield::collapsedNoLocked;
    }
  }

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_attachmentextensions => [
          'type' => 'text',
          'label' => 'Allowed Attachment Extensions',
          'icon' => 'file-pdf-o',
          'textformatters' => [
            'TextformatterEntities',
          ],
          'notes' => 'Default is: ' . self::allowedAttachmentExtensions,
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
            self::field_attachmentextensions,
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
