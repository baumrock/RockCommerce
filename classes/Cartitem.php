<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use RockMigrations\MagicPage;
use RockMoney\Money;

use function ProcessWire\rockfrontend;
use function ProcessWire\rockmoney;

class Cartitem extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_cartitem";
  const prefix = "rockcommerce_cartitem_";

  const field_page = self::prefix . "page";
  const field_amount = self::prefix . "amount";

  public function __construct($parent = false)
  {
    try {
      $this->template = $this->wire->templates->get(self::tpl);
      $this->parent = $parent;
    } catch (\Throwable $th) {
    }
    parent::__construct();
  }

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    if ($f = $form->get(self::field_page)) {
      $page = $f->value;
      if ($page && $page->id) {
        $f->entityEncodeText = false;
        $f->notes = "<a href={$page->editUrl()} target=_blank class=uk-margin-small-right>" . $this->_('edit') . "</a>"
          . "<a href={$page->url()} target=_blank>" . $this->_('view') . "</a>";
      }
    }
  }

  public function onCreate(): void
  {
    $this->name = $this->wire->pages->names->uniqueRandomPageName([
      'min' => 10,
      'max' => 15,
    ]);
  }

  public function onSaved(): void
  {
    if ($this->isTrash()) return;
    $this->meta('rockcommerce-cart', $this->parent->id);
  }

  /** frontend */

  public function cart(): Cart
  {
    if ($this->isTrash()) {
      $cartID = $this->meta('rockcommerce-cart');
      return $this->wire->pages->get($cartID);
    }
    return $this->parent;
  }

  public function ___cartTitle(): string
  {
    return $this->page()->title;
  }

  public function ___cartDescription(): string
  {
    return "";
  }

  public function html($str)
  {
    return rockfrontend()->html($str);
  }

  public function itemNet(): Money
  {
    return rockmoney()->parse(0);
  }

  public function itemVat(): Money
  {
    return rockmoney()->parse(0);
  }

  public function itemGross(): Money
  {
    return rockmoney()->parse(0);
  }

  public function page(): Page|false
  {
    return $this->getFormatted(self::field_page);
  }

  public function totalNet(): Money
  {
    return $this->itemNet()->times($this->amount(1));
  }

  public function totalVat(): Money
  {
    return $this->itemVat()->times($this->amount(1));
  }

  public function totalGross(): Money
  {
    return $this->itemGross()->times($this->amount(1));
  }

  /** backend */

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_page => [
          'type' => 'page',
          'label' => 'Item',
          'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
          'inputfield' => 'InputfieldPageAutocomplete',
          'findPagesSelector' => 'id>0,template!=admin',
          'labelFieldName' => 'title',
          'collapsed' => Inputfield::collapsedNo,
        ],
        self::field_amount => [
          'type' => 'integer',
          'label' => 'Amount',
          'icon' => 'list-ol',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields-' => [
            self::field_page => ['columnWidth' => 50],
            self::field_amount => ['columnWidth' => 50],
          ],
          'parentTemplates' => [
            Cart::tpl,
          ],
          'noLang' => 1, // single language
          'noChildren' => 1,
          'noMove' => 1,
          'noChangeTemplate' => 1,
          'noParents' => 1,
          'sortfield' => '-id',
          'icon' => 'cube',
          'noSettings' => 1,
        ],
      ],
    ]);
  }
}
