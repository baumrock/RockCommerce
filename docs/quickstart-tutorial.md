# Quickstart Tutorial

> This tutorial was written by forum member @Sanyaissues while beta-testing RockCommerce. THANK YOU SO MUCH, Michael!

The day has finally arrived! You need to build an ecommerce site. You’ve never done it before, but obviously, as the ProcessWire lover you are, there’s no way you’re Shopify-ing or Magento-ing your way into this. Nope, you want to do it yourself.

What if I told you (*crescendo music kicks in*) you could create a shop, right here, right now, in less than an hour?

Introducing:
[RockCommerce](https://www.baumrock.com/en/processwire/modules/rockcommerce/), the ProcessWire way to do ecommerce.


Jokes aside, yeah, we’re building a shopping cart using the brand-new, shiny, and absolutely lovely Bernhard module. Here’s what we’ll do:

- **Frontend Store:** View products and add them to the cart.
- **Backend:** ProcessWire as a backend for products.
- **Payment:** And yes, we’ll process the cart products and handle the payment.

<img src=https://i.imgur.com/Zm7BQ36.gif alt='What we will build' class=blur>

To start, please [create a fresh ProcessWire installation](https://processwire.com/docs/start/install/new/) using the blank profile.
Once you're done, come back here.

## Installation

To install RockCommerce, purchase the module from https://www.baumrock.com/en/processwire/modules/rockcommerce/ and place the files in the `site/modules/RockCommerce` directory. After a modules refresh, you should see the new `RockCommerce` module in the Modules list:

<img src=https://i.imgur.com/DFaQmt5.png alt='Install RockCommerce' class=blur>

As you can see, the module needs some other modules before we can install it:

- **RockMigrations**: The module is used to create all necessary fields and templates. Don't worry, you don't have to write any custom migrations! You can still use the GUI for creating fields and templates for your project.
- **RockFrontend**: RockCommerce needs some of RockFrontend's features, like AJAX endpoints, for example. That does NOT mean, that you have to use RockFrontend for your frontend markup. You can use whatever frontend framework you want (eg WireFrame).
- **RockMoney**: Because 0.1 + 0.2 is not always 0.3 (see [docs](https://www.baumrock.com/en/processwire/modules/rockmoney/docs/))

All these modules are free and open-source, so you can install them from the modules admin by clicking their linked names and hit install.

Once you installed all dependencies, you can install RockCommerce by clicking the `Install` button and you should the RockCommerce config screen:

<img src=https://i.imgur.com/7ewqbjl.png alt='RockCommerce config screen' class=blur>

As the notice in the screenshot suggests, it is recommended to also install TracyDebugger to get prettier dumps of metadata stored with orders, for example.

> Pro-Tip: Enable RockFrontend's LiveReload feature in RockFrontend's module config to quickly see changes you make to your frontend code!

## Setup the Frontend Mockups

Now let's start building something! 🚀

To quickly get an idea of what we'll be building, we'll start with a simple frontend mockup for our store. We will use the blank profile with a very basic output approach and put all the necessary code in the `home.php` file. No inlcudes, no markup regions, no template engine whatsoever.

For quick styling, we will use [UIkit](https://getuikit.com/docs/introduction). Of course, you’re free to use your favorite styling framework (*cough* Tailwind) or even some custom old-fashioned CSS.

Now, open `site/templates/home.php`, paste and save the following:

```html
<?php namespace ProcessWire; ?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- UIkit CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.21.16/dist/css/uikit.min.css" />
    <!-- UIkit JS -->
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.21.16/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.21.16/dist/js/uikit-icons.min.js"></script>
    <!-- Paste RockCommerce JS here -->

  </head>
  <body>
    <header class="uk-navbar-container uk-padding-small">
      <nav class="uk-container uk-container-small uk-flex ">
        <div class="uk-navbar-left uk-text-bolder">🚀👕 RockFashion</div>
        <div class="uk-navbar-right">
          <!-- Cart Count -->
          <span uk-icon="icon: bag"></span> Cart items: XXX
        </div>
      </nav>
    </header>

    <main>
      <div class="uk-container uk-container-small uk-section">

        <div class="uk-grid uk-grid-column-medium">

          <!-- Products region -->
          <div id="products" class="uk-width-2-3">
            <div class="uk-grid uk-grid-medium uk-child-width-expand">
              <div>
                <img src="https://placehold.co/300x300/jpg" />
                <h2>Product title</h2>
                <p><strong>€ XXX</strong></p>
                <a class="uk-button uk-button-primary" href="">Add to cart</a>
              </div>
            </div>
          </div>

          <!-- Cart region -->
          <div class="uk-width-1-3">
            <div id="cart" class="uk-card uk-card-default uk-card-body">
              <p>Products added: XXX</p>
              <p>Subtotal € XXX</p>

              <!-- Payment button -->
            </div>
          </div>

        </div>
      </div>
    </main>

  </body>
</html>

```

Nothing fancy here: We’re including UIkit in our head and defining the HTML skeleton with two regions:  the _products_, and the _cart_.

Now visit the homepage of your project and you should see something like this:

<img src=https://i.imgur.com/vmhk6tn.png alt='Frontend with placeholders' class=blur>

As you can see, we get some markup at the bottom of the page that we don't need. This is because the default profile has `$config->appendTemplateFile = '_main.php';` in the `site/config.php` file, which appends the `_main.php` template to any render templates. It's usually very useful, but for this demo, we won't be using it, so we comment out this line:

`label: /site/config.php`
```php
// $config->appendTemplateFile = '_main.php';
```

### Add RockCommerce to the Frontend

With RockCommerce installed and our basic html ready, we're just one step away from using RockCommerce.

In your `site/templates/home.php` add this script-tag at the end of the `head` tag instead of the `<!-- Paste RockCommerce JS here -->` message:

```html
<script
    src="<?= $config->urls->siteModules ?>RockCommerce/dst/RockCommerce.min.js"
    defer></script>
```

Now, to test if it’s working, update the _Cart items: XXX_ line in the `header` tag with this one, which includes the magic attribute `rc-cart-count`. This attribute helps us display how many products have been added to the cart:

```html
<span uk-icon="icon: bag"></span> Cart items: <span rc-cart-count></span>
```

And also, add the Magic attribute to the cart region:

```html
<p>Products added: <span rc-cart-count></span></p>
```

Head to your browser, and if you see `Cart items: 0` in both sections, congratulations, we’re set! If not... well, go back and check what you missed. That’s the charm of tutorials, isn’t it?

<img src=https://i.imgur.com/I4qsDqf.png class=blur>

*So, what’s happening here?*

In a nutshell, RockCommerce sets up endpoints on our website to handle products, carts, and orders. Then in the frontend it uses [Alpine](https://alpinejs.dev/) as a bridge to give us access to whatever product, cart, or order info we need.

This means the logic, flow and the interface are all up to us! It’s not one of those boxed, ready-to-go solutions where you have to hammer things here and there to make it fit your needs.

So, now that we have the pasta, let's add some salsa:

## Backend Setup

Unilaterally, I’ve decided we’re going to sell Fashion. Let’s set the foundation for our products by creating a product template.

### Create the Product Template

1. Go to **Setup > Templates > Add New**.
2. Type `product` as the name of the template and click **Add Template**.

<img src="https://i.imgur.com/0nLknv0.png" alt="Add a product template" class=blur>


### Add a Product

1. Head to the homepage of the admin panel and, in the page tree, click **Home > New**.

<img src="https://i.imgur.com/18PyGt8.png" alt="Add new page" class=blur>

2. Select the template `product`, give the page the title **T-Shirt**, and hit **Save + Publish**.

<img src="https://i.imgur.com/grfGqrG.png" alt="Add T-Shirt page" class=blur>


### Turn the Product Page into a RockCommerce Product

To convert our product page into a RockCommerce product, we will use a Custom Page Class and the `\RockCommerce\Product` trait.

First, create a new file and copy this code into it. Save the file as `site/classes/ProductPage.php`:

```php
<?php

namespace ProcessWire;

class ProductPage extends Page
{
  use \RockCommerce\Product;
}
```

> Tip: [Custom Page Classes](https://processwire.com/blog/posts/pw-3.0.152/#new-ability-to-specify-custom-page-classes) let us interact with pages in a more organized and *classy way* (pun intended) by introducing Object-Oriented Programming. Without them, we (or just me?) end up dumping a bunch of logic and code into templates, making things messier and more complex. If you're not using classes yet, I beg you: give them a try!


This Custom Page Class is connected to our product page template, which means any page using that template will automatically become a RockCommerce product.

To prove it, go to the settings-tab of your T-Shirt page and you should see the Page object class being `ProductPage`:

<img src=https://i.imgur.com/hN570IT.png alt="Custom Page Class for Product Page" class=blur>

Great! Now let's add a price to our T-Shirt. To do so, we need to add the field `rockcommerce_net` to our product template:

<img src=https://i.imgur.com/VKzVPfM.png alt="Add Net Price field" class=blur>

So, to wrap up our products, go back to your T-Shirt page, enter `14` (or whatever value you prefer) as the price of our T-Shirt in the `Net Price` field. Then, click `Save + Add New` and also create a Sweater product.

<img src=https://i.imgur.com/kZVaAeI.png alt='Add price to T-Shirt' class=blur>

> Tip: By default, the currency is set to Euro. To change it, go to `Modules > Site > RockMoney`.


Finally, hit `publish` (or the road Jack!).


## Frontend Products and Cart

### Display Products

So far, we’ve got RockCommerce running and two shiny products ready to sell. To give our future customers a smooth, AJAX-like shopping experience, actions like adding/removing products, updating the cart, and navigating through checkout will be powered by **Alpine**. Luckily, RockCommerce handles most of the heavy lifting for us.

Let's start by replacing the placeholder content and loading real product data.

Update the `Product` region in your `site/templates/home.php` file with the following:

```php
<!-- Products region -->
<div id="products" class="uk-width-2-3">
  <div class="uk-grid uk-grid-medium uk-child-width-expand">

    <?php foreach (pages('template=product') as $product): ?>
      <div <?= $product->rcAttributes() ?>>
        <img src="https://placehold.co/300x300/jpg" />
        <h2><?= $product->title ?></h2>
        <p><strong><?= $product->rockcommerce_net ?></strong></p>
        <a class="uk-button uk-button-primary" href="#" @click.prevent='addToCart'>Add to cart</a>
      </div>
    <?php endforeach; ?>

    </div>
</div>
```

Now reload the homepage and you'll see two things:

- Our products are now displayed and show their prices.
- We can add products to the cart by clicking the `ADD TO CART` button and the cart counter will increase - how cool is that?!

<img src=https://i.imgur.com/0azSUAU.png class=blur>

But hold your horses! Let’s review what’s happening here:

- We looped through all pages with the `product` template using `pages('template=product')` to display our two products.
- For each product, we displayed a placeholder image, title and price.
- We addd the `$product->rcAttributes()` method to inject essential product data into each product's `<div ...>` attributes.
- Finally, by using the `@click.prevent='addToCart'` dispatcher we made it possible to add products to the cart with a single click.

Regarding `rcAttributes()` — let me show you what it does. Right-click one of your products, inspect its code, and you’ll see something like this:

```html
<div
  x-data="RcProduct"
  rc-pid="1034"
  rc-price="14"
  rc-minamount="1"
  rc-maxamount="10"
  rc-cid="1"
>
```

The `rcAttributes()` method dynamically injects product data that Alpine uses to handle the cart. So, when `@click.prevent='addToCart'` is triggered, it sends a request with that info to the `/rockcommerce/cart/add` endpoint, which in return updates the cart. That’s how our `rc-cart-count` counter increases!

All of this happens just by adding `$product->rcAttributes()` and `@click.prevent='addToCart'` to our product. Neat!

If you installed TracyDebugger, you'll see that for each click on `Add to cart` a new request is made to the `/rockcommerce/cart/add` endpoint:

<img src=https://i.imgur.com/WBGTUR2.png alt='AJAX Requests' class=blur>

### Display Cart

Alright, so far, so good! Our customer can add products, but the products aren't showing up in the cart. Let’s add some markup to get a fully functional dynamic cart:

```html
<!-- Cart region -->
<div class="uk-width-1-3">
  <div
    id="cart"
    class="uk-card uk-card-default uk-card-body"
    x-data="RcCart"
    rc-reload
  >
    <p>Products added: <span rc-cart-count></span></p>
    <template x-for="item in items">
      <div
        x-init="console.log(item)"
        class="uk-flex uk-text-small"
      >
        <div class="uk-width-1-4"><img src="https://placehold.co/60x60/jpg" /></div>
        <div class="uk-width-3-4 uk-padding-small uk-padding-remove-top">
          <span x-text="`${item.title} x ${item.amount}`"></span><br />
          <span x-text="item.totalNet"></span><br />
          <a href="#" @click.prevent="deleteItem(item.id)">remove</a>
        </div>
      </div>
    </template>
    <hr class="uk-divider-small" />
    <p>Subtotal <span x-text="totalNet"></span></p>

    <!-- Payment button -->
  </div>
</div>
```

Go to your browser, refresh the page, add bam, your products are there! With real, dynamic content! If you don't believe me, try adding some more products and see all counts and prices update!

<img src=https://i.imgur.com/mdrN2rF.png class=blur>

I don't want us to miss what’s important here, so let’s strip down the code and imagine it like this:

```html
<div
  x-data="RcCart" // Alpine component
  rc-reload       // Magic RockCommerce reload attribute
>
  <p>
    Products added:
    <span rc-cart-count></span>       // Magic RockCommerce cart-count attribute
  </p>
  <template x-for="item in items">    // Loop over all cart items
    <div x-init="console.log(item)">  // Log item attributes
      Cart items render here...
    </div>
  </template>
</div>
```

What we are doing is creating an _Alpine cart component_ `RcCart` and including the `rc-reload` magic attribute, which will reload cart data for us when the page is loaded. Then, we loop through the `items` array, which contains the items added to our cart, so we can render each one.

And, as a bonus, we included `x-init="console.log(item)"` so we can peek inside each item. In other words, we are "opening the box" to understand which item properties are available and how we can render them.

Open the console, and you’ll see a `Proxy(Object)` for each item in the cart. Inside that object (in the target property) is all the product information Alpine can use to render:

<img src=https://i.imgur.com/gtzCxLo.png alt='Cart Item Data' class=blur>

Since the cart is handled on the frontend, we did not use PHP’s syntax for properties (e.g.: `$product->title`), but used JavaScript syntax instead (e.g.: `item.title`).

To better understand how all that works, let's take a look at a simplified version of the cart markup with some comments:

```html
<!-- The RcCart Alpine component -->
<div x-data="RcCart">
  <p>Products added: ...</p>

  <!-- Loop over all cart items -->
  <template x-for="item in items">
    <!-- Render title and amount of the item object -->
    <span x-text="`${item.title} x ${item.amount}`"></span>
    <!-- Render total net price of the item object -->
    <span x-text="item.totalNet"></span>
    <!-- Render delete button -->
    <!-- When clicked, execute the deleteItem method and pass the item's id -->
    <a href="#" @click.prevent="deleteItem(item.id)">remove</a>
  </template>

  <!-- Render total net price of the cart -->
  <!-- Note that totelNet here refers to the RcCart component! -->
  <p>Subtotal <span x-text="totalNet"></span></p>
</div>
```

> Pro-Tip: You can inspect all AJAX requests made by RockCommerce by opening the Network tab in your browser's developer tools. There you will see what data is sent to the backend and what data is received, for example:

<img src=https://i.imgur.com/eKHucfh.png alt='AJAX Request: Add Cart Item' class=blur>

## Payment

One of the things I feared most about the DIY e-commerce concept was: *Okay, how am I going to make payments work?*

So, since our demo store is already displaying products, and we can add and remove them from the cart, we’re going to take a shortcut. Instead of building a full checkout form (where we’d obviously capture customer details like name and address), we are going to grab the bull by the horns, and jump straight to the payment!

Olé! 🐂

### Create the Payment Button

So we're going to add a payment button under the cart region with this simple markup:

```html
<!-- Payment button -->
<form method="post" action="/payment/">
  <button type="submit" class="uk-button uk-button-primary">
    Go to payment
  </button>
</form>
```

As you see, it’s a plain and simple form that doesn’t actually submit any data — it just triggers the `/payment/` page. We'll cover that in the next step, but first, let's improve our button a little bit by adding some Alpine magic!

We'll disable the button when the cart is empty and enable it when products are added:

```html
<form method="post" action="/payment/">
  <button
    type="submit"
    :class="count ? 'uk-button-primary' : 'uk-button-default'"
    :disabled="!count"
    class="uk-button">
      Go to payment
  </button>
</form>
```

<img src=https://i.imgur.com/Ckcqyo4.gif alt='Dynamic Payment Button' class=blur>

### Use Mollie as a Payment Service Provider

For this demo, we’re going to integrate Mollie because it’s easy, and Bernhard already [created the module](https://processwire.com/talk/topic/30529-rockmollie-integrates-mollie-payments-into-processwire/).

1. Start by creating a Mollie account at [here](https://my.mollie.com/dashboard/signup/2316011) (Affiliate-Link).
2. Click "Online payments."
3. Copy the test key under "Test API key."
4. Add this to the end of your `site/config.php` file, making sure to replace `your API key` with the key you copied:

`label: /site/config.php`
```php
$config->mollieApiKey = 'test_xxx'; // Paste your key here
```

### Install RockMollie

1. Go to `Modules > New` in the ProcessWire admin.
2. Under `Add Module From Directory`, type `RockMollie`.
3. Click on `Get Module info`, then `Download Now`, and click on `Install Now`.

### Processing the Payment

Now let's implement the `/payment/` endpoint that will handle the payment process with Mollie!

The logic here is pretty simple. When the customer hits the `Go to payment` button, we send him to the `/payment/` endpoint. There, we convert his cart into an order and pass the order information (total price to pay) to Mollie so they can charge our customer. Then, Mollie will return the customer back to our website and we can handle the rest.

Here's a diagram that illustrates the flow:

<img src=https://i.imgur.com/G8PDxGn.png alt='Payment Flow' class=blur>

Can you guess how much code we’d need to make that happen? Let's find out:

First, please create the file `site/rockcommerce.php`. This is the recommended place to put all your hooks that modify RockCommerce's behavior.

> **Tip**: If you're not familiar with hooks, pause here and check out the [Using Hooks in ProcessWire](https://processwire.com/docs/modules/hooks/) documentation.

Now that we have it, let's paste our first hook:

```php
<?php

namespace ProcessWire;

// Add an URL-hook to handle form submissions at the '/payment/' URL
wire()->addHook('/payment/', function ($event) {
  // Create a new order using RockCommerce cart
  // Use whatever subject you want, but for now we'll use 'New Order'
  $order = rockcommerce()->cart()->createOrder('New Order');

  // Create a payment for the order. We set '/thanks/' as the redirect URL
  // after payment, but you can change it to whatever you want
  $payment = $order->createPayment('/thanks/');

  // If the payment was successfully created, redirect to the checkout URL
  if ($payment) {
    wire()->session->redirect($payment->getCheckoutUrl());
  } else {
    throw new WireException('Payment creation failed.');
  }
});
```

Second… no, there's no second step, just this hook. Really.
I know! It is so simple, yet powerful!

> Tip: Read more about URL hooks [here](https://processwire.com/blog/posts/pw-3.0.173/).

### The Thanks Page

To finish, we are going to create a simple thanks page, by ripping of the layout of our Home page. Please create a new file `site/templates/thanks.php` and paste this:

```html
<?php namespace ProcessWire; ?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- UIkit CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.21.16/dist/css/uikit.min.css" />
    <!-- UIkit JS -->
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.21.16/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.21.16/dist/js/uikit-icons.min.js"></script>
    <!-- RockCommerce JS -->
    <script
      src="<?= $config->urls
        ->siteModules ?>RockCommerce/dst/RockCommerce.min.js"
      defer
    ></script>
  </head>
  <body>
    <header class="uk-navbar-container uk-padding-small">
      <nav class="uk-container uk-container-small uk-flex ">
        <div class="uk-navbar-left uk-text-bolder">🚀👕 RockFashion</div>
        <div class="uk-navbar-right">
          <span uk-icon="icon: bag"></span> Cart items: <span rc-cart-count></span>
        </div>
      </nav>
    </header>
    <main>
      <div class="uk-container uk-container-small uk-section">
        <h1>Thanks a ton!</h1>
        <p>Your order will arrive in 2 hours. 🚀 We are FasTees than Amazon! 🛍️</p>
      </div>
    </main>
  </body>
</html>
```

Now, go to the ProcessWire admin, navigate to **Setup > Templates > Add New**, and ProcessWire will prompt you to add the `Thanks` template.

Next, return to the Admin home, and create a child page under Home with the name `Thanks`, select the thanks template and publish it.

Go to your browser and visit the `/thanks/` page.

<img src=https://i.imgur.com/xHrBJbu.png alt='Thanks Page' class=blur>

Nice! We have our thanks page ready, but… did you notice that the cart still has items? Weird. That’s because we never reset it! The products will stay there as long as the customer doesn't clear their browser's local storage. To fix it, we can use the `rc-cart-reset` magic attribute.

You can place it anywhere on the thank you page, but let’s add it below our delivery message:

```html
<p>Your order will arrive in 2 hours. 🚀 We are FasTees than Amazon! 🛍️</p>
<!-- This line resets the cart! -->
<span rc-cart-reset></span>
```

Refresh your browser, and voilà! The cart has been reset.

## Testing

And FINALLY! we can test our brand new ecommerce:

<img src=https://i.imgur.com/Zm7BQ36.gif alt='Testing our new store' class=blur>

## Bonus Track: Customize RockCommerce

So, you thought I was going to leave you with a demo store without product photos? Come on! We act like RockStars here — we leave the best for the end of the concert!

Please continue with the [Customizing RockCommerce Tutorial](../customizing-rockcommerce-tutorial/).
