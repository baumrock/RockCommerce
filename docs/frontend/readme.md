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

## HTMX

### Updating elements

Often when one element changes (eg updating the amount of a cart item) other things on the page also need to change (eg the sidebar with the total amount to pay). RockCommerce makes that super easy. Simply add the elemenets that should get updated to the ajax request:

```html
// use swap:'foo,bar,baz' syntax
<div
  hx-post="/rc-update-item?id={$item->id}"
  hx-swap="outerHTML"
  hx-vars="swap:'cart-sidebar'"
>
```

<div class=uk-alert>Note that for security considerations only partials can be used for swapping!</div>

The swapped element needs to have either an id or a `rc-swap` attribute that defines the target elements:

```html
// replace a single element
<div id="cart-sidebar">...</div>

// replace multiple target elements
<div rc-swap=".cart-items-count">...</div>
```
