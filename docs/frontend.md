# Frontend

## Alpine.js

RockCommerce uses Alpine.js to make it as easy as it can get to add e-commerce logic to your frontend just by adding magic attributes to your markup.

If you have never used Alpine.js before, I can recommend this video as a great introduction:

https://www.youtube.com/watch?v=1rDU3Y0Wlnw

Also check out the docs at https://alpinejs.dev/

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
