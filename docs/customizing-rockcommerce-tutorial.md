# Customizing RockCommerce Tutorial

So you followed the [Quickstart Tutorial](./quickstart-tutorial/) and now you want to customize RockCommerce to your liking?

If you noticed, by default, RockCommerce doesn’t force you to define products in any specific way. How they look or which fields they have is totally up to you, just like any ProcessWire page.

That means, if we want to extend RockCommerce, we always need to extend both the frontend and the backend of our site. Let's start with the frontend to see what we want to achieve.

## Updating the Products List

<img src=https://i.imgur.com/llPGAK6.png alt='Products List' class=blur>

To update the products list we can use regular ProcessWire principles:

- Add an image field to the product template
- Upload images to our products
- Output the image markup in the PHP template file

So first, please create an image field and add it to the product template:

<img src=https://i.imgur.com/XH6TVBB.png alt='Add Image Field' class=blur>

Then upload images to your products.

<img src=https://i.imgur.com/ez9ZMOg.png alt='Upload Images' class=blur>

Next, we can add the image to the products list in `home.php` in the `products` region:

```php
// replace this
<img src="https://placehold.co/300x300/jpg" />

// with this
<img src="<?= $product->productimage->size(300, 300)->url ?>" />
```

<img src=https://i.imgur.com/QxcnfU4.png alt='Products List with Image' class=blur>

## Updating Cart Items

To update the product image in the cart, we cannot use the same approach, because remember it is managed by Alpine, so we depend on the information available through the Items array object in the RcCart component.

To better understand what we are going to do, please add a product to the cart and check the console again, like in the previous tutorial:

<img src=https://i.imgur.com/bkbp7ut.png alt='Console Output' class=blur>

Notice anything? Yes, the pic and description are empty! That’s because RockCommerce doesn’t know about our image or description fields… yet.

But no worries — with Processwire you are always just a hook away from the solution!

### Add the missing data

Inside the file `site/rockcommerce.php`, we add another hook:

`label: /site/rockcommerce.php`
```php
// Add a hook to modify the Items array
wire()->addHookAfter('Item::getJsonArray', function ($event) {
  $data = $event->return;
  bd($data);
});
```

TracyDebugger will show us the `data` object:

<img src=https://i.imgur.com/ljCRLWP.png alt='Hook Data' class=blur>

Now all we have to do is populate the `pic` item of the array. So we update our hook to this:

```php
// Add a hook to modify the Items array
wire()->addHookAfter('Item::getJsonArray', function ($event) {
  $data = $event->return;

  // Get the product page using the ID in the data
  $product = wire()->pages->get($data['product']);
  if (!$product->id) {
    // Return if the product doesn't exist
    return;
  }

  if ($product->rockcommerce_productfields->product_image) {
    // Add the product image URL if it exists
    $data['pic'] = $product->rockcommerce_productfields->product_image->width(
      60
    )->url;
  }

  // Add the product description if it's available
  $data['description'] =
    $product->rockcommerce_productfields->product_description ?? null;

  // Set the modified data back to the event return
  $event->return = $data;
});
```

Now all that is left is to replace the placeholder image with the actual product image in `home.php`. Remember that we are now in a dynamic frontend component, so we need to use JavaScript syntax rather than PHP like before:

```php
// replace this
<div class="uk-width-1-4"><img src="https://placehold.co/60x60/jpg" /></div>

// with this
<div class="uk-width-1-4"><img :src="item.pic" /></div>
```

The `:src` tells Alpine to use the `pic` value as the source of the image. Simple as that!

<img src=https://i.imgur.com/1nUZ5bf.png alt='Cart Items with Image' class=blur>

That looks good! Now that you learned how to customize RockCommerce, we can say goodbye. Nothing lasts forever, they say.

See you around!
