<?php

namespace ProcessWire;

use RockCommerce\Cart;
use RockCommerce\Carts;
use RockCommerce\InvoiceGroup;
use RockCommerce\Invoices;
use RockCommerce\Order;
use RockCommerce\Orders;
use RockCommerce\ProductPage;
use RockCommerce\RootPage;
use RockCommerce\Variations;
use RockCommerce\Webhook;
use RockCommerce\Webhooks;
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
 */
class RockCommerce extends WireData implements Module, ConfigurableModule
{
  const prefix = "rockcommerce_";

  const session_cart = 'rockcommerce_cart';
  const viewFolders = [
    '/site/templates/RockCommerce/frontend',
    '/site/modules/RockCommerce/frontend',
  ];
  const webhookurl = "/rockcommerce/webhook";

  const pricetype_net = 1;
  const pricetype_gross = 0;
  const pricetype_runtime = 2; // not yet implemented

  public $noComplexPricing = false;

  public $priceType = self::pricetype_net;

  /** @var WireArray */
  public $productTemplates = false;

  private $taxrates = false;

  public static function getModuleInfo()
  {
    return [
      'title' => 'RockCommerce',
      'version' => '0.0.1',
      'summary' => 'E-Commerce Module for ProcessWire',
      'autoload' => true,
      'singular' => true,
      'icon' => 'money',
      // PW 3.0.234 for repeater classes
      'requires' => [
        'ProcessWire>=3.0.234',
        'RockFrontend>=3.3.0',
        'RockMigrations>=3.36',
        'RockMoney>=1.0.1',
        'RockMollie>=1.0.0',
        'RockGrid>=2.2.0',
      ],
      'installs' => [
        'ProcessRockCommerce',
        'InputfieldRcPricing',
      ],
    ];
  }

