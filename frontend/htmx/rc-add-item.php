<?php

namespace ProcessWire;

$rc = rockcommerce();

$cart = $rc->getCart(
  create: true,
  session: true,
);
bd($cart->addItem());
bd($cart);
