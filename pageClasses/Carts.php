<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockCommerce;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

class Carts extends Page
{
  use MagicPage;

  const removefilled = 60;
  const removeempty = 1;

  private bool $noHook = false;

  public function init(): void
  {
    wire()->addHookAfter('Pages::saved', $this, 'deleteUnusedCarts');
  }

  protected function deleteUnusedCarts(): void
  {
    try {
      $cartsPage = rockcommerce()->cartsPage();
      $days = $cartsPage->removeEmptyDays();
      if ($days === false) return;
      if ($this->noHook) return;
      $this->noHook = true;
      $old = wire()->pages->find([
        'parent' => $cartsPage,
        'created<' => $days . " days ago",
        'children.count' => 0,
      ]);
      // no need to put them in the trash!
      foreach ($old as $p) $p->delete();
    } catch (\Throwable $th) {
      $this->log($th->getMessage());
    }
  }

  public function editForm(InputfieldWrapper $form): void
  {
    if ($f = $form->get(RockCommerce::field_removefilled)) {
      $f->notes = "0 = Never; Default: " . self::removefilled;
    }
    if ($f = $form->get(RockCommerce::field_removeempty)) {
      $f->notes = "0 = Never; Default: " . self::removeempty;
    }
  }

  public function removeEmptyDays(): int|false
  {
    $days = $this->getFormatted(RockCommerce::field_removeempty);
    if ($days === '') return 1;
    if ($days === 0) return false;
    return (int)$days;
  }
}
