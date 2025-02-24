<?php

namespace RockCommerce;

use ProcessWire\Page;
use ProcessWire\PageArray;
use ProcessWire\RockCommerce;
use ProcessWire\Wire;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

class Discounts extends Wire
{

  public function discountsPage()
  {
    return wire()->pages->get([
      'parent' => rockcommerce()->rootPage(),
      'template' => RockCommerce::template_discounts,
    ]);
  }

  public function getDiscountPages(): PageArray
  {
    return wire()->pages->find([
      'parent' => $this->discountsPage(),
    ]);
  }

  public function getActiveDiscountPage(Page $p): DiscountPage|false
  {
    foreach ($this->getActiveDiscountPages() as $discountPage) {
      if (!$discountPage instanceof DiscountPage) continue;
      $ids = $discountPage->getPageIDs();
      if (in_array($p->id, $ids)) return $discountPage;
    }
    return false;
  }

  /**
   * Get all active discount pages
   */
  public function getActiveDiscountPages(): PageArray
  {
    return wire()->pages->find([
      'parent' => $this->discountsPage(),
      RockCommerce::field_datefrom . '<=' => time(),
      RockCommerce::field_dateto . '>' => time(),
    ]);
  }

  /**
   * Does the given page have an active discount applied?
   */
  public function hasActiveDiscount(Page $p): bool
  {
    return !!$this->getActiveDiscountPage($p);
  }
}
