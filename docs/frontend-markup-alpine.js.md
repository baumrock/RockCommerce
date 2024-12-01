# Frontend Markup + Alpine.js

ProcessWire leaves it completely up to the developer how they want to handle their frontend. This makes it hard to develop modules that add functionality to the frontend, because every frontend is different!

On the other hand, an E-Commerce module needs to be secure, which means that we have to do all the calculations on the server side. How to solve this?

Our first approach was using HTMX and making every aspect of RockCommerce hookable. As a consequence, changing even the smallest detail required the developer to add a hook to make that change. We all know that hooks are great, but we also know that they add some boilerplate code if all you want to do is change a single line of markup.

Alpine.js to the rescue!

## Alpine.js

Alpine.js provides the perfect balance between flexibility and functionality for our e-commerce needs. It allows you to:

1. Write your own custom markup exactly how you want it
2. Easily add e-commerce features by using simple HTML attributes
3. Access cart data, product info, and order details through Alpine's reactive data system
4. Keep your frontend code clean and declarative without complex JavaScript

This means you can build your store's frontend the way you envision it, while RockCommerce handles all the complex e-commerce logic behind the scenes. No need to fight with rigid templates or write extensive JavaScript - just add magic attributes to your HTML and you're ready to go.

If you have never used Alpine.js before, I can recommend this video as a great introduction:

https://www.youtube.com/watch?v=1rDU3Y0Wlnw

Also check out the docs at https://alpinejs.dev/

In RockCommerce, the main concept is that we register a global `RockCommerce` object, which has access to various components like `RcCart` or `RcProduct`. If you have purchased RockCommerce, I recommend you to check out those components and see how they work. A simple one to start is `/site/modules/RockCommerce/alpine/RcTaxselect.js`.

## Markup Injection

RockCommerce will automatically inject the following markup into your frontend:

```html
<div id="rockcommerce">
  <div x-data="RcCart" ...></div>
  <div x-data="RcTaxrates" ...></div>
  <div x-data="RockCommerce" data-config="{...}" ...></div>
</div>
```

This is done by the `addMarkup()` method in the `RockCommerce` module and several conditions have to be met for this to happen:

```php
public function ___addMarkup($page, $html)
{
  $config = wire()->config;
  if ($config->ajax) return;
  if ($config->external) return;
  if (!$page->viewable()) return;
  if ($page->template == 'admin') return;
  if (!strpos($html, '</head>')) return;

  // this is to support disabling rockcommerce markup via hook
  if ($config->noRockCommerceMarkup) return;

  // first we add the body, because there some scripts are added
  $html = str_replace(
    '</body>',
    $this->markupBody($page) . '</body>',
    $html
  );

  return $html;
}
```

If you find that RockCommerce is injecting markup in unwanted places, please let us know by opening a topic on the [ProcessWire forum](https://processwire.com/talk/).

If it's a specific case only for your project, you can disable it by setting `$config->noRockCommerceMarkup` to `true` with a hook:

```php
wire()->addHookBefore('RockCommerce::addMarkup', function ($event) {
  $page = $event->arguments(0);
  // disable RockCommerce markup on the homepage
  if ($page->id === 1) wire()->config->noRockCommerceMarkup = true;
});
```

Please note that RockCommerce needs this markup to be able to function, so if you disable it, you need to check if you didn't break the e-commerce functionality of your site!
