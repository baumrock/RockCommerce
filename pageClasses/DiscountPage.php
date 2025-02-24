<?php

namespace RockCommerce;

use ProcessWire\HookEvent;
use ProcessWire\Page;
use ProcessWire\RepeaterPageArray;
use ProcessWire\RockCommerce;
use RockMigrations\MagicPage;
use RockMoney\Money;

use function ProcessWire\wire;

class DiscountPage extends Page
{
  use MagicPage;

  private $ids = false;

  public function init(): void
  {
    wire()->addHookAfter('Page::loaded', $this, 'onLoaded');
  }

  /* --- regular methods --- */

  public function applyTo(Money $price): Money
  {
    $val = $this->value();
    $type = $this->type();
    if ($type === 'percentage') $price = $price->times(1 - $val / 100);
    return $price;
  }

  public function applyToRepeaterItems(): RepeaterPageArray
  {
    return $this->getFormatted(RockCommerce::field_applytorepeater);
  }

  public function attrs(): string
  {
    return "
      rc-has-discount=1
      rc-discount-value='{$this->value()}'
      rc-discount-type='{$this->type()}'
    ";
  }

  public function editFormContent($form): void
  {
    $ids = $this->getPageIDs();
    $num = count($ids);
    $table = '<style>
      .pagestable { font-size: 0.9rem; }
      .pagestable td { padding: 2px 5px; }
      </style>
      <table class="pagestable uk-table uk-table-small uk-table-striped uk-margin-remove">';
    foreach ($ids as $id) {
      $p = wire()->pages->get($id);
      $table .= "<tr><td>
        <a
          title=edit
          uk-tooltip
          href={$p->editUrl()} class=uk-margin-small-right><i class='fa fa-edit'></i></a>
        <a
          title=view
          uk-tooltip
          class='uk-link-reset uk-text-small' href={$p->url}>
          {$p->parent->url}
          <strong>{$p->title}</strong>
        </a>
      </td></tr>";
    }
    $table .= '</table>';
    $form->add([
      'type' => 'markup',
      'label' => "Applies to $num Pages",
      'value' => $table,
      'notes' => 'This list updates after save if you changed the selection!',
    ]);
  }

  public function ___getPageTitle()
  {
    if (!$this->tsFrom()) return;
    if (!$this->tsTo()) return;
    $from = date('d.m.Y', $this->tsFrom());
    $to = date('d.m.Y', $this->tsTo());
    $val = $this->value();
    $type = $this->type();
    if ($type === 'percentage') $type = '%';
    if ($type === 'fixed') $type = "@";
    $active = $this->isActive() ? '✅' : '❌';
    return "$val{$type} $from - $to $active";
  }

  /**
   * Get pages that this discount applies to
   */
  public function getPageIDs(): array
  {
    if ($this->ids !== false) return $this->ids;
    if (wire()->input->post->submit_save) return [];

    $ids = [];
    $items = $this->applyToRepeaterItems();
    foreach ($items as $item) {
      $applyto = $item
        ->getFormatted(RockCommerce::field_applyto)
        ->first()
        ->value;

      $pagestree = $item->getFormatted(RockCommerce::field_pages . "[]");
      $pagesauto = $item->getFormatted(RockCommerce::field_pagesautocomplete . "[]");
      $pages = clone $pagestree;
      $pages->add($pagesauto);

      $add = [];
      if ($applyto === 'single') $add = $pages->each('id');
      if ($applyto === 'children') {
        $add = wire()->pages->findIDs([
          'parent' => $pages,
        ]);
      }
      if ($applyto === 'descendants') {
        $add = wire()->pages->findIDs([
          'has_parent' => $pages,
        ]);
      }
      if ($applyto === 'pages+children') {
        $add = wire()->pages->findIDs("(id=$pages),(parent=$pages)");
      }
      if ($applyto === 'pages+descendants') {
        $add = wire()->pages->findIDs("(id=$pages),(has_parent=$pages)");
      }
      $ids = array_merge($ids, $add);
    }

    return $this->ids = $ids;
  }

  public function isActive(): bool
  {
    return $this->tsFrom() <= time() && time() < $this->tsTo();
  }

  public function onCreate(): void
  {
    $this->name = wire()->pages->names()->uniqueRandomPageName();
  }

  public function onLoaded(HookEvent $event): void
  {
    $p = $event->object;
    if (!$p instanceof self) return;
    $p->title = $p->getPageTitle() ?: "New Discount";
  }

  public function tsFrom(): int
  {
    return (int)$this->getUnformatted(RockCommerce::field_datefrom);
  }

  public function tsTo(): int
  {
    return (int)$this->getUnformatted(RockCommerce::field_dateto);
  }

  public function type(): string
  {
    return (string)$this
      ->getFormatted(RockCommerce::field_discounttype)
      ?->first()
      ?->value;
  }

  public function value(): string
  {
    return (string)$this->getFormatted(RockCommerce::field_discount);
  }
}
