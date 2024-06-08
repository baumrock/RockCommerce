# Frontend Helpers

RockCommerce provides a JavaScript file that enables you to integrate E-Commerce functionality into your existing pages without requiring a complete overhaul of your setup.

## Setup

TBD

## Cart-Count

<img src=cart-count.png class=blur>

The `rc-cart-count` attribute allows you to display the number of items in the user's cart. Add it to any HTML element where you want to display the cart count. For example:

```html
<span rc-cart-count hidden></span>
```

This will automatically update the element with the current cart count from the local storage when the DOM content is loaded.

Note: In the example, the element is initially hidden. Once the cart count is updated, the `hidden` attribute is removed, making the element visible. To prevent layout shifts, consider using an absolutely positioned badge or remove the hidden attribute so only the number will update.
