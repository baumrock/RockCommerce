# Cart Rendering

## Quickstart

A RockCommerce cart consists of two parts:

1. The cart items (the main cart area)
2. The cart summary (usually in a sidebar)

The html could look like this:

```html
<div x-data=RcCart>
  <div>
    <!-- cart items -->
  </div>
  <div>
    <!-- cart summary -->
  </div>
</div>
```

Adding `x-data=RcCart` to the wrapper div initializes the `RcCart` component. This component is responsible for handling all cart operations, like adding and removing items, updating quantities, applying coupons, and calculating shipping costs.

### AJAX Response

As soon as the user opens the cart page, the `RcCart` component will be initialized and make a GET request to `rockcommerce/cart/get`. This will fetch the cart data, which will look something like this:

```json
{
  "count": 8,
  "items": [
    {"pid": 123, "amount": 6, "title": "My Product", "variation": "1010:1111,2222"},
    {"pid": 123, "amount": 1, "title": "My Product", "variation": "1010:1111"},
    {"pid": 456, "amount": 1, "title": "My Other Product", "variation": ""},
  ]
}
```

This date will then be used to render the cart items.

### Rendering the cart items

The markup of the cart items is totally up to you. However, you have to apply some special attributes to the elements, so that the `RcCart` component knows which elements to update:

```html
<template x-for='item in items'>
  <div><!-- x-for must have one root element! -->
    <h3 x-text='item.title'></h3>
    <div>Amount: <span x-text='item.amount'></span></div>
    <div>Variation: <span x-text='item.variation'></span></div>
  </div>
</template>
```

### Loading Spinner

To display a loading spinner while the cart is loading, you can use Alpine's `x-show` directive:

```html
<div x-show='loading'><!-- your loading spinner --></div>
```

## Customizing the cart

### Using hooks

Place your hooks in `/site/init.php` (not ready.php!):

```php
// modify the cartitem's title:
$wire->addHookAfter("Cartitem::cartTitle", function ($event) {
  $item = $event->object;
  $product = $item->product(); // get the item's referenced product
  $event->return = $product->title;
});

// modify the cartitem's description:
$wire->addHookAfter("Cartitem::cartDescription", function ($event) {
  $item = $event->object;
  $product = $item->product(); // get the item's referenced product

  // if you want to render html markup you need to use
  // the html() helper to prevent latte from escaping your markup
  // note: do this only on save markup!
  $event->return = $item->html($product->body);
});
```
