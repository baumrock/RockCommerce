# Frontend Rendering

## Concept

RockCommerce uses the power of RockFrontend to render all frontend facing pages and make everything customisable by the developer in an easy and efficient way.

## _init.php

Similar to ProcessWire's `_init.php` file in the templates folder you can add the file `/site/templates/RockCommerce/frontend/_init.php`. This file will be loaded by `_main.php` whenever a rockcommerce frontend page is rendered.

This file is the place where you can add styles or scripts or adjust properties of the `rockcommerce` API variable like `pageTitle` etc:

```php
<?php

namespace ProcessWire;

$rc->pageTitle = 'foo bar';
```