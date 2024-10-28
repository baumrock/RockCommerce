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

## AJAX Response

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

## Rendering cart items

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

## Show empty cart message

To show a message when the cart is empty, you can use the `x-show` directive:

```html
<div x-show="!items.length">
  Your cart is empty.
</div>
```

## Delete item button

To add a delete button to each cart item, just call the `deleteItem` method with the item's id from any rendered item row inside the `x-for` loop:

```html
<button @click='deleteItem(item.id)'>Remove this item from the cart</button>
```

Or as a link with UIkit tooltip:

```html
<a
  href
  @click.prevent='deleteItem(item.id)'
  title='Aus dem Warenkorb entfernen'
  uk-tooltip
>
  Remove this item from the cart
</a>
```

## Variation Info Table

If you are using variations, you can display them in the cart like this (inside the `x-for='item in items'` loop):

```html
<div
  x-show='item.variationTableData && item.variationTableData.length'
>
  <template x-for='row in item.variationTableData'>
    <div>
      <div x-text='row.variation'></div>
      <div>
        <template x-for='opt in row.options'>
          <div x-text='opt'></div>
        </template>
      </div>
    </div>
  </template>
</div>
```

Of course you can use any other markup as well. Use `<table>` or flexbox or whatever fits your frontend best.

## Item Image

Displaying an image for every item is a little different, because the image path is not available in the cart's JS data. This is because every implementation of RockCommerce can be different.

But it's easy to tell RockCommerce which image to display. We only need a hook to modify the data that RockCommerce uses when grabbing data via AJAX:

```php
// site/ready.php
wire()->addHookAfter('Item::getJsonArray', function (HookEvent $event) {
  $arr = $event->return;
  $product = wire()->pages->get($arr['product']);

  // check if product is valid
  // and if so, populate the pic property
  if(...) {
    $arr['pic'] = $product->coverpic()->maxSize(100, 100)->webp->url;
  }

  // return the modified array to the hookevent
  $event->return = $arr;
});
```

<div class='uk-alert uk-alert-warning'>This is an example that you can't copy 1:1 - you have to modify it to fit your needs.</div>

Once that hook is in place, you can use the `pic` property in your template, just like any other item property:

```html
<img :src='item.pic' :alt='item.title'>
```
