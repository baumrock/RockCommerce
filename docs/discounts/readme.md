# Discounts

In RockCommerce, discounts are a powerful feature that allows you to reduce the price of products. A discount is a percentage reduction (e.g., 50%) that is applied directly to a product's price.

## Key Concepts

### Discounts vs Coupons

- **Discounts** are applied directly to individual products
- **Coupons** are applied to the entire cart at checkout
- Discounts are calculated before coupons are applied

## Backend Implementation

- Discounts are managed through Shop > Settings > Discounts
- Each discount is represented by a ProcessWire page
- Each discount can be applied to a variety of pages
- Each discount has a start and end date

<img src=https://i.imgur.com/A0xVK4z.png class=blur>

## Frontend Integration

<img src=https://i.imgur.com/poySyFp.png class=blur>

Depending on your frontend implementation discounts can appear on multiple places.

### Product Page

On the product page you might want to display several details about the product and the current discount:

1. Discount description (amount & period)
2. Regular price
3. Reduced price

<img src=https://i.imgur.com/w66HOpy.png class=blur>

The discount description is best implemented on your own. I'm placing these kind of helpers in a `Site.module.php` module so that I can easily access it from my templates:

```latte
{var $discount = rockcommerce()->discounts()->getActiveDiscountPage($page)}
<div n:if='$discount'>
  {$site->discountLabel($page)}
</div>
```

This snippet uses LATTE syntax, but you can do the same with plain PHP.

- First, it grabs the active discount for the current page
- Then it adds the label, but only if a discount is active (n:if directive)
- Finally it writes the label to that div

The label is constructed like this in my `Site.module.php`:

```php
public function discountLabel(Page $product, $showUntil = true): string
{
  $discount = rockcommerce()->discounts()->getActiveDiscountPage($product);
  if (!$discount instanceof DiscountPage) return '';
  $to = date('d.m.Y', $discount->tsTo());
  $val = $discount->value();
  $type = $discount->type();
  if ($type === 'percentage') $type = '%';
  if ($type === 'fixed') {
    $type = '';
    $val = rockmoney($val);
  }
  $label = "-$val{$type}";
  if ($showUntil) $label .= " bis $to";
  return $label;
}
```

### Taxes

In this example shop we show prices with 0% or 20% taxes depending on the user. This makes things more complicated and we need to hook into the price calculation of the product page from JavaScript:

```js
// Show prices +VAT
ProcessWire.addHookAfter("RcProduct::priceTotal", (event) => {
  const money = event.return;
  event.return = money.times(1 + RockCommerce.getTaxrate());
});
// Set no-discount price +VAT
ProcessWire.addHookAfter("RcProduct::setPriceNoDiscount", (event) => {
  const money = event.arguments(0);
  const product = event.object;
  product.priceNoDiscount = money
    .times(product.amount)
    .times(1 + RockCommerce.getTaxrate())
    .format();
});
```

### Cart Page

<img src=https://i.imgur.com/3chNZEz.png class=blur>

On the cart page we might also want to show the savings for the user. In this case we show the original price and the reduced price on every cart item:

```html
<template x-if='!item.hasDiscount'>
  <div class='uk-text-right'>
    <div class='uk-text-bold'>exkl. MwSt. <span x-text='item.itemNet'></span></div>
  </div>
</template>
<template x-if='item.hasDiscount'>
  <div class='uk-text-right'>
    <div class='line-through'>exkl. MwSt. <span x-text='item.itemNetNoDiscount'></span></div>
    <div class='uk-text-bold'>exkl. MwSt. <span x-text='item.itemNet'></span></div>
  </div>
</template>
```

AlpineJS will do its magic and populate those placeholders for us!

## Limitations

1. **Percentage-Only**: At the moment only percentage discounts are supported. Fixed amount discounts are not implemented yet due to complexity with tax calculations.

2. **Product-Level Only**: Discounts cannot be applied to:
  - Individual product variations
  - Specific combinations of product options
  - Shipping costs
