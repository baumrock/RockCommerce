<?php

namespace ProcessWire;

use RockCommerce\Order;
use RockCommerce\Orders;
use RockCommerce\RootPage;

/**
 * @author Bernhard Baumrock, 24.07.2023
 * @license COMMERCIAL PLEASE DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 */
class RockCommerce extends WireData implements Module, ConfigurableModule
{
  const webhookurl = "/rockcommerce/webhook";

  public static function getModuleInfo()
  {
    return [
      'title' => 'RockCommerce',
      'version' => '0.0.1',
      'summary' => 'E-Commerce Module for ProcessWire',
      'autoload' => true,
      'singular' => true,
      'icon' => 'money',
      'requires' => [
        'RockMigrations>=3.27',
        'RockMoney>=1.0.2',
        'RockMollie>=1.0.0',
      ],
      'installs' => [],
    ];
  }

  // /site/modules/RockCRM.module.php
  public function init()
  {
    $this->loadConfig();
    $this->wire('rockcommerce', $this);

    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->watch($this);

    // load page classes
    require_once __DIR__ . "/RockCommercePage.php";
    $rm->pageClassLoader(__DIR__ . "/classes", "RockCommerce");

    // hooks
    $this->wire->addHookAfter(self::webhookurl, $this, "handleWebhook");
  }

  public function ready()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    if ($this->wire->page->template == 'admin') {
      $rm->addStyles(__DIR__ . "/less/backend.less");
    }
  }

  public function createOrder(string $subject, float $price, array $data = []): Order
  {
    $order = $this->wire->pages->newPage('template=' . Order::tpl);
    $order->set('title', $subject);
    $order->set(Order::field_price, $price);
    foreach ($data as $k => $v) {
      $order->set(Order::prefix . $k, $v);
    }
    $order->save();
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

  public function getOrder(string $orderid): Order|false
  {
    $order = $this->wire->pages->get([
      'template' => Order::tpl,
      'name' => $orderid,
    ]);
    if (!$order->id) return false;
    return $order;
  }

  public function handleWebhook(HookEvent $event)
  {
    $method = $this->wire->input->requestMethod();
    if ($method !== 'POST') return; // 404

    // we never need tracy on webhooks
    \Tracy\Debugger::$showBar = false;

    echo "RockCommerce Rocks!\n\n";

    // get payment and update order status
    $paymentid = $this->wire->input->post->id;
    if (!$paymentid) return true;
    try {
      $this->debug("Payment ID: $paymentid");
      $payment = $this->rockmollie()->api()->payments->get($paymentid);
      $order = $this->getOrder($payment->metadata->orderid);
      $this->debug("Order: " . $order);
      if ($order) $order->setPaymentStatus($payment->status);
    } catch (\Throwable $th) {
      $this->debug($th->getMessage(), true);
    }

    // dont render any html
    return true;
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

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');

    // create data pages
    $rootPage = $rm->createPage(
      template: RootPage::tpl,
      parent: 1,
      name: "rockcommerce",
      title: "RockCommerce",
      status: ["hidden"],
    );
    $rm->createPage(
      template: Orders::tpl,
      parent: $rootPage,
      name: 'orders',
      title: 'Orders',
    );
  }

  public function ordersPage(): Orders
  {
    return $this->wire->pages->get([
      'parent' => $this->rootPage(),
      'template' => Orders::tpl,
    ]);
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

  public function redirectUrl(Order $order): string
  {
    return $this->wire->pages->get(1)->httpUrl()
      . "rockcommerce/orders/?id={$order->name}";
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
