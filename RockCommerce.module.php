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
      ],
      'installs' => [],
    ];
  }
  // /site/modules/RockCRM.module.php
  public function init()
  {
    $this->wire('rockcommerce', $this);

    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->watch($this);

    // load page classes
    require_once __DIR__ . "/RockCommercePage.php";
    $rm->pageClassLoader(__DIR__ . "/classes", "RockCommerce");
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

  public function rootPage(): RootPage
  {
    return $this->wire->pages->get([
      'parent' => 1,
      'template' => RootPage::tpl,
    ]);
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
    return $inputfields;
  }
}
