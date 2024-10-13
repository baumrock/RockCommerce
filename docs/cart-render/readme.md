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
  Ihr Warenkorb ist leer.
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

## Loading Spinner

To display a loading spinner while the cart is loading, you can use Alpine's `x-show` directive:

```html
<div x-show='loading'><!-- your loading spinner --></div>
```
