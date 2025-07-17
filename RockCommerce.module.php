<?php

namespace ProcessWire;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use RockCommerce\Cart;
use RockCommerce\Carts;
use RockCommerce\Discounts;
use RockCommerce\Frontend;
use RockCommerce\Order;
use RockCommerce\Orders;
use RockCommerce\PaymentProvider;
use RockCommerce\ProductPage;
use RockCommerce\ProductVariation;
use RockCommerce\RootPage;
use RockCommerce\Settings;
use RockCommerce\Taxrates;
use RockCommerce\TaxratesArray;
use RockCommerce\TaxSelect;
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
require_once __DIR__ . '/RockMigrationsConstants.php';
class RockCommerce extends WireData implements Module, ConfigurableModule
{
  use \RockCommerce\RockMigrationsConstants;

  const prefix = 'rockcommerce_';
  const maintenance = 'rockcommerce_maintenance';
  const permission_variations = 'rockcommerce-variations';

  const session_cart = 'rockcommerce_cart';
  const viewFolders = [
    '/site/templates/RockCommerce/frontend',
    '/site/modules/RockCommerce/frontend',
  ];

  // webhook url without trailing slash
  const webhookurl = '/rockcommerce/webhook';

  const pricetype_net = 1;
  const pricetype_gross = 0;
  const pricetype_runtime = 2; // not yet implemented

  const field_variation = 'rockcommerce_variation';
  const field_variations = 'rockcommerce_variations';
  const field_variationcomment = 'rockcommerce_variationcomment';
  const field_variationoptions = 'rockcommerce_variationoptions';
  const field_variationstr = 'rockcommerce_variationstr';
  const template_variations = 'rockcommerce_variations';
  const template_variation = 'rockcommerce_variation';

  private $checkout = false;

  private $discounts;

  /** @var array */
  public $findRawVariationOptions;

  public $frontend;

  private $jsConfig;
  private $jsFiles = [];

  public $noComplexPricing = false;

  public $priceType = self::pricetype_net;

  /** @var WireArray */
  public $productTemplates = false;

  public $showHelp = false;

  private $taxrates = false;

  public $taxselect;

  public $useProductVariations = false;

  public function init()
  {
    // add classloader
    // should be done by rockmigrations but does not work during installation
    wire()->classLoader->addNamespace('RockCommerce', __DIR__ . '/pageClasses');

    // attach hooks from rockcommerce.php
    $hookFile = wire()->config->paths->site . 'rockcommerce.php';
    if (is_file($hookFile)) require_once $hookFile;

    // initialize module and api
    $this->loadConfig();
    $this->wire('rockcommerce', $this);
    $this->loadProductTemplates();

    // load submodules
    $this->frontend = new Frontend();
    $this->taxselect = new TaxSelect();

    $this->initWhenInstalled();

    /** @var RockMigrations $rm */
    $rm = wire()->modules->get('RockMigrations');
    $rm->watch($this);

    // register AJAX endpoints
    rockfrontend()->addAjaxFolder('rockcommerce', __DIR__ . '/ajax');

    // payment status webhook
    // For the webhook we allow both slash and no slash urls!
    // This is because different payment providers might add/remove slashes.
    wire()->addHookAfter(self::webhookurl . "/?", $this, "hookHandleWebhook");

    // hooks
    wire()->addHookAfter('ProcessPageList::execute', $this, 'redirectPageList');
    wire()->addHookAfter(
      'Page::render',
      $this,
      'addMarkupHook',
      // priority 200 means RC assets are loaded after RF assets which have 100
      ['priority' => 200]
    );
    wire()->addHookAfter('Page::render', $this, 'tweakRockCommercePageList');

    // now that the module is ready we init the productpage
    // to attach hooks from the product trait
    (new ProductPage())->_init();
  }

  public function ready()
  {
    if (wire()->modules->isInstalled('RockLoaders')) rockloaders()->add('dots');
    $this->addBackendAssets();
  }

