<?php

namespace ProcessWire;

$rc = rockcommerce();

// add product to cart
$amount = $input->get('amount', 'int');
$product = $rc->getProduct($input->get('product', 'int'));
$rc->cart()->addItem($product, $amount);

// redirect
$session->redirect("/rc/item-added");