  public function init()
  {
    // initialize module and api
    $this->loadConfig();
    $this->wire('rockcommerce', $this);
    $this->loadProductTemplates();

    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->watch($this);
    $rm->watch(
      what: __DIR__ . "/Product.php",
      options: ['trigger' => $this],
    );

    /** @var RockGrid $grid */
    $grid = $this->wire->modules->get('RockGrid');
    $grid->addDir(__DIR__ . "/RockGrid");

    // create assets
    $rm->minify(__DIR__ . "/RockCommerceBackend.js");
    $rm->minify(__DIR__ . "/RockCommerceFrontend.js");
    $rm->minify(__DIR__ . "/RockCommerceSite.js");
    $rm->minify(__DIR__ . "/Cart.js");

    // HTMX+AJAX
    $this->addHTMX(); // frontend endpoints
    $this->addAJAX(); // backend endpoints

    // hooks
    wire()->addHookAfter(self::webhookurl, $this, "handleWebhook");
    wire()->addHookAfter("Page::render",   $this, "initRockCommerceSite");

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
      $rm->addStyles(__DIR__ . "/less/backend.less");
      $rm->addScripts(__DIR__ . "/RockCommerceBackend.min.js");
    }
  }

  /** regular methods */

  /**
   * Add ajax endpoints for the RockCommerce backend (eg Price Editor)
   * Endpoints are located in /site/modules/RockCommerce/ajax.
   * Results will sent be returned as json encoded string.
   */
  public function addAJAX(): void
  {
    // attach hooks only for ajax requests or superusers
    $sudo = $this->wire->user->isSuperuser();
    $ajax = $this->wire->config->ajax;
    if (!($ajax or $sudo)) return;

    $files = $this->wire->files->find(__DIR__ . "/ajax", ['extensions' => 'php']);
    foreach ($files as $file) {
      $name = substr(basename($file), 0, -4);
      $this->wire->addHook("/rockcommerce/$name", function (HookEvent $event) use ($file, $name) {
        try {
          return json_encode($this->wire->files->render($file));
        } catch (\Throwable $th) {
          return json_encode([
            'error' => $th->getMessage(),
          ]);
        }
      });
    }
  }

  /**
   * Scan /htmx folder for endpoints and add them via hook
   */
  public function addHTMX(): void
  {
    foreach ($this->viewFolders(absolute: true, reverse: true) as $folder) {
      $endpoints = $this->wire->files->find(
        "$folder/htmx",
        ['extensions' => ['php']]
      );
      foreach ($endpoints as $endpoint) {
        $base = substr(basename($endpoint), 0, -4);
        $this->wire->addHook("/$base", function (HookEvent $event) use ($base) {
          // make htmx endpoints only available via ajax
          // superusers are allowed to access them directly (for debugging)
          $sudo = $this->wire->user->isSuperuser();
          $ajax = $this->wire->config->ajax;
          if (!$ajax && !$sudo) throw new WireException("No direct access");

          // render endpoint
          try {
            $view = $this->view("htmx/$base");
          } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $view = "<div class='uk-alert uk-alert-danger'>$msg</div>";
          }

          $swap = $this->wire->input->post('swap', 'string');
          if ($swap) $view .= $this->swap(explode(",", $swap));

          // return response
          return $view;
        });
      }
    }
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

  public function getJsConfig($json = false): array|string
  {
    $config = [
      'debug' => $this->wire->config->debug,
      'currency' => rockmoney()->currencyStr ?: 'EUR',
      'locale' => $this->frontendLocale ?: 'de-DE',
      'priceType' => $this->priceType() ?: 'net',
    ];
    if ($json) return json_encode($config);
    return $config;
  }

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
      if (!$tpl->fields->has("name=" . ProductPage::field_available)) continue;
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
   * Add JS tag on frontend to init RCSite object
   */
  public function initRockCommerceSite(HookEvent $event): void
  {
    /** @var Page $page */
    $page = $event->object;
    if ($page->template == 'admin') return;
    if ($this->wire->config->ajax) return;
    if (!$page->viewable()) return;
    $event->return = str_replace(
      "</body>",
      "<script>document.addEventListener('DOMContentLoaded', function() {
        RockCommerceSite.init({$this->getJsConfig(true)});
      });</script></body>",
      $event->return
    );
  }

  public function invoicesPage(): Invoices
  {
    return $this->wire->pages->get([
      'template' => Invoices::tpl,
      'parent' => $this->rootPage(),
    ]);
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

  public function migrate()
  {
    $rm = rockmigrations();
    $rm->installModule("InputfieldRcPricing");

    $rootPage = $rm->createPage(
      template: RootPage::tpl,
      parent: 1,
      name: "rc",
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

    // set tags for all fields
    foreach ($this->wire->fields as $field) {
      if (!str_starts_with($field->name, "rockcommerce_")) continue;
      $rm->setFieldData($field, ['tags' => 'RockCommerce']);
    }

    // migrate all products
    foreach ($this->wire->templates as $tpl) {
      $p = $this->wire->pages->newPage("template=$tpl");
      if (!$p->isProduct) continue;
      $p->_migrate();
    }
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

  /**
   * Method that renders the frontend folder when the rootpage is viewed
   * The heavy lifting is done by RockFrontend so that other modules can use
   * the same concept as well :)
   */
  public function renderFrontend(): string
  {
    return $this->rockfrontend()->viewFolders(
      self::viewFolders,
      [
        'trailingSlash' => $this->rootPage()->trailingslash(),
      ]
    );
  }

  public function setCart(Cart $cart): void
  {
    $this->wire->session->set(self::session_cart, $cart->name);
  }

  public function ___view(
    string $file,
    array|Page $vars = [],
    Page $page = null,
  ): \Latte\Runtime\Html|string {
    // make some defaults available in all frontend files:
    $defaults = [
      'rc' => $this,
      'rf' => rockfrontend(),
    ];

    // if custom vars are specified merge them
    $vars = array_merge($defaults, $vars);

    // on the first request we need to tell rockfrontend
    // which view folders to look into
    if (!$this->viewFoldersSet) {
      rockfrontend()->setViewFolders($this->viewFolders());
      $this->viewFoldersSet = true;
    }

    // use rockfrontend to render the file
    return rockfrontend()->view($file, $vars, $page);
  }

  /**
   * Generate swap container for HTMX requests
   *
   * Usage example:
   * echo $rc->swap([
   *  'partials/cart-sidebar',
   * ]);
   */
  public function swap(array $files): string
  {
    $out = "\n<div id='rc-swap' hidden>";

    // before we render all views we make sure to call _init.php
    $this->view('_init.php');

    // then we render all requested views
    foreach ($files as $file) {
      // note: for security only partials can be swapped!
      $out .= $this->view("partials/$file");
    }

    $out .= "</div>";
    return $out;
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
      'parent' => 1,
      'template' => RootPage::tpl,
    ]);
  }

  /**
   * Return a rockcommerce url in form /rc/whatever
   *
   * Usage:
   * $rc->url("/foo/bar") --> /rc/foo/bar
   * $rc->url("/foo/bar", true) --> https://example.com/rc/foo/bar
   */
  public function url($str, $http = false): string
  {
    return $this->rootPage()->url($http) . ltrim($str, "/");
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
    return rtrim($this->webhook, "/") . self::webhookurl;
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
    $fs->label = 'Mail Settings';
    $fs->icon = 'cogs';
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

    // Pricing
    $fs = new InputfieldFieldset();
    $fs->label = 'Pricing Settings';
    $fs->icon = 'money';
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

    // webhook url
    $inputfields->add([
      'type' => 'URL',
      'name' => 'webhook',
      'label' => 'Webhook Base Url',
      'icon' => 'link',
      'value' => $this->webhook,
      'notes' => "This is the base url that the payment provider triggers on status updates.
        Usually this is your production domain but during development it could be a temporary ngrok domain.",
    ]);

    return $inputfields;
  }
}
