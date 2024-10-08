# Installation

## Step 1: Install the module

RockCommerce is built on top of some other modules, like RockMoney for working with monetary values. Before you can install RockCommerce, you need to install all these other modules.

## Step 2: Add RockCommerce to your frontend

To add RockCommerce to your frontend, all you have to do is to add the RockCommerce JS file to your markup:

```html
<html>
  <head>
    <script src="/site/modules/RockCommerce/dst/RockCommerce.min.js" defer></script>
  </head>
  <body>
  </body>
</html>
```

## Step 3: Add a cart indicator to test if everything works

Now add the following HTML somewhere in your page:

```html
Items in the cart: <span rc-cart-count></span>
```

You should get this output:

```html
Items in the cart: 0
```

If you don't see the zero, something went wrong. Check the browser console for errors.

