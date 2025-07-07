# Product Variations

To use the product variations feature you need to install the RockGrid module as this module does the heavy lifting of all the variation inputs.

## Setup

Go to the RockCommerce config page and check the box to enable product variations. If you don't have RockGrid installed it will show a warning like this:

<img src=https://i.imgur.com/so7EHNZ.png class=blur>

Once you have installed RockGrid check that the RockCommerce grids are listed under Setup > RockGrid:

<img src=https://i.imgur.com/muRAtjy.png class=blur>

Next, add the `rockcommerce_variations` field to your product template (and also the `net` field if not yet done):

<img src=https://i.imgur.com/lHrkrWc.png class=blur>

Now edit a product and it should look something like this:

<img src=https://i.imgur.com/VjKWVy8.png class=blur>

To add a new variation click on the `+ New Variation` button. A modal will pop up and you can define the variation's name and its options:

<img src=https://i.imgur.com/dYEXO3n.png class=blur>

Close the modal (and maybe reload the page) and you can edit variations for your product.

## Variation Selection

<img src=https://i.imgur.com/mxnwxwx.gif class=blur>

To apply variations to your product you have to select them from the list. This is to make variations reusable across your shop, for example you might want to add the variation "Color (red/green/blue)" that you can use for all your T-Shirts.

You can either select all options of the variation by clicking on the variation's name in the "variation" column or you can enable/disable certain options by clicking on the option in the "option" column. This makes it possible to provide many global options but still define customized options for each product in a very fast and convenient way.

## Prices

<img src=https://i.imgur.com/OLu4s8Q.gif class=blur>

Each variation has a price. You can either use "0" as base price and enter the variation price for each variation (eg, 10€ / 20€ / 30€) or sometimes you might want to set a total price. To save you or your clients from the hassle of calculating all price differences there is the "total" column that you can use and RockCommerce will calculate the difference for you.

## API

This is how you can loop over all variations from PHP:

```php
$page = wire()->pages->get(123);
foreach($page->variations() as $variation) {
  echo "<div>{$variation->title()}</div>";
}
```

For every variation we usually have several options to choose from. You can access them from the variation via its `options()` method:

```php
foreach($variation->options() as $option) {
  echo $option->title;
}
```

## Frontend

We assume you followed the quickstart tutorial where we explained how RockCommerce works and how it uses Alpine to apply some magic!

There we showed how to list products with their price and an add to cart button.

Next we want to list selectable product variations and add an inputfield to enter the amount of products to order with some nice buttons to increase and decrease the amount.

To recap, this was the code we had so far:

```php
<div <?= $product->rcAttributes() ?>>
  <img src="https://placehold.co/300x300/jpg" />
  <h2><?= $product->title ?></h2>
  <p><strong><?= $product->rockcommerce_net ?></strong></p>
  <a class="uk-button uk-button-primary" href="#" @click.prevent='addToCart'>Add to cart</a>
</div>
```

Now let's add an input to allow ordering multiple items:

```php
<div>
  <input type="number" x-model="amount" value="1" />
</div>
```

The `x-model` attribute tells Alpine to update the `amount` property of this `RcProduct` object whenever we change the value of the input. This already makes it impossible to enter negative numbers!

Maybe you have realised that we are not yet done: The price does not update when changing the amount! This is because we populated the price via PHP but we didn't tell Alpine to update it as well. We can fix this easily:

```php
// change this
<p><strong><?= $product->rockcommerce_net ?></strong></p>

// to that
<p><strong x-text="price"><?= $product->rockcommerce_net ?></strong></p>
```

Now whenever you change the amount the price should update instantly! 🚀

Next, let's add a plus and minus button:

```php
<div>
  <button @click="minus">-</button>
  <input type="number" x-model="amount" value="1" />
  <button @click="plus">+</button>
</div>
```

## Variations

Finally let's add our product's variations!

```php
<?php foreach($product->variations() as $variation): ?>
  <div <?= $variation->rcAttributes() ?>>
    <div><strong><?= $variation->title() ?></strong></div>
    <div>
      <?php foreach($variation->options() as $option): ?>
        <a href <?= $option->rcAttributes() ?>>
          <?= $option->title ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
```

Once you add this code you should already be able to click on the variation options and if you set a price for that option in the backend the product's price should update immediately.

But not only the price will update - RockCommerce will also add or remove the `rc-active` class from each option as you select/unselect it!

The final working code is this:

```php
<div <?= $product->rcAttributes() ?>>
  <img src="https://placehold.co/300x300/jpg" />
  <h2><?= $product->title ?></h2>
  <p><strong x-text="price"><?= $product->rockcommerce_net ?></strong></p>
  <?php foreach($product->variations() as $variation): ?>
    <div <?= $variation->rcAttributes() ?>>
      <div><strong><?= $variation->title() ?></strong></div>
      <div>
        <?php foreach($variation->options() as $option): ?>
          <a href <?= $option->rcAttributes() ?>>
            <?= $option->title ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <div>
    <button @click="minus">-</button>
    <input type="number" x-model="amount" value="1" />
    <button @click="plus">+</button>
  </div>
  <a class="uk-button uk-button-primary" href="#" @click.prevent='addToCart'>Add to cart</a>
</div>
```
