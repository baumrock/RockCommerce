# Rendering Prices

By default all prices will be shown as net values:

```php
echo new Price(100, 20); // 100,00€
echo new PriceMulti(100, 200, 20); // 100,00€ - 200,00€
```

You can switch to gross prices with a config setting:

```php
$config->rcRenderType = "gross";
echo new Price(100, 20); // 120,00€
echo new PriceMulti(100, 200, 20); // 120,00€ - 240,00€
```

Or for even more control you can hook the `toString()` method:

```php
$wire->addHookAfter("Price::toString", function ($event) {
  $price = $event->object;
  $event->return = "EURO {$price->gross}";
});
$wire->addHookAfter("PriceMulti::toString", function ($event) {
  $price = $event->object;
  $event->return = "from {$price->from} up to {$price->to}";
});
echo new Price(100, 20); // EURO 120,00€
echo new PriceMulti(100, 200, 20); // from EURO 120,00€ up to EURO 240,00€
```

You can also prevent showing the to-price:

```php
$wire->addHookAfter("PriceMulti::toString", function ($event) {
  $price = $event->object;
  $event->return = "from {$price->from}";
});
// from 120,00€
```

## Price Hooks (Frontend)

This section explains how to use hooks to modify price rendering in RockCommerce. Hooks allow you to customize the behavior of price calculations and display. The following examples demonstrate how to double the price and add a fixed amount to it.

```js
RockCommerce.before("ProductPriceTotal", (event) => {
  let price = event.return;
  event.return = price.times(1.2);
});
```

Note: You can add this code to your site's JS - just make sure that this is loaded before alpineJS has been loaded.
