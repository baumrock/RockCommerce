<?php

namespace ProcessWire;

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
    $rm->pageClassLoader(__DIR__ . "/classes", "RockCommerce");
  }

  /** PUBLIC API */

  /** END PUBLIC API */

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
