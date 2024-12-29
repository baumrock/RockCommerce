# Taxes

Taxes are complicated - but we have to deal with them. RockCommerce tries to keep things as simple as possible but to provide the tools to hopefully cover most cases.

In RockCommerce all prices are stored as net values in the database. Taxes are then applied globally at runtime. You can have different taxrates for different users, but at the moment it is not possible to have different taxrates for different products. If you have that need please get in touch with me.

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
