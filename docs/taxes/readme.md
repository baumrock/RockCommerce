# Taxes

Taxes are complicated. But we have to live with them.

In RockCommerce all prices are stored as net values in the database.

As there are so many options for taxes RockCommerce tries to keep things as simple as possible and therefore does not provide a UI for taxes. Instead gross prices are calculated at runtime. For that every product needs to have a dedicated taxrate, which is available as $page->taxrate() method.

This method by default returns the value set in `$config->rockcommerceTaxrate` but you can also hook the taxrate method for full control.

## Global Taxrate

For simple shops you can define a global taxrate like this:

```php
$config->rockcommerceTaxrate = 0.2;
```

## Individual Taxrates

In real life things can quickly get more complicated. For example you might have products with 10% taxrate and some with 20%. Or you might have governmental benefits for all sustainable products having 0% taxrate. Or you might have to charge different taxes based on the location of the user. The list goes on...

All this can be done with a hook, eg in init.php:

```php
wire()->addHookAfter("RockCommerce::taxrate", function ($event) {
  $product = $event->arguments('product');

  // apply whatever logic you need and return the new taxrate:
  if ($product->id === 1557) $event->return = 0.5;

  // fictional example
  if($this->wire->user->region == 'EU') $event->return = 0.123;

  // runtim taxes, no vat for companies
  if($this->wire->session->userIsCompany) $event->return = 0;
});
```