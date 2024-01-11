# Frontend Helpers

RockCommerce provides a JavaScript file that enables you to integrate E-Commerce functionality into your existing pages without requiring a complete overhaul of your setup.

## Setup

When using RockFrontend just add the file to your site scripts:

```php
// example usage in _init.php
$rf = rockfrontend();
$rf->scripts()
  // example project using uikit
  ->add('/site/templates/uikit/dist/js/uikit.min.js')

  // example project loading the site's main.js file
  ->add('/site/templates/scripts/main.js', 'defer')

  // add this line to load RockCommerceSite magic :)
  ->add('/site/modules/RockCommerce/RockCommerceSite.min.js', 'defer')
  ->minify(!$config->debug);
```

## Cart-Count

<img src=cart-count.png class=blur>

One of the features provided by this JavaScript file is the `rc-cart-count` class. This class can be used to display the number of items in the user's cart. Here's how you can use it:

1. Add the `rc-cart-count` class to any HTML element where you want to display the cart count. For example:
```html
<span class="rc-cart-count" hidden>0</span>
```
2. The `rc-cart-count` class will automatically populate the element with the current cart count stored in the local storage. This happens when the DOM content is loaded.

Note: In the example the element is initially hidden. Once the cart count is populated, the `hidden` attribute is removed, and the element becomes visible. To avoid layout shifts either use an absolutely positioned badge or remove the hidden attribute so only the number will update.
