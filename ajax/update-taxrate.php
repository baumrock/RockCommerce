<?php

namespace ProcessWire;

use RockCommerce\ProductPage;

$rc = rockcommerce();

$id = $input->post('id', 'int');
$taxrate = $input->post('taxrate', 'float');

// check if it is a product
/** @var ProductPage $product */
$product = rockcommerce()->getProduct($id);
if (!$product) throw new WireException("Invalid Product");

$product->setTaxrate($taxrate);
return $product->getPriceEditorRow();