  private function addBackendAssets(): void
  {
    $p = wire()->page;
    if ($p->template != 'admin') return;
    if (wire()->config->ajax) return;
    if (wire()->config->external) return;
    $rm = rockmigrations();
    // currency for variation inputs
    $rm->addScripts(__DIR__ . '/../RockMoney/lib/currency.min.js');
  }

  /**
   * Add markup to frontend pages
   */
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

  protected function addMarkupHook(HookEvent $event): void
  {
    $newHtml = $this->addMarkup($event->object, $event->return);
    if (!$newHtml) return;
    $event->return = $newHtml;
  }

  public function addVariationsUrl(): string
  {
    return wire()->pages->get(2)->url
      . 'page/add/?parent_id='
      . $this->variationsPage();
  }

  public function apiNote(Inputfield $field, $note, $nl = true): void
  {
    $this->helpNote($field, $note, 'API', $nl);
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

  public function cartsPage(): Carts|Nullpage
  {
    return wire()->pages->get([
      'parent' => $this->rootPage(),
      'name' => 'carts',
    ]);
  }

  public function checkout(): MultiStepForm
  {
    if ($this->checkout) return $this->checkout;
    return $this->checkout = new MultiStepForm();
  }

  /**
   * Delete old webhook pages
   * This is triggered on every new webhook request.
   * The maximum setting can be adjusted on the Webhooks page.
   */
  public function cleanupOldWebhookPages(): void
  {
    $max = $this->webhooksPage()->max();
    $keep = wire()->pages->findIDs([
      'template' => RockCommerce::template_webhook,
      'sort' => '-id',
      'limit' => $max,
    ]);
    $selector = [
      "template" => RockCommerce::template_webhook,
      "id!=" => $keep,
    ];
    while ($p = wire()->pages->get($selector) and $p->id) {
      wire()->pages->delete($p);
      wire()->pages->uncacheAll();
    }
  }

  public function ___createOrder(
    string $subject,
    Money|float $net,
    Money|float $vat,
    Money|float $total,
    string $email,
    array $pageProperties = [],
    array $details = [],
  ): Order {
    $money = rockmoney();
    $order = wire()->pages->newPage('template=' . self::template_order);
    $order->set('title', $subject);
    $order->set(self::field_net, $money->parse($net));
    $order->set(self::field_vat, $money->parse($vat));
    $order->set(self::field_total, $money->parse($total));
    $order->set(RockMigrations::field_email, $email);
    foreach ($pageProperties as $k => $v) $order->set($k, $v);
    $order->save();
    $order->meta('details', $details);
    return $order;
  }

  public function dashboardPage(): Page
  {
    return wire()->pages->get([
      'template' => 'admin',
      'name' => ProcessRockCommerce::dashboardPageName,
    ]);
  }

  /**
   * Debug method for webhook debugging
   *
   * This will only echo something if "debug" is set to true in the
   * $config->rockcommerce array.
   */
  private function debug($str, $log = false): void
  {
    if ($log) wire()->log($str);
    if (!$this->debug) return;
    echo "$str\n\n";
  }

  public function __debugInfo()
  {
    return [
      'productTemplates' => $this->productTemplates,
    ];
  }

  /**
   * This method will only be executed if debug mode is enabled
   * and $config->rockdevtools = true;
   */
  private function dev(): void
  {
    $config = wire()->config;
    if ($config->ajax) return;
    if ($config->external) return;
    if (!$config->debug) return;
    if (!$config->rockdevtools) return;

    // compile all assets to one minified js file
    if (!wire()->modules->isInstalled('RockDevTools')) return;
    $dir = wire()->config->urls($this);

    $tools = rockdevtools();
    $tools
      ->assets()
      ->js()
      // load healthchecks first
      // this might help for debugging if any later file throws an error
      ->add($dir . 'src/healthchecks.js')
      // load base component before all others
      ->add($dir . 'alpine/RockCommerce.js')
      // then load all other alpine components
      ->add($dir . 'alpine/*.js')
      // save it to dst folder
      ->save($dir . 'dst/RockCommerce.min.js');
  }

  public function discounts(): Discounts
  {
    if ($this->discounts) return $this->discounts;
    return $this->discounts = new Discounts();
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
        self::field_variationoptions . '.title',
      ],
      ['nulls' => true]
    );

