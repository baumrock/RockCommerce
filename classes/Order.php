<?php

namespace RockCommerce;

use Mollie\Api\Resources\Payment;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use ProcessWire\RockMollie;
use ProcessWire\WireData;
use ProcessWire\WireException;
use RockMigrations\MagicPage;
use RockMoney\Money;

class Order extends Page
{
  use MagicPage;
  use RockCommercePage;

  const tpl = "rockcommerce_order";
  const prefix = "rockcommerce_order_";

  const field_price = self::prefix . "price";
  const field_payment = self::prefix . "payment";
  const field_paymentstatus = self::prefix . "paymentstatus";

  public function __construct()
  {
    try {
      $this->template = $this->rockmigrations()->getTemplate(self::tpl, true);
      $this->parent = $this->rockcommerce()->ordersPage();
    } catch (\Throwable $th) {
    }
  }

  /** magic */

  public function editFormContent(InputfieldWrapper $form): void
  {
    $rm = $this->rockmigrations();
    $form->prepend($rm->inputfield([
      'type' => 'markup',
      'label' => 'Order ID',
      'value' => $this->name,
      'columnWidth' => 33,
      'notes' => "API: \$order->name\n" .
        'Order created at ' . date("d.m.Y H:i:s", $this->created),
    ]));
    $form->add($rm->inputfield([
      'type' => 'markup',
      'name' => 'order-details',
      'label' => 'Order Details',
      'value' => $rm->renderTable($this->meta('details')),
      'collapsed' => Inputfield::collapsedYesAjax,
      'notes' => 'Additional information for the order.',
    ]));
    $form->add($rm->inputfield([
      'type' => 'markup',
      'name' => 'payment-details',
      'label' => 'Initial Payment Details',
      'value' => '<p>Payment details that have been received at the time of creating the order and payment.</p>'
        . $rm->renderTable($this->meta('payment'), [
          'tableclass' => 'uk-table-divider',
        ]),
      'collapsed' => Inputfield::collapsedYesAjax,
    ]));
  }

  public function onCreate(): void
  {
    $this->status = 1;
    $this->name = $this->wire->pages->names()->uniqueRandomPageName([
      'min' => 20,
      'max' => 40,
    ]);
  }

  /** backend */

  public function badge($str, $cls = '')
  {
    return "<span class='uk-alert $cls rc-badge'>$str</span>";
  }

  public function getPageListLabel()
  {
    $date = $this->badge(date("d.m.Y H:i:s", $this->created));
    $price = $this->badge($this->price());
    $status = $this->statusBadge();
    return $date
      . $status
      . $price
      . $this->title;
  }

  /**
   * Get payment from mollie
   */
  public function getPayment(): Payment|false
  {
    try {
      /** @var RockMollie $mollie */
      $mollie = $this->wire->modules->get('RockMollie');
      return $mollie->api()->payments->get($this->paymentId());
    } catch (\Throwable $th) {
      $this->rockcommerce()->log($th->getMessage());
    }
    return false;
  }

  public function paymentId(): string
  {
    return $this->getFormatted(self::field_payment);
  }

  public function createPayment(): Payment|false
  {
    if ($this->paymentId()) throw new WireException("A payment for this order has already been created.");

    // create mollie payment
    /** @var RockMollie $mollie */
    $mollie = $this->wire->modules->get('RockMollie');
    $rc = $this->rockcommerce();
    try {
      $price = $this->price(1);
      if (!$price instanceof Money) throw new WireException("Invalid Price");
      $payment = $mollie->api()->payments->create([
        "amount" => [
          "currency" => (string)$price->money->getCurrency(),
          "value" => $price->formatMollie(),
        ],
        "description" => (string)$this->title,
        "redirectUrl" => $rc->redirectUrl($this),
        "webhookUrl" => $rc->webhookUrl(),
        "metadata" => ["orderid" => $this->name],
      ]);
      $this->setPayment($payment);
      return $payment;
    } catch (\Throwable $th) {
      $this->wire->log($th->getMessage());
    }

    return false;
  }

  public function getDetails(): WireData
  {
    $details = new WireData();
    $info = $this->meta('details');
    if (is_array($info)) $details->setArray($info);
    return $details;
  }

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_price => [
          'type' => 'RockMoney',
          'label' => 'Price',
          'icon' => 'money',
          'collapsed' => Inputfield::collapsedNoLocked,
          'tags' => 'RockCommerce',
          'notes' => 'The final amount that you charge the client including all taxes.',
        ],
        self::field_payment => [
          'type' => 'text',
          'label' => 'Payment',
          'icon' => 'money',
          'textformatters' => [
            'TextformatterEntities',
          ],
          'collapsed' => Inputfield::collapsedNoLocked,
          'notes' => 'The id of the payment of the payment provider.',
        ],
        self::field_paymentstatus => [
          'type' => 'text',
          'label' => 'Payment-Status',
          'icon' => '',
          'textformatters' => [
            'TextformatterEntities',
          ],
          'collapsed' => Inputfield::collapsedNoLocked,
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'required' => false,
              'collapsed' => Inputfield::collapsedHidden,
              'columnWidth' => 40,
            ],
            self::field_price => [
              'columnWidth' => 20,
            ],
            self::field_payment => [
              'columnWidth' => 20,
            ],
            self::field_paymentstatus => [
              'columnWidth' => 20,
            ],
          ],
          'parentTemplates' => [
            Orders::tpl,
          ],
          'noLang' => 1, // single language
        ],
      ],
    ]);
  }

  /**
   * Save payment ID and payment info to this order
   */
  public function setPayment(Payment $payment): void
  {
    $this->setAndSave(self::field_payment, $payment->id);
    $this->meta('payment', json_encode($payment));
    $this->setPaymentStatus($payment->status);
  }

  public function setPaymentStatus(string $status): void
  {
    $this->setAndSave(self::field_paymentstatus, $status);
  }

  public function statusBadge(): string
  {
    $str = $this->paymentstatus(1);
    if (!$str) return '';
    switch ($str) {
      case 'paid':
        $cls = 'uk-alert-success';
        break;
      case 'open':
        $cls = 'uk-alert-warning';
        break;
      default:
        $cls = '';
        break;
    }
    return $this->badge($str, $cls);
  }
}
