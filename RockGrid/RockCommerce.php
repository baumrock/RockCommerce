<?php

namespace RockGrid;

use RockCommerce\Invoice;
use RockCommerce\Order;

class RockCommerce extends Grid
{

  public function info()
  {
    return parent::info()->setArray([
      'title' => 'RockCommerce',
      'icon' => 'money',
      'hidden' => true,
      // 'help' => true, // you can remove this after setup
    ]);
  }

  public function getButtons()
  {
    // add or modify buttons at the top right of the grid
    return parent::getButtons();
  }

  public function getButtonsCenter()
  {
    // add or modify buttons at the top center of the grid
    return parent::getButtonsCenter();
  }

  public function getButtonsLeft()
  {
    // add or modify buttons at the top left of the grid
    return parent::getButtonsLeft();
  }

  public function getData()
  {
    // we use findRaw in this example because it is shipped with the core
    // my recommendation is to use RockFinder3 as it provides a lot more
    // and customized options for working with PW page data in grids
    $data = $this->findRaw([
      'template' => Order::tpl,
      'sort' => '-id',
    ], [
      'id',
      'title',
      Order::field_invoice . "." . Invoice::field_number => 'invoice',
      Order::field_net => 'net',
      Order::field_paymentstatus => 'status',
      'created' => 'date',
      'meta.details' => 'recipient',
    ]);

    // convert invoice number to plain number
    foreach ($data as $row) {
      $this->pagefieldSubvalue($row, Order::field_invoice, Invoice::field_number);

      // transform details meta data
      $meta = @$row->meta['details'] ?: [];
      $meta = (object)$meta;
      $name = array_filter([
        @$meta->company,
        @$meta->forename,
        @$meta->surname,
      ]);
      $row->recipient = implode(" ", $name);

      $row->country = strtoupper((string)@$meta->country);

      unset($row->meta);
      // bd($row);
    }

    return $data;
  }

  /**
   * Transform findRaw result into a readable result for RockGrid
   */
  public function pagefieldSubvalue($row, $field1, $field2)
  {
    $row->invoice = "";
    if (!property_exists($row, $field1)) return;
    $array = $row->{$field1};
    if (is_array($array)) {
      unset($row->$field1);
      foreach ($array as $value) {
        $row->invoice = @$value[$field2];
        return;
      }
    }
  }

  /**
   * This method defines who can view the grid
   * By default only superusers can view data. Adjust this to your needs.
   * @return bool
   */
  public function isViewable()
  {
    if ($this->wire->user->isSuperuser()) return true;
    // if($this->wire->user->hasRole('foo')) return true;
    return false;
  }
}
