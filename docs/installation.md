# Installation

In this example we will use the [default profile (standard/intermediate edition)](https://processwire.com/download/site-profiles/#default-profile-standard-intermediate-edition).

## Step 1: Install the module

RockCommerce is built on top of some other modules, like RockMoney for working with monetary values. Before you can install RockCommerce, you need to install all these other modules.

VIDEO

## Step 2: Add RockCommerce to your frontend

To add RockCommerce to your frontend, all you have to do is to add the RockCommerce JS file to your main markup file. In the default profile, this is the file `site/templates/_main.php`.

```php
<head>
  <script src="<?= $config->urls->siteModules ?>RockCommerce/dst/RockCommerce.min.js" defer></script>
</head>
```

To make sure that RockCommerce is working on your frontend, add this markup somewhere in your page:

```html
<div>Cart Count: <span rc-cart-count></span></div>
```

RockCommerce should populate this span with a zero.

## Step 3: Turn your pages into products

Any ProcessWire page can be turned into a RockCommerce product. To do this, you need to add the `\RockCommerce\Product` trait to your page type.

Fear not! It's as simple as creating one file!

To turn the `basic-page` template into a product, we create the file `site/classes/BasicPagePage.php`:

```php
<?php

namespace ProcessWire;

class BasicPagePage extends Page
{
  use \RockCommerce\Product;
}
```

Two important things to note here:

1. We create a Custom Page Class for the `basic-page` template.
2. The class must use the `\RockCommerce\Product` trait.

Now do a Modules Refresh and all your `basic-page` pages will be RockCommerce products!

<img src=https://i.imgur.com/ZdO9ObZ.png class=blur height=300>

## Step 4: Render an add to cart button

In `site/templates/basic-page.php`, replace this:

```php
$content = $page->get('body');
```

with that:

```php
$attrs = $page->rcAttributes();
$content = "<div $attrs><button @click='addToCart'>Add to cart</button></div>";
$content .= $page->get('body');
```

Now reload the page and you should see the add to cart button. If you inspect the sourcecode, you should see something like this:

```html
<div
  x-data="RcProduct"
  rc-pid="1002"
  rc-price="123"
  rc-minamount="1"
  rc-maxamount="10"
  rc-cid="1"
>
  <button @click="addToCart">Add to cart</button>
</div>
```

Once you click the button, you should see the cart count increment! You can inspect the devtools of your browser to see details what is happening.

Behind the scenes, RockCommerce is using Alpine.js to handle the button click and it will send an AJAX request to `/rockcommerce/cart/add` and this request will return the cart data and cart count, which will then be used to update all `rc-cart-count` DOM elements on the page.

## Step 5: Render the cart

To render the cart, we first need to create a template for the cart page. We'll call this template `cart` in this example.

Then we create a new page using this template. We'll call it `cart` and the title will be `Cart`.

Next, we create the template file `site/templates/cart.php` and add this markup:

```php
<?php

namespace ProcessWire;

$content = "<div>Cart Items:</div>";

$content .= "<div x-data='RcCart' rc-reload>
  <template x-for='item in items'>
    <!-- IMPORTANT: template must have a single root element! -->
    <div style='border: 2px solid #ccc; padding: 5px;'>
      <div x-text='item.title'></div>
      <div>Amount: <span x-text='item.amount'></span></div>
      <div>Price: <span x-text='item.price'></span></div>
    </div>
  </template>
</div>";
```

We can now view our cart page at `/cart` and we should see something like this:

<img src=https://i.imgur.com/1UKN1bk.png class=blur height=400>

## Step 6: Adding a simple checkout

Now let's create a simple checkout page where users can enter their details and complete the order.

In this example we'll use `RockForms`, but you can use any other module or plain html markup as well. But be aware that things can quickly get complicated, so we highly recommend using RockForms, which supports Multi-Step Forms out of the box!

