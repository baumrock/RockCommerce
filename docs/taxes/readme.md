# Taxes

Taxes are complicated - but we have to deal with them. RockCommerce tries to keep things as simple as possible but provides the tools to hopefully cover most cases.

In RockCommerce all prices are stored as net values in the database. Taxes are then applied globally at runtime.

## Naming Convention

Consider the taxrate `19.5%`. This number could be presented in different ways, like `0.195`, `19.5` or `19.5%`, or it could even refor to the page ID that is related to that taxrate.

To make things as clear as possible, RockCommerce uses the following naming convention:

- `1030 (int)` = `id`
- `Non-EU Company` = `label`
- `0% (Non-EU Company)` = `labelLong`
- `19.5 (float)` = `percent`
- `19.5% (string)` = `percentLong`
- `0.195 (float)` = `taxrate`

## API (PHP)

In PHP all available taxrates will be available via `rockcommerce()->taxrates()`. This will return a `TaxratesArray` which extends a regular `PageArray` and can be used in the same way:

```php
foreach (rockcommerce()->taxrates() as $taxrate) {
  echo $taxrate->percentLong();
}
```

### Setting the global taxrate

The global taxrate for the current user is stored in his/her cart. Note that you need to use the taxID (Page ID of the taxrate) and not the taxrate (float) as the value:

```php
rockcommerce()->cart()->setTaxID(1030);
```

## API (JavaScript)








- `20%`: This is the taxrate as a string.
- `123`: This is the page ID that contains the taxrate.













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