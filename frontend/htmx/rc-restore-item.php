<?php

namespace ProcessWire;

use RockCommerce\Cartitem;

$rc = rockcommerce();

$item = $pages->get($input->get('id', 'int'));
if (!$item instanceof Cartitem) throw new WireException("Invalid Item ID");

$cart = $input->get('cart', 'int');
if ($item->cart()->id !== $cart) throw new WireException("Invalid Cart ID");

$item->cart()->template->noChildren = false;
$pages->restore($item);
return $rc->view('partials/cart-item', ['item' => $item]);
