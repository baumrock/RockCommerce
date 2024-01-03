<?php

namespace ProcessWire;

use RockCommerce\Cartitem;

$rc = rockcommerce();

$item = $pages->get($input->get('item', 'string'));
if (!$item instanceof Cartitem) throw new WireException("Invalid Item ID");

// check if we know the correct cart
if ($item->cart()->name !== $input->get('cart', 'string')) {
  throw new WireException("Invalid Cart ID");
}

// temporarily set noChildren to false
// this ensures that the restore operation works
// otherwise PW will throw an error that children are not allowed
// under parents that have noChildren = true
$item->cart()->template->noChildren = false;
$pages->restore($item);

// render the item
return $rc->view('partials/cart-item', ['item' => $item]);