    $result = [];
    foreach ($arr as $varid => $fields) {
      foreach ($fields[self::field_variationoptions] as $optid => $data) {
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
    if (!$id) $id = wire()->session->get(self::session_cart);
    $cart = wire()->pages->get($id);
    if ($cart instanceof NullPage && $create) $cart = $this->newCart();
    if (!$cart instanceof Cart) return false;
    if ($session) $this->setCart($cart);
    return $cart;
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
  //     'debug' => wire()->config->debug,
  //     'currency' => rockmoney()->currencyStr ?: 'EUR',
  //     'locale' => $this->frontendLocale ?: 'de-DE',
  //     'priceType' => $this->priceType() ?: 'net',
  //   ];
  //   if ($json) return json_encode($config);
  //   return $config;
  // }

  /**
   * Get order by id or name
   * returns false if order is not found
   */
  public function getOrder(string $orderid = null): Order|false
  {
    if ($orderid === null) $orderid = wire()->input->get('id', 'string');
    $order = wire()->pages->get([
      'template' => self::template_order,
      'name' => $orderid,
    ]);
    if (!$order->id) return false;
    return $order;
  }

  /**
   * Get order by payment ID
   * returns false if order is not found
   */
  public function getOrderByPaymentID(string $paymentid): Order|false
  {
    $paymentid = wire()->sanitizer->selectorValue($paymentid);
    if (!$paymentid) return false;
    $order = wire()->pages->get([
      'template' => self::template_order,
      self::field_paymentid => $paymentid,
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
    if (!$page instanceof Page) $page = wire()->pages->get((string)$page);
    if (!$page->isProduct) return false;
    return $page;
  }

  /**
   * Get all product templates
   */
  private function getProductTemplates(): WireArray
  {
    $templates = new WireArray();
    foreach (wire()->templates as $tpl) {
      $p = wire()->pages->newPage("template=$tpl");
      if (!$p->isProduct) continue;
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

  /**
   * Hookable method to attach actions after webhook is received
   * @return void
   */
  public function ___handleWebhook(
    Webhook $log,
    $input,
    $info, // request info (to be available in hooks)
  ) {
    // get payment ID from input
    $paymentID = wire()->sanitizer->filename($input->id);

    // find order
    $order = rockcommerce()->getOrderByPaymentID($paymentID);
    if (!$order) {
      $log->echo('No order found for payment ' . $paymentID);
      return;
    }

    // all good, continue
    $log->echo("Found order $order for payment $paymentID");

    // get provider
    $provider = rockcommerce()->paymentProvider();
    if (!$provider) {
      $log->echo("No payment provider configured");
      return;
    }

    // get payment from provider
    $payment = $provider->getPayment($paymentID);
    if ($payment) {
      $log->echo("Updating payment status to '{$payment->status}'");
      $order->setPaymentStatus($payment->status);
    } else {
      $log->echo("Payment not found or status not available for payment ID: " . $paymentID);
    }
  }

  public function hookHandleWebhook(HookEvent $event)
  {
    // NOTE: We allow all requests (GET/POST)
    // This is to get maximum compatibility with all possible payment providers

    // disable tracy markup on webhooks (if it is installed)
    try {
      \Tracy\Debugger::$showBar = false;
    } catch (\Throwable $th) {
    }

    // log this request to a processwire page
    $webhook = wire()->pages->new([
      "template" => RockCommerce::template_webhook,
      "parent" => $this->webhooksPage(),
    ]);

    // cleanup old webhook pages
    $this->cleanupOldWebhookPages();

    // save request input
    $input = wire()->input->post;
    $webhook->meta(Webhook::meta_input, $input->getArray());

    // save request info
    $info = $webhook->requestInfo();
    $webhook->meta(Webhook::meta_info, $info);

    // trigger hookable method
    $this->handleWebhook($webhook, $input, $info);

    $webhook->echo("done");

    return true;
  }

  public function helpNote(Inputfield $field, $note, $prefix = '', $nl = true): void
  {
    if (!$this->showHelp) return;
    if (!wire()->user->isSuperuser()) return;
    $notes = array_filter([
      $field->notes,
      $prefix ? "$prefix: $note" : $note,
    ]);
    $field->notes = $nl
      ? implode("\n", $notes)
      : implode(" ", $notes);
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
  //   if (wire()->config->ajax) return;
  //   if (!$page->viewable()) return;
  //   $event->return = str_replace(
  //     "</body>",
  //     "<script>document.addEventListener('DOMContentLoaded', function() {
  //       RockCommerceSite.init({$this->getJsConfig(true)});
  //     });</script></body>",
  //     $event->return
  //   );
  // }

  /**
   * Things to do only when the module is installed
   *
   * These operations throw an error if the module is not yet completely
   * installed, eg the RootPage does not exist yet etc;
   */
  public function initWhenInstalled(): void
  {
    if (!$this->isInstalled()) return;

    // add field variationoptions to template rockcommerce_variation
    // see template/variation.php for more details
    $rm = rockmigrations();
    $rm->once(
      '2024-11-29: Add variationoptions field to template rockcommerce_variation',
      function (RockMigrations $rm) {
        $rm->addFieldToTemplate(
          RockCommerce::field_variationoptions,
          RockCommerce::template_variation
        );
      }
    );

    $this->installProductVariations();

    // js config for the frontend
    $this->jsConfig();

    // things to do while developing
    $this->dev();

    // trigger maintenance from time to time
    $expire = 60 * 60 * 1; // every hour
    wire()->cache->get(self::maintenance, $expire, function () {
      $this->maintenance();
    });
  }

  private function installProductVariations(): void
  {
    if (!$this->useProductVariations) return;

    // install RockGrid if it is not installed
    $rockgrid = wire()->modules->get('RockGrid');
    if (!$rockgrid) {
      // this prevents showing the warning when box is being unchecked
      if (!wire()->input->post('submit_save_module')) {
        $this->warning('Please install RockGrid to use product variations.');
      }
      return;
    }
    $this->migrate();
  }

  public function isInstalled(): bool
  {
    try {
      return $this->rootPage()->id > 0;
    } catch (\Throwable $th) {
      return false;
    }
  }

  /**
   * Set JS config value
   */
  public function js(string $prop, mixed $val): void
  {
    $this->jsConfig->set($prop, $val);
  }

  /**
   * js config
   * will be available in fronend via RockCommerce.config
   * as soon as the RockCommerce:ready event has been fired
   */
  private function jsConfig(): void
  {
    $this->jsConfig = new WireData();
    $this->js('root', wire()->config->urls->root);
    $this->js('taxrates', $this->taxrates()->getJsonArray());
    $this->js('defaultTaxID', $this->taxratesPage()->getDefaultTaxID());
  }

  /**
   * Load config settings from $config->rockcommerce
   * This makes it possible to have settings in the module's config, but you can
   * override them via config-local.php if you need.
   */
  private function loadConfig(): void
  {
    $overrides = wire()->config->rockcommerce;
    if (!is_array($overrides)) return;
    // note: $this->set($k, $v) does not work here
    foreach ($overrides as $k => $v) $this->$k = $v;
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
  private function loadProductTemplates($reset = false): void
  {
    $cache = "rc-product-templates";

    // reset cache on modules refresh
    wire()->addHookAfter("Modules::refresh", function () use ($cache) {
      wire()->cache->delete($cache);
    });

    // reset cache?
    if ($reset) wire()->cache->delete($cache);

    // populate templates from cache
    $templates = wire()->cache->get($cache, function () {
      return (string)$this->getProductTemplates();
    });
    $templates = (new WireArray())->setArray(explode("|", $templates));
    $this->productTemplates = $templates;
  }

  public function mailfrom(): string
  {
    $host = wire()->config->httpHost;
    return $this->mailfrom ?: "RockCommerce@$host";
  }

  public function mailfromName(): string
  {
    return $this->mailfromName ?: "RockCommerce";
  }

  public function maintenance(): void
  {
    // remove unused variations
    $oldVariations = wire()->pages->find([
      'parent' => $this->variationsPage(),
      'title' => '',
      'created<' => time() - 60 * 60, // older than 1h
    ]);
    foreach ($oldVariations as $p) $p->delete();
  }

  public function ___markupBody(): string
  {
    $conf = json_encode($this->jsConfig->getArray());
    $conf = wire()->sanitizer->entities($conf);
    $out = '<div id="rockcommerce">';

    // load cart component
    // this hidden element makes sure that we load the component even
    // if there is no cart element in the DOM. If there is one, the init()
    // method will take care of loading the cart only once.
    // data-ajax=false tells RockCommerce not to load items via ajax
    $this->loadJS('RcCart');
    $out .= "<div x-data=RcCart data-noajax hidden></div>";

    // load other alpine components
    $out .= "<div x-data=RcTaxrates hidden></div>";

    // rockcommerce has to be the last alpine element
    // this is to ensure that all other components are ready at this point
    $out .= "<div x-data=RockCommerce data-config='$conf' hidden></div>";

    $out .= "</div>";

    return $out;
  }

  public function migrate()
  {
    $rm = rockmigrations();

    // add permissions
    $rm->createPermission(
      self::permission_variations,
      'User can manage product variations'
    );

    $rootPage = $rm->createPage(
      template: self::template_rootpage,
      parent: 2,
      name: "rockcommerce",
      title: "RockCommerce",
      status: ["hidden"],
    );
    $rm->createPage(
      template: self::template_orders,
      parent: $rootPage,
      name: "orders",
      title: "Orders",
    );
    $rm->createPage(
      template: self::template_webhooks,
      parent: $rootPage,
      name: "webhooks",
      title: "Webhooks",
      status: ["hidden"],
    );
    $rm->createPage(
      template: self::template_carts,
      parent: $rootPage,
      title: 'Carts',
      name: 'carts',
      status: [],
    );
    $rm->createPage(
      template: self::template_taxrates,
      parent: $rootPage,
      title: 'Taxrates',
      name: 'taxrates',
    );
    $rm->createPage(
      template: self::template_settings,
      parent: $rootPage,
      title: 'Settings',
      name: 'settings',
    );
    $rm->createPage(
      template: self::template_discounts,
      parent: $rootPage,
      title: 'Discounts',
      name: 'discounts',
    );

    $this->migrateVariations();
  }

  public function migrateVariations(): void
  {
    if (!$this->useProductVariations) return;
    rockmigrations()->runConfigMigrations(
      __DIR__ . '/RockMigrationsVariations',
      false
    );
    rockmigrations()->createPage(
      template: self::template_variations,
      parent: $this->rootPage(),
      title: 'Variations',
      name: 'variations',
    );
  }

  /**
   * Create a new cart and save it to the database
   */
  public function newCart(): Cart
  {
    return wire()->pages->new([
      'template' => self::template_cart,
    ]);
  }

  public function ordersPage(): Orders
  {
    return wire()->pages->get([
      'parent' => $this->rootPage(),
      'template' => self::template_orders,
    ]);
  }

  /**
   * Get the payment provider that is configured in the module's config
   */
  public function paymentProvider(): PaymentProvider|false
  {
    // if not paymentprovider is configured, return the first available provider
    if (!$this->paymentprovider) {
      $providers = $this->paymentProviders();
      foreach ($providers as $provider) return $provider;
      return false;
    }

    // get the payment provider from the module's config
    $name = $this->paymentprovider;
    $provider = wire()->modules->get($name);
    if (!$provider instanceof PaymentProvider) return false;
    return $provider;
  }

  /**
   * Get an array of all available (installed) payment providers
   */
  public function paymentProviders(): array
  {
    // get all modules that implement PaymentProvider
    $providers = [];
    foreach (wire()->modules as $module) {
      if (!$module instanceof PaymentProvider) continue;
      $providers[] = $module;
    }
    return $providers;
  }

  public function priceType(): string
  {
    if ($this->priceType == self::pricetype_gross) return "gross";
    return "net";
  }

  public function processPage(): Page
  {
    return wire()->pages->get([
      'parent' => 2,
      'name' => 'rockcommerce',
    ]);
  }

  public function processUrl(string $url): string
  {
    return $this->processPage()->url . $url;
  }

  /**
   * This hook is used to make sure that when a breadcrumb item is clicked
   * we don't land on the main pagelist but on the RockCommerce branch
   */
  protected function redirectPageList(): void
  {
    if (wire()->config->ajax) return;
    if (wire()->config->external) return;
    if (wire()->input->get('id')) return;
    $open = wire()->input->get('open', 'int');
    if (!$open) return;
    $p = wire()->pages->get($open);
    $isRockCommerce = $p
      ->closest('template=' . self::template_rootpage)
      ->id > 0;
    if (!$isRockCommerce) return;

    // redirect to rockcommerce settings page
    $url = wire()->pages->get(2)->url;
    $p = rockcommerce()->rootPage();
    wire()->session->redirect($url . "page/?id=$p&open=$open");
  }

  public function ___renderAddress($data): string
  {
    $parts = [];
    if ($data->company) $parts[] = "$data->company";
    $parts[] = "{$data->firstname} {$data->lastname} {$data->fullname}";
    $parts[] = "$data->street";
    $parts[] = "$data->zip $data->city";
    $parts[] = "$data->countryName";

    $addr = array_filter(array_map('trim', $parts));
    return implode('<br>', $addr);
  }

  public function rockfrontend(): RockFrontend
  {
    return wire()->modules->get('RockFrontend');
  }

  public function rockmollie(): RockMollie
  {
    return wire()->modules->get('RockMollie');
  }

  public function rootPage(): RootPage|NullPage
  {
    return wire()->pages->get([
      // 'parent' => 2,
      'template' => self::template_rootpage,
    ]);
  }

  public function sanitizeVariation(string|null $variation): string
  {
    if (is_null($variation)) return '';

    $str = '';
    // "6780:6730,6731-6789:6784"
    // Split the variation string into individual key-value pairs
    $pairs = explode('-', $variation);
    foreach ($pairs as $pair) {
      if (!strpos($pair, ':')) continue;

      // Split each pair into key and values
      list($key, $values) = explode(':', $pair);

      // Sanitize key
      $key = (int)$key;

      // get variation from key
      $variation = wire()->pages->get($key);
      if (!$variation instanceof ProductVariation) continue;
      $validOptionIds = [];
      foreach ($variation->options() as $opt) {
        $validOptionIds[] = $opt->option->id;
      }

      $sanitizedValues = [];

      // Split values by comma and sanitize each
      $valueArray = explode(',', $values);
      foreach ($valueArray as $value) {
        $value = (int)$value;
        if (!in_array($value, $validOptionIds)) continue;
        $sanitizedValues[] = $value;
      }

      // Only add to the sanitized string if key is valid and there are sanitized values
      if (!empty($sanitizedValues)) {
        $str .= ($str ? '-' : '') . $key . ':' . implode(',', $sanitizedValues);
      }
    }

    return $str;
  }

  public function setCart(Cart $cart): void
  {
    wire()->session->set(self::session_cart, $cart->name);
  }

  public function settings(): Settings
  {
    return wire()->pages->get([
      'template' => self::template_settings,
      'parent' => $this->rootPage(),
    ]);
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
    return (float)wire()->config->rockcommerceTaxrate;
  }

  public function taxrates(): TaxratesArray
  {
    $rates = wire()->pages->find([
      'parent' => $this->rootPage()->path . 'taxrates',
      'include' => 'all',
    ]);
    return (new TaxratesArray())->import($rates);
  }

  public function taxratesPage(): Taxrates
  {
    return wire()->pages->get([
      'template' => RockCommerce::template_taxrates,
      'parent' => $this->rootPage(),
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

  protected function tweakRockCommercePageList(HookEvent $event): void
  {
    if (wire()->config->ajax) return;
    $id = wire()->input->get('id', 'int');

    // to prevent endless loops if id is not set for whatever reason
    if (!$id) return;

    // only execute hook if ?id=XXX matches RC RootPage ID
    if ($id !== $this->rootPage()->id) return;

    // prevent double rendering of page list if ?id=XX&open=XX
    if ($id === wire()->input->get('open', 'int')) {
      $url = wire()->pages->get(2)->url . "page/";
      wire()->session->redirect("$url?id=$id");
      return;
    }

    $event->return = str_replace(
      "<div id='PageListContainer'",
      "<style>
        #PageListContainer .PageListItem { --rock-muted-background: none; }
      </style>
      <div id='PageListContainer'",
      $event->return
    );
  }

  public function ___uninstall(): void
  {
    rockmigrations()->deleteFields('tags=RockCommerce');
    rockmigrations()->deleteTemplates('tags=RockCommerce');
    rockmigrations()->deletePermission(ProcessRockCommerce::permission);
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

  /**
   * Convert variation string to array
   *
   * Example return:
   * [
   *   6780 => [6730, 6731],
   *   6799 => [6793, 6792],
   * ]
   */
  public function variationArray(string $str): array
  {
    $str = $this->sanitizeVariation($str);
    if (!$str) return [];

    $result = [];
    $variations = explode('-', $str);
    foreach ($variations as $variation) {
      list($key, $values) = explode(':', $variation);
      $result[$key] = array_map('intval', explode(',', $values));
    }
    return $result;
  }

  public function variationsPage(): Variations|NullPage
  {
    return wire()->pages->get([
      'parent' => $this->rootPage(),
      'name' => 'variations',
    ]);
  }

  /**
   * Turn a variation string like 6789:6784,6799-6793:6792
   * into a variation table
   *
   * Note: We do not add <table> to make it easy to style the table on
   * the frontend.
   */
  public function variationTableData(string $str): array
  {
    $variationArray = $this->variationArray($str);
    if (empty($variationArray)) return [];

    $result = [];
    foreach ($variationArray as $variationId => $optionIds) {
      $variationPage = wire()->pages->get($variationId);
      $options = [];
      foreach ($optionIds as $optionId) {
        $option = wire()->pages->get($optionId);
        $options[] = $option->title;
      }
      $result[] = [
        'variation' => $variationPage->title(),
        'options' => $options,
      ];
    }
    return $result;
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
    $rm = wire()->modules->get('RockMigrations');
    $root = wire()->config->paths->root;
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
      if ($absolute) return rtrim(wire()->config->paths->root, "/") . $dir;
      return $dir;
    }, self::viewFolders);
    if ($reverse) return array_reverse($folders);
    return $folders;
  }

  public function webhooksPage(): Webhooks
  {
    return wire()->pages->get([
      'template' => RockCommerce::template_webhooks,
      'parent' => $this->rootPage(),
    ]);
  }

  public function webhookUrl(): string
  {
    return $this->host() . self::webhookurl;
  }

  public function ___install(): void
  {
    rockmigrations()->log("Installing RockCommerce");
    $this->init();
    rockmigrations()->runConfigMigrations(__DIR__ . '/RockMigrations/');
    $this->migrate();
    rockmigrations()->log("Installing RockCommerce DONE");
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

    if (!wire()->config->usePageClasses) {
      $inputfields->add([
        'type' => 'markup',
        'label' => 'WARNING',
        'value' => "<div class='uk-alert uk-alert-warning uk-margin-remove'>
          This module requires the use of page classes. Please set <code>\$config->usePageClasses = true;</code> in your config.php file.
        </div>",
      ]);
    }

    if (!wire()->modules->isInstalled('TracyDebugger')) {
      $inputfields->add([
        'type' => 'markup',
        'label' => 'TracyDebugger is not installed',
        'value' => "<div class='uk-alert uk-alert-info uk-margin-remove'>
          We recommend to install TracyDebugger to make several dumps of RockCommerce more pretty!
        </div>",
        'icon' => 'exclamation-triangle',
      ]);
    }

    // $inputfields->add([
    //   'type' => 'markup',
    //   'label' => 'Config Settings Override',
    //   'value' => "You can override any setting in your config.php file:<br>
    //     <pre class=uk-margin-small-top><code>\$config->rockcommerce['setting'] = 'newvalue';</code></pre>
    //     <p>Active Overrides from \$config:</p>"
    //     . rockmigrations()->dump(wire()->config->rockcommerce),
    //   'icon' => 'life-ring',
    //   'notes' => 'Use Shilft+Click on any field label to copy fieldname to clipboard.',
    // ]);

    $inputfields->add([
      'type' => 'checkbox',
      'name' => 'showHelp',
      'checked' => $this->showHelp ? 'checked' : '',
      'label' => 'Show help and API notes to superusers',
    ]);
    $inputfields->children->last()->notes = '$config->rockcommerce["showHelp"] = true/false;';

    // payment providers
    $f = new InputfieldRadios();
    $f->label = 'Payment Provider';
    $f->icon = 'money';
    $f->name = 'paymentprovider';
    $f->options = [];
    foreach ($this->paymentProviders() as $provider) {
      if (!$provider instanceof Module) continue;
      $name = $provider->className();
      $f->addOption($name, $name, ['selected' => $name === $this->paymentprovider]);
    }
    $inputfields->add($f);

    // Pricing
    $fs = new InputfieldFieldset();
    $fs->label = 'Pricing';
    $fs->icon = 'money';
    $fs->collapsed = Inputfield::collapsedNo;
    $inputfields->add($fs);

    // use product variations?
    if (!wire()->modules->isInstalled('RockGrid')) {
      $fs->add([
        'type' => 'markup',
        'value' => 'Product variations require RockGrid>=1.3.0. Please install RockGrid first.',
      ]);
    } else {
      // check if RockGrid is at least 1.3.0
      $version = wire()->modules->get('RockGrid')->config->version;
      if (version_compare($version, '1.3.0') < 0) {
        $fs->add([
          'type' => 'markup',
          'value' => 'Product variations require RockGrid>=1.3.0. Please update RockGrid to the latest version.',
        ]);
      } else {
        $fs->add([
          'type' => 'checkbox',
          'name' => 'useProductVariations',
          'label' => 'Enable Product Variations',
          'checked' => $this->useProductVariations ? 'checked' : '',
          'columnWidth' => 100,
          'notes' => 'When checked, RockGrid and all related fields will be installed.
          Once installed, unchecking the box will not remove the module or fields, it will just not use them.',
        ]);
      }
    }

    $link = wire()->pages->get(2)->url . "module/edit?name=RockMoney";
    $fs->add([
      'type' => 'markup',
      'value' => nl2br("PHP pricing settings are defined in the <a href=$link>RockMoney module</a>.
        Frontend pricing can be customised via hooks."),
    ]);

    // // Backend
    // $fs = new InputfieldFieldset();
    // $fs->label = 'Backend';
    // $fs->icon = 'cogs';
    // $fs->collapsed = Inputfield::collapsedYes;
    // $inputfields->add($fs);

    return $inputfields;
  }
}
