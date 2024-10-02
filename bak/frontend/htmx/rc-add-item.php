<?php

namespace ProcessWire;

$rc = rockcommerce();

bd($rc->cart()->addItem());
