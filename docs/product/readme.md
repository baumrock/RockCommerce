# Product

## Make any page a RockCommerce product

First, create a backup of your Database. RockCommerce will add many fields to your product template, so if anything goes wrong or you want to revert for whatever reason it's good to have a backup!

Next you have to create a custom pageclass for your template. Don't panik! That's only one simple step: <a href=https://processwire.com/blog/posts/pw-3.0.152/#new-ability-to-specify-custom-page-classes target=_blank>See here</a>.

Finally you have to make your pageclass use the `Product` trait of RockCommerce. That's another simple step:

```php
<?php

namespace ProcessWire;

class ProductPage extends Page
{
  use \RockCommerce\Product;
}
```

That's all you have to do! On the next `modules::refresh` RockCommerce will add all the necessary fields to your template.

Note that in this example we are using the `ProductPage` class which is tied to the `product` template. Your IDE should now know that every `ProductPage` is also a RockCommerce `Product` and therefore it comes with several RockCommerce-related methods that you can use - see the next section for an example!

## Finding products

Use regular ProcessWire API to find products.

Find all products:

```php
// using array-syntax and functions api
$pages->find([
  'template' => rockcommerce()->productTemplates,
]);

// using array-syntax and api variable
$pages->find([
  'template' => $rockcommerce->productTemplates,
]);

// using string notation
$pages->find("template={$rockcommerce->productTemplates}");
```

Of course, if you are only using RockCommerce on one template, for example on the `product` template, you can use a regular selector like this:

```php
$pages->find("template=product");
```

### Finding proucts by price

#### Simple pricing (one single price, no from-to price)

```php
$tpl = rockcommerce()->productTemplates;
$net = \RockCommerce\ProductPage::field_net;
$pages->find("template=$tpl,$net>100,$net<150");
```

Note that you can always use fieldnames instead of using the field constants, but I prefer to use constants as this prevents typos and the IDE helps me to pick from all available fields:

<img src=../field-constants.gif class=blur>

#### Complex pricing (from-to price)

If a product has variations with different prices things get more complex. Let's say we wanted to find all products that have a price over 1000€:

```php
$tpl = rockcommerce()->productTemplates;

// use "tonet" field instead of "net" which holds the to-price
$tonet = \RockCommerce\ProductPage::field_tonet;

bd($all = $pages->find("template=$tpl,$tonet>1000"));
foreach ($all as $p) bd((string)$p->price());

// example return:
// 4.833,33€
// 666,67€ - 5.750,00€
// 5.833,33€
```

## Checking if a page is a product

```php
if($page->isProduct) {
  // do something
}

// or in a latte template
<div n:if="$page->isProduct">I am a RockCommerce product</div>
```

Note that the `isProduct` property is a runtime property that only exists in memory and not in the database!

