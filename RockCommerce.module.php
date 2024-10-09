<?php

namespace ProcessWire;

use RockCommerce\Cart;
use RockCommerce\Carts;
use RockCommerce\Frontend;
use RockCommerce\InvoiceGroup;
use RockCommerce\Invoices;
use RockCommerce\Order;
use RockCommerce\Orders;
use RockCommerce\ProductPage;
use RockCommerce\RootPage;
use RockCommerce\Taxrate;
use RockCommerce\Taxrates;
use RockCommerce\TaxSelect;
use RockCommerce\Variation;
use RockCommerce\Variations;
use RockCommerce\Webhook;
use RockCommerce\Webhooks;
use RockForms\MultiStepForm;
use RockMoney\Money;

/**
 * Make RockCommerce available via rockcommerce() function
 */
function rockcommerce(): RockCommerce
{
  return wire()->modules->get('RockCommerce');
}

/**
 * @author Bernhard Baumrock, 24.07.2023
 * @license COMMERCIAL PLEASE DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 *
 * @method Order createOrder(string $subject, Money|float $net, Money|float $vat, Money|float $total, string $email, array $pageProperties = [], array $meta = []) Hookable method to create an order
 */
class RockCommerce extends WireData implements Module, ConfigurableModule
{
  const prefix = 'rockcommerce_';
  const maintenance = 'rockcommerce_maintenance';
  const permission_variations = 'rockcommerce-variations';

  const session_cart = 'rockcommerce_cart';
  const viewFolders = [
    '/site/templates/RockCommerce/frontend',
    '/site/modules/RockCommerce/frontend',
  ];
  const webhookurl = '/rockcommerce/webhook';

  const pricetype_net = 1;
  const pricetype_gross = 0;
  const pricetype_runtime = 2; // not yet implemented

  /** @var MultiStepForm */
  public $checkout;

  /** @var array */
  public $findRawVariationOptions;

  public $frontend;

  private $jsConfig;
  private $jsFiles = [];

  public $maxamount;
  public $minamount;
  public $noComplexPricing = false;

  public $priceType = self::pricetype_net;

  /** @var WireArray */
  public $productTemplates = false;

  public $showHelp = false;

  private $taxrates = false;

  public $taxselect;

  public function init()
  {
    // set default values
    if ($this->minamount === null) $this->minamount = 1;
    if ($this->maxamount === null) $this->maxamount = 10;

    // initialize module and api
    $this->loadConfig();
    $this->wire('rockcommerce', $this);
    $this->loadProductTemplates();

    // load submodules
    $this->frontend = new Frontend();
    $this->checkout = new MultiStepForm();
    $this->taxselect = new TaxSelect();

    // js config
    // will be available in fronend via RockCommerce.config
    // as soon as the RockCommerce:ready event has been fired
    $this->jsConfig = new WireData();
    $this->js('minamount', $this->minamount);
    $this->js('maxamount', $this->maxamount);
    $this->js('root', wire()->config->urls->root);

    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->watch($this);
    $rm->watch(
      what: __DIR__ . "/classLoader/Product.php",
      options: ['trigger' => $this],
    );

    // things to do while developing
    $this->dev();

    // trigger maintenance from time to time
    $expire = 60 * 60 * 1; // every hour
    $this->wire->cache->get(self::maintenance, $expire, function () {
      $this->maintenance();
    });

    // minify all js files during development
    $rm->minify(__DIR__ . "/RockCommerceOLD.js");
    $rm->minify(__DIR__ . "/alpine");
    $hookFile = $this->wire->config->paths->templates . "scripts/RCHooks.js";
    if (is_file($hookFile)) $rm->minify($hookFile);

    // register AJAX endpoints
    rockfrontend()->addAjaxFolder('rockcommerce', __DIR__ . '/ajax');

    // hooks
    wire()->addHookAfter(self::webhookurl, $this, "handleWebhook");
    wire()->addHookAfter(
      'Page::render',
      $this,
      'addMarkupHook',
      // priority 200 means RC assets are loaded after RF assets which have 100
      ['priority' => 200]
    );

    if (!$this->showRootPageInTree) $rm->hidePageFromTree($this->rootPage());

    // now that the module is ready we init the productpage
    // to attach hooks from the product trait
    (new ProductPage())->_init();
  }

