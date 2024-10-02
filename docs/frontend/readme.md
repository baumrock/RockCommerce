# Frontend

RockCommerce provides all the necessary tools to turn your PW site into a fully fledged online shop.

For that, RockCommerce will inject some scripts in the `<head>` and the following markup at the end of the `<body>` tag on every rendered page on the frontend:

```html
<div id="rockcommerce">
  <div x-data="rccart" ...></div>
  <div x-data="rockcommerce" ...></div>
</div>
```

If you want to disable all RockCommerce markup globally, you can do so by setting `$config->noRockCommerceMarkup` to `true` in your `config.php` file.

If you want to disable the RockCommerce markup on a specific page, you can do so by adding a hook like this in your `ready.php` file:

```php
wire()->addHookBefore('RockCommerce::addMarkup', function ($event) {
  $page = $event->arguments(0);
  // disable RockCommerce markup on the homepage
  if ($page->id === 1) wire()->config->noRockCommerceMarkup = true;
});
```

This ensures that AlpineJS is loaded and the RockCommerce object is available in the frontend:

```js
console.log(RockCommerce);
```

## View Files

Whenever RockCommerce needs to output some frontend markup, it will use the `view()` method of the `RockCommerce` class. This method will try to find the requested file in the following paths:

- `/site/templates/RockCommerce/frontend`
- `/site/modules/RockCommerce/frontend`

If the file exists, the `view()` method will return the rendered output. If the file does not exist, the `view()` method will return an empty string.

### Outputting View Files

You can output a view file from your templates like this:

```php
// echo output in a template file:
echo $rockcommerce->view('hello-world.html');

// or using tracy debugger, eg in site/ready.php:
bd($rockcommerce->view('hello-world.html'));
```

### Replacing View Files

If you want to replace the output of a view file, just create a file with the same name in the templates directory. RockCommerce will then use your file instead of the default one.

For example, you could create the file `site/templates/RockCommerce/frontend/hello-world.html` with the following content:

```html
<h1>Hello Universe</h1>
```

### Hooking View Files

If you don't want to replace the whole view file, but only add or remove some markup, you can hook into the `RockCommerce::view()` method and return your custom output - for example using RockFrontend's DOM tools:

```php
// site/ready.php
wire()->addHookAfter('RockCommerce::view', function ($event) {
  $file = $event->arguments(0);
  if ($file !== 'hello-world.html') return;

  // get output from event
  $output = $event->return;

  // outputs <h1>Hello World</h1>
  bd($output);

  // use RockFrontend's dom tools to modify the output
  // see https://www.baumrock.com/en/processwire/modules/rockfrontend/docs/dom/
  $dom = rockfrontend()->dom($output);
  $dom->filter('h1')->each(function ($node) {
    $node->addClass('my-universe');
    $node->setText('Hello Universe');
  });

  // write manipulated output back to variable
  $output = $dom->html();

  // outputs <h1 class="my-universe">Hello Universe</h1>
  bd($output);

  // write output back to event
  $event->return = $output;
});
```