  public function ready()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $admin = $this->wire->page->template == 'admin';
    $ajax = $this->wire->config->ajax;
    if ($admin and !$ajax) {
      // $rm->addStyles(__DIR__ . "/less/backend.less");
      $rm->addScripts(__DIR__ . "/js/RockCommerceBackend.min.js");

      // we need currency.min.js for for variation inputs
      $rm->addScripts(__DIR__ . '/../RockMoney/lib/currency.min.js');
    }

    // add rockloader
    rockloaders()->add('dots');
  }

  /**
   * Add markup to frontend pages
   */
  public function ___addMarkup($page, $html)
  {
    $config = $this->wire->config;
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

  protected function addMarkupHook(HookEvent $event): void
  {
    $newHtml = $this->addMarkup($event->object, $event->return);
    if (!$newHtml) return;
    $event->return = $newHtml;
  }

  public function addVariationsUrl(): string
  {
    return $this->wire->pages->get(2)->url
      . 'page/add/?parent_id='
      . $this->variationsPage();
  }

  public function attrs($name, $attrs): string
  {
    $this->loadJS($name);
    return "
      x-data=$name
      $attrs
    ";
  }

  public function badge($str, $cls = '')
  {
    if (!"$str") return;
    return "<span class='uk-alert $cls rc-badge'>$str</span>";
  }

  /**
   * Logic triggered by mollie webhook
   * Hookable if custom actions should be needed.
   */
  public function ___callWebhook(string $paymentid): void
  {
    $this->debug("Payment ID: $paymentid");
    $payment = $this->rockmollie()->api()->payments->get($paymentid);
    $order = $this->getOrder($payment->metadata->orderid);
    if ($order) {
      $this->debug("Order: " . $order);
      $order->setPaymentStatus($payment->status);
    } else {
      throw new WireException("No order found");
    }
  }

  /**
   * Get current cart or create a new one
   *
   * This is for use on the frontend where you can simply do this:
   * rockcommerce()->cart()->items() without throwing errors if no cart
   * exists
   */
  public function cart(): Cart
  {
    return $this->getCart(
      create: true,
      session: true,
    );
  }

  public function cartsPage(): Carts
  {
    return $this->wire->pages->get([
      'parent' => $this->rootPage(),
      'name' => 'carts',
    ]);
  }

  /**
   * Delete old webhook pages
   * This is triggered on every new webhook request.
   * The maximum setting can be adjusted on the Webhooks page.
   */
  public function cleanupOldWebhookPages(): void
  {
    $max = $this->webhooksPage()->max();
    $keep = $this->wire->pages->findIDs([
      'template' => Webhook::tpl,
      'sort' => '-id',
      'limit' => $max,
    ]);
    $selector = [
      "template" => Webhook::tpl,
      "id!=" => $keep,
    ];
    while ($p = $this->wire->pages->get($selector) and $p->id) {
      $this->wire->pages->delete($p);
      $this->wire->pages->uncacheAll();
    }
  }

  public function ___createOrder(
    string $subject,
    Money|float $net,
    Money|float $vat,
    Money|float $total,
    string $email,
    array $pageProperties = [],
    array $meta = [],
  ): Order {
    $money = rockmoney();
    $order = $this->wire->pages->newPage('template=' . Order::tpl);
    $order->set('title', $subject);
    $order->set(Order::field_net, $money->parse($net));
    $order->set(Order::field_vat, $money->parse($vat));
    $order->set(Order::field_total, $money->parse($total));
    $order->set(RockMigrations::field_email, $email);
    foreach ($pageProperties as $k => $v) {
      $order->set(Order::prefix . $k, $v);
    }
    $order->save();
    $order->meta('details', $meta);
    return $order;
  }

  /**
   * Debug method for webhook debugging
   *
   * This will only echo something if "debug" is set to true in the
   * $config->rockcommerce array.
   */
  private function debug($str, $log = false): void
  {
    if ($log) $this->wire->log($str);
    if (!$this->debug) return;
    echo "$str\n\n";
  }

  /**
   * This method will be executed only if debug mode is enabled,
   * which should only be the case during development.
   */
  private function dev(): void
  {
    $config = wire()->config;
    if (!$config->debug) return;
    if ($config->ajax) return;
    if ($config->external) return;

    // we compile via RockAssets module, so if that does not exist, we exit
    /** @var RockAssets $assets */
    $assets = wire()->modules->get('RockAssets');
    if (!$assets) return;

    // compile all alpine js components to one JS file
    // wire()->config->debug = false;
    // wire()->config->rockassetsForceRecompile = true;
    $assets
      ->add('/site/modules/RockJavaScriptHooks/Hooks.js')
      ->add('/site/modules/RockMoney/RockMoney.min.js')
      ->add('/site/modules/RockMoney/lib/currency.min.js')

      // load alpine components, with main component first
      ->add(__DIR__ . '/alpineNEW/RockCommerceNEW.js')
      // then load all other components from alpine folder
      ->addAll(__DIR__ . '/alpineNEW')
      // finally load alpinejs
      ->add(__DIR__ . '/lib/alpine.min.js', preventMinify: true)
      ->saveTo(
        __DIR__ . '/dst/RockCommerce.min.js',
        minify: true,
      );
  }

  public function findRawVariationOptions(): array
  {
    // get options from cache if already loaded
    if ($this->findRawVariationOptions) return $this->findRawVariationOptions;

    // find raw variations
    $arr = wire()->pages->findRaw(
      ['parent' => $this->variationsPage()],
      [
        'title',
        Variation::field_options . '.title',
      ],
      ['nulls' => true]
    );

    $result = [];
    foreach ($arr as $varid => $fields) {
      foreach ($fields[Variation::field_options] as $optid => $data) {
        $result[] = [
          'id' => $optid,
          'varid' => $varid,
          'variation' => $fields['title'],
          'option' => $data['title'],
        ];
      }
    }
    return $result;
  }

  /**
   * Get Cart
   *
   * Returns false if cart is not found.
   *
   * If you want to create a cart on the fly (eg frontend) use cart() instead.
   *
   * Usage:
   * $rockcommerce->getCart(123);
   * $rockcommerce->getCart("abc");
   */
  public function getCart(
    int|string $id = null,
    $create = false, // create if not exists?
    $session = false, // save to session?
  ): Cart|false {
    if (!$id) $id = $this->wire->session->get(self::session_cart);
    $cart = $this->wire->pages->get($id);
    if ($cart instanceof NullPage && $create) $cart = $this->newCart();
    if (!$cart instanceof Cart) return false;
    if ($session) $this->setCart($cart);
    return $cart;
  }

  public function getDefaultTaxrate(): float
  {
    $taxrate = wire()->pages->get([
      'template' => Taxrate::tpl,
      Taxrate::field_isdefault => 1,
    ]);
    if ($taxrate->id) {
      return $taxrate->getFormatted(Taxrate::field_taxrate);
    }
    return 0;
  }

  /**
   * Get file either from templates/rockcommerce
   * or from module frontend folder
   */
  public function getFile(string $file): string
  {
    $paths = [
      wire()->config->paths->templates . 'RockCommerce/frontend',
      __DIR__ . '/frontend',
    ];

    $file = Paths::normalizeSeparators($file);
    foreach ($paths as $path) {
      $path = Paths::normalizeSeparators($path);
      $f = rtrim($path, '/') . '/' . ltrim($file, '/');
      if (file_exists($f)) return $f;
    }

    return '';
  }

  // public function getJsConfig($json = false): array|string
  // {
  //   $config = [
  //     'debug' => $this->wire->config->debug,
  //     'currency' => rockmoney()->currencyStr ?: 'EUR',
  //     'locale' => $this->frontendLocale ?: 'de-DE',
  //     'priceType' => $this->priceType() ?: 'net',
  //   ];
  //   if ($json) return json_encode($config);
  //   return $config;
  // }

  public function getOrder(string $orderid = null): Order|false
  {
    if ($orderid === null) $orderid = $this->wire->input->get('id', 'string');
    $order = $this->wire->pages->get([
      'template' => Order::tpl,
      'name' => $orderid,
    ]);
    if (!$order->id) return false;
    return $order;
  }

  /**
   * Get a RockCommerce Product
   * Returns false if the found page is not a product.
   */
  public function getProduct(Page|string|int $page): Page|false
  {
    if (!$page instanceof Page) $page = $this->wire->pages->get((string)$page);
    if (!$page->isProduct) return false;
    return $page;
  }

  /**
   * Get all product templates
   */
  private function getProductTemplates(): WireArray
  {
    $templates = new WireArray();
    foreach ($this->wire->templates as $tpl) {
      if (!$tpl->fields->has("name=" . ProductPage::field_canbebought)) continue;
      $templates->add($tpl);
    }
    return $templates;
  }

  /**
   * Get array of taxrates
   * Example:
   * [
   *   '20.00' => '20.00%',
   * ]
   */
  public function getTaxrates(): array
  {
    if ($this->taxrates) return $this->taxrates;

    // parse taxrates from config
    $arr = explode(",", (string)$this->rawtaxrates);
    $arr = array_map(function ($el) {
      return (float)trim($el);
    }, $arr);
    $taxrates = array_filter($arr);
    $arr = [];
    foreach ($taxrates as $v) $arr[(string)$v] = "$v%";
    return $this->taxrates = $arr;
  }

  public function handleWebhook(HookEvent $event)
  {
    $method = $this->wire->input->requestMethod();
    if ($method !== 'POST') return; // 404

    // create webhook log page
    $log = $this->wire->pages->new("template=" . Webhook::tpl
      . ",parent=" . $this->webhooksPage());

    // cleanup old webhook pages
    $this->cleanupOldWebhookPages();

    // save received input
    $inputdata = $this->wire->input->post->getArray();
    $log->meta(Webhook::meta_input, json_encode($inputdata));
    ob_start();

    // we never need tracy on webhooks
    \Tracy\Debugger::$showBar = false;

    echo "RockCommerce Rocks!\n\n";

    $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    echo "$actual_link\n\n";

    // get payment and update order status
    $paymentid = $this->wire->input->post('id', 'string');
    if (!$paymentid) return true;
    try {
      $this->callWebhook($paymentid);
    } catch (\Throwable $th) {
      $this->debug($th->getMessage(), true);
    }

    // save output to log
    $output = ob_get_clean();
    $log->meta(Webhook::meta_output, $output);

    // dont render any html
    echo $output;
    return true;
  }

  /**
   * Host without trailing slash
   */
  public function host(): string
  {
    return rtrim('https://' . $_SERVER['HTTP_HOST'], '/');
  }

  /**
   * Add JS tag on frontend to init RCSite object
   */
  // public function initRockCommerceSite(HookEvent $event): void
  // {
  //   /** @var Page $page */
  //   $page = $event->object;
  //   if ($page->template == 'admin') return;
  //   if ($this->wire->config->ajax) return;
  //   if (!$page->viewable()) return;
  //   $event->return = str_replace(
  //     "</body>",
  //     "<script>document.addEventListener('DOMContentLoaded', function() {
  //       RockCommerceSite.init({$this->getJsConfig(true)});
  //     });</script></body>",
  //     $event->return
  //   );
  // }

  public function invoicesPage(): Invoices
  {
    return $this->wire->pages->get([
      'template' => Invoices::tpl,
      'parent' => $this->rootPage(),
    ]);
  }

  /**
   * Set JS config value
   */
  public function js(string $prop, mixed $val): void
  {
    $this->jsConfig->set($prop, $val);
  }

  /**
   * Load config settings from $config->rockcommerce
   * This makes it possible to have settings in the module's config, but you can
   * override them via config-local.php if you need.
   */
  private function loadConfig(): void
  {
    $overrides = $this->wire->config->rockcommerce;
    if (!is_array($overrides)) return;
    foreach ($overrides as $k => $v) $this->set($k, $v);
  }

  public function loadJS($jsfile): void
  {
    $load = $this->jsFiles;
    $load[$jsfile] = true;
    $this->jsFiles = $load;
  }

  /**
   * Populate $rockcommerce->productTemplates on module init()
   */
  private function loadProductTemplates(): void
  {
    $cache = "rc-product-templates";

    // reset cache on modules refresh
    $this->wire->addHookAfter("Modules::refresh", function () use ($cache) {
      $this->wire->cache->delete($cache);
    });

    // populate templates from cache
    $templates = $this->wire->cache->get($cache, function () {
      return (string)$this->getProductTemplates();
    });
    $templates = (new WireArray())->setArray(explode("|", $templates));
    $this->productTemplates = $templates;
  }

  public function mailfrom(): string
  {
    $host = $this->wire->config->httpHost;
    return $this->mailfrom ?: "RockCommerce@$host";
  }

  public function mailfromName(): string
  {
    return $this->mailfromName ?: "RockCommerce";
  }

  public function maintenance(): void
  {
    // remove unused variations
    $oldVariations = $this->wire->pages->find([
      'parent' => $this->variationsPage(),
      'title' => '',
      'created<' => time() - 60 * 60, // older than 1h
    ]);
    foreach ($oldVariations as $p) $p->delete();
  }

  public function ___markupBody(): string
  {
    $conf = json_encode($this->jsConfig->getArray());
    $conf = $this->wire->sanitizer->entities($conf);
    $out = '<div id="rockcommerce">';

    // load cart component
    // this hidden element makes sure that we load the component even
    // if there is no cart element in the DOM. If there is one, the init()
    // method will take care of loading the cart only once.
    // data-ajax=false tells RockCommerce not to load items via ajax
    $this->loadJS('RcCart');
    $out .= "<div x-data=RcCart data-noajax hidden></div>";

    // rockcommerce has to be the last alpine element
    // this is to ensure that all other components are ready at this point
    $out .= "<div x-data=RockCommerceNEW data-config='$conf' hidden></div>";
    $out .= "<div x-data=RockCommerce data-config='$conf' hidden></div>";

    $out .= "</div>";

    return $out;
  }

  public function migrate()
  {
    $this->migrateCleanup();
    $rm = rockmigrations();

    // add permissions
    $rm->createPermission(
      self::permission_variations,
      'User can manage product variations'
    );

    $rm->installModule("InputfieldRcPricing");
    $rm->installModule("FieldtypeRcVariations");
    $rm->installModule("InputfieldRcVariations");

    $rootPage = $rm->createPage(
      template: RootPage::tpl,
      parent: 2,
      name: "rockcommerce",
      title: "RockCommerce",
      status: ["hidden"],
    );
    $rm->createPage(
      template: Orders::tpl,
      parent: $rootPage,
      name: "orders",
      title: "Orders",
    );
    $rm->createPage(
      template: Webhooks::tpl,
      parent: $rootPage,
      name: "webhooks",
      title: "Webhooks",
      status: ["hidden"],
    );
    $invoicesPage = $rm->createPage(
      template: Invoices::tpl,
      parent: $rootPage,
      name: "invoices",
      title: "Invoices",
      status: ["hidden", "locked"],
    );
    $rm->createPage(
      template: InvoiceGroup::tpl,
      parent: $invoicesPage,
      name: "rockcommerce",
      title: "RockCommerce",
      status: ["hidden"],
    );
    $rm->createPage(
      template: Carts::tpl,
      parent: $rootPage,
      title: 'Carts',
      name: 'carts',
      status: [],
    );
    $rm->createPage(
      template: Variations::tpl,
      parent: $rootPage,
      title: 'Variations',
      name: 'variations',
    );
    $rm->createPage(
      template: Taxrates::tpl,
      parent: $rootPage,
      title: 'Taxrates',
      name: 'taxrates',
    );

    // set tags for all fields
    foreach ($this->wire->fields as $field) {
      if (!str_starts_with($field->name, "rockcommerce_")) continue;
      $rm->setFieldData($field, ['tags' => 'RockCommerce']);
    }

    // migrate all products
    $fired = false;
    foreach ($this->wire->templates as $tpl) {
      $p = $this->wire->pages->newPage("template=$tpl");
      if (!$p->isProduct) continue;

      // trigger _migrate only once for the whole project
      // this is so that when using multiple templates using the product trait
      // we don't end up getting the _migrate() of the trait being triggered
      // more than once
      if ($fired) continue;
      $fired = true;
      $p->_migrate();
    }
  }

  public function migrateCleanup(): void
  {
    $rm = rockmigrations();
    // nothing to do yet
  }

  /**
   * Create a new cart and save it to the database
   */
  public function newCart(): Cart
  {
    return $this->wire->pages->new([
      'template' => Cart::tpl,
    ]);
  }

  public function ordersPage(): Orders
  {
    return $this->wire->pages->get([
      'parent' => $this->rootPage(),
      'template' => Orders::tpl,
    ]);
  }

  public function priceType(): string
  {
    if ($this->priceType == self::pricetype_gross) return "gross";
    return "net";
  }

  public function processPage(): Page
  {
    return $this->wire->pages->get([
      'parent' => 2,
      'name' => 'rockcommerce',
    ]);
  }

  public function processUrl(string $url): string
  {
    return $this->processPage()->url . $url;
  }

  public function rockfrontend(): RockFrontend
  {
    return $this->wire->modules->get('RockFrontend');
  }

  public function rockmollie(): RockMollie
  {
    return $this->wire->modules->get('RockMollie');
  }

  public function rootPage(): RootPage
  {
    return $this->wire->pages->get([
      // 'parent' => 2,
      'template' => RootPage::tpl,
    ]);
  }

  public function setCart(Cart $cart): void
  {
    $this->wire->session->set(self::session_cart, $cart->name);
  }

  // /**
  //  * Generate swap container for HTMX requests
  //  *
  //  * Usage example:
  //  * echo $rc->swap([
  //  *  'partials/cart-sidebar',
  //  * ]);
  //  */
  // public function swap(array $files): string
  // {
  //   $out = "\n<div id='rc-swap' hidden>";

  //   // before we render all views we make sure to call _init.php
  //   $this->view('_init.php');

  //   // then we render all requested views
  //   foreach ($files as $file) {
  //     // note: for security only partials can be swapped!
  //     $out .= $this->view("partials/$file");
  //   }

  //   $out .= "</div>";
  //   return $out;
  // }

  public function ___taxrate($product): float
  {
    return (float)$this->wire->config->rockcommerceTaxrate;
  }

  public function taxrates(): PageArray
  {
    return $this->wire->pages->find([
      'parent' => $this->rootPage()->path . 'taxrates',
    ]);
  }

  /**
   * Convert path to url
   * @return string
   */
  public function toUrl(string $path, $cachebuster = false): string
  {
    return rockfrontend()->toUrl($path, $cachebuster);
  }

  /**
   * Return a rockcommerce url in form /rc/whatever
   *
   * If second param is true this will return the current host, which will
   * be either example.com or example.com.ddev.site or 12345.ngrok.app etc.
   *
   * Usage:
   * $rc->url("/foo/bar") --> /rc/foo/bar
   * $rc->url("/foo/bar", true) --> https://example.com/rc/foo/bar
   */
  public function url($str, $http = false): string
  {
    if ($http) $http = $this->host();
    return $http . '/' . ltrim($str, "/");
  }

  public function variationsPage(): Variations
  {
    return $this->wire->pages->get([
      'parent' => $this->rootPage(),
      'name' => 'variations',
    ]);
  }

  public function ___view(
    string $file,
    array|Page $vars = [],
    Page $page = null,
  ): \Latte\Runtime\Html|string {
    $file = $this->getFile($file);
    $vars = [
      'rc' => $this,
      'rf' => rockfrontend(),
    ];
    $out = rockfrontend()->render($file, $vars);
    if ($out instanceof \Latte\Runtime\Html) return $out;
    return (string)$out;
  }

  /**
   * Render a frontend file on one of the frontend folders
   */
  public function viewFile(string $file): string|false
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $root = $this->wire->config->paths->root;
    foreach (self::viewFolders as $dir) {
      $path = $rm->path("$root/$dir/$file");
      if (is_file($path)) return $path;
    }
    return false;
  }

  /**
   * Get sanitized viewfolders array
   */
  public function viewFolders(
    $absolute = false,
    $reverse = false,
  ): array {
    $folders = array_map(function ($item) use ($absolute) {
      $dir = rtrim(Paths::normalizeSeparators($item), "/");
      if ($absolute) return rtrim($this->wire->config->paths->root, "/") . $dir;
      return $dir;
    }, self::viewFolders);
    if ($reverse) return array_reverse($folders);
    return $folders;
  }

  public function webhooksPage(): Webhooks
  {
    return $this->wire->pages->get([
      'template' => Webhooks::tpl,
      'parent' => $this->rootPage(),
    ]);
  }

  public function webhookUrl(): string
  {
    return $this->host() . self::webhookurl;
  }

  public function ___install(): void
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->log("Installing RockCommerce");
    $this->init();
    $this->migrate();
  }

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    $name = strtolower($this);
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Documentation & Updates',
      'icon' => 'life-ring',
      'value' => "<p>Hey there, coding rockstars! 👋</p>
        <ul>
          <li><a class=uk-text-bold href=https://www.baumrock.com/modules/$name/docs>Read the docs</a> and level up your coding game! 🚀💻😎</li>
          <li><a class=uk-text-bold href=https://www.baumrock.com/rock-monthly>Sign up now for our monthly newsletter</a> and receive the latest updates and exclusive offers right to your inbox! 🚀💻📫</li>
          <li><a class=uk-text-bold href=https://github.com/baumrock/$name>Show some love by starring the project</a> and keep me motivated to build more awesome stuff for you! 🌟💻😊</li>
          <li><a class=uk-text-bold href=https://paypal.me/baumrockcom>Support my work with a donation</a>, and together, we'll keep rocking the coding world! 💖💻💰</li>
        </ul>",
    ]);

    $inputfields->add([
      'type' => 'markup',
      'label' => 'Config Settings Override',
      'value' => "You can override any setting in your config.php file:<br>
        <pre class=uk-margin-small-top><code>\$config->rockcommerce['setting'] = 'newvalue';</code></pre>",
      'icon' => 'life-ring',
      'notes' => 'Use Shilft+Click on any field label to copy fieldname to clipboard.',
    ]);

    $fs = new InputfieldFieldset();
    $fs->label = 'E-Mails';
    $fs->icon = 'cogs';
    $fs->collapsed = Inputfield::collapsedYes;
    $inputfields->add($fs);

    // admin email address
    $fs->add([
      'type' => 'email',
      'name' => 'adminmail',
      'label' => 'Admin E-Mail',
      'icon' => 'envelope-o',
      'value' => $this->adminmail,
      'notes' => "Send an email to this address on every new order.",
      'columnWidth' => 33,
    ]);

    // email address used to send emails
    $fs->add([
      'type' => 'email',
      'name' => 'mailfrom',
      'label' => 'Mailfrom E-Mail Address',
      'icon' => 'envelope-o',
      'value' => $this->mailfrom,
      'columnWidth' => 33,
    ]);

    // email name used to send emails
    $fs->add([
      'type' => 'text',
      'name' => 'mailfromName',
      'label' => 'Mailfrom E-Mail Name',
      'icon' => 'user-o',
      'value' => $this->mailfromName,
      'columnWidth' => 33,
    ]);

    // Product
    $fs = new InputfieldFieldset();
    $fs->label = 'Products';
    $fs->icon = 'cubes';
    $fs->collapsed = Inputfield::collapsedYes;
    $inputfields->add($fs);

    $fs->add([
      'type' => 'integer',
      'name' => 'minamount',
      'label' => 'Minimum Amount',
      'value' => $this->minamount,
      'columnWidth' => 50,
      'notes' => 'Defaults to 1 if empty.',
    ]);
    $fs->add([
      'type' => 'integer',
      'name' => 'maxamount',
      'label' => 'Maximum Amount',
      'value' => $this->maxamount,
      'columnWidth' => 50,
      'notes' => 'Defaults to 10 if empty.',
    ]);

    // Pricing
    $fs = new InputfieldFieldset();
    $fs->label = 'Pricing';
    $fs->icon = 'money';
    $fs->collapsed = Inputfield::collapsedYes;
    $inputfields->add($fs);

    // taxrates
    $fs->add([
      'type' => 'text',
      'name' => 'rawtaxrates',
      'label' => 'Taxrates',
      'notes' => 'Enter comma-seperated list of taxrates, eg "10.5, 20".
        The first item in the list will be used as default value on new products.',
      'value' => $this->rawtaxrates,
      'icon' => 'percent',
      'required' => true,
      'columnWidth' => 50,
    ]);

    // complex pricing?
    $fs->add([
      'type' => 'checkbox',
      'name' => 'noComplexPricing',
      'label' => 'Allow only simple pricing for all products',
      'checked' => $this->noComplexPricing ? 'checked' : '',
      'columnWidth' => 50,
    ]);

    // frontend pricing
    $fs->add([
      'type' => 'toggle',
      'name' => 'priceType',
      'label' => 'Frontend Pricing Display',
      'formatType' => 0, // integer
      'labelType' => 100, // custom
      'useOther' => 0,
      'icon' => 'money',
      'yesLabel' => 'net',
      'noLabel' => 'gross',
      'defaultOption' => 'net',
      'value' => $this->priceType,
      'notes' => 'Choose how to display pricing on the frontend.',
      'columnWidth' => 50,
    ]);

    $fs->add([
      'type' => 'text',
      'name' => 'frontendLocale',
      'label' => 'Frontend Locale (JavaScript)',
      'notes' => 'Used to render prices on the frontend. Default is de-DE.',
      'value' => $this->frontendLocale,
      'icon' => 'money',
      'columnWidth' => 50,
    ]);

    $link = $this->wire->pages->get(2)->url . "module/edit?name=RockMoney";
    $fs->add([
      'type' => 'markup',
      'value' => "Note: PHP pricing settings are defined in the <a href=$link>RockMoney module</a>.",
    ]);

    // Order
    $fs = new InputfieldFieldset();
    $fs->label = 'Orders';
    $fs->icon = 'check-square-o';
    $fs->collapsed = Inputfield::collapsedYes;
    $inputfields->add($fs);

    $fs->add([
      'type' => 'text',
      'name' => 'publicOrderPageUrl',
      'label' => 'Public Orderpage-Url',
      'description' => 'The url of the page that you created for handling order updates, eg after payment to show the success message.',
      'notes' => 'Make sure the url has a trailing slash or not depending on your site settings - otherwise it will redirect to the other version and lose query parameters!
        Example: If the final url is https://example.com/order/?id=123 you have to enter /order/ in this field.',
      'value' => $this->publicOrderPageUrl,
    ]);

    // Backend
    $fs = new InputfieldFieldset();
    $fs->label = 'Backend';
    $fs->icon = 'cogs';
    $fs->collapsed = Inputfield::collapsedYes;
    $inputfields->add($fs);

    $fs->add([
      'type' => 'checkbox',
      'name' => 'showRootPageInTree',
      'checked' => $this->showRootPageInTree ? 'checked' : '',
      'label' => 'Show rootpage in pagetree',
    ]);

    $fs->add([
      'type' => 'checkbox',
      'name' => 'showHelp',
      'checked' => $this->showHelp ? 'checked' : '',
      'label' => 'Show help notes to superusers',
    ]);

    return $inputfields;
  }
}
