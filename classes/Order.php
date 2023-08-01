<?php

namespace RockCommerce;

use Mollie\Api\Resources\Payment;
use ProcessWire\FieldtypePage;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use ProcessWire\RockMollie;
use ProcessWire\WireData;
use ProcessWire\WireException;
use ProcessWire\WireMail;
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
  const field_invoice = self::prefix . "invoice";

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
    $rc = $this->rockcommerce();
    $form->prepend($rm->inputfield([
      'type' => 'markup',
      'label' => 'Order ID',
      'value' => $this->name,
      'columnWidth' => 33,
      'notes' => "API: \$order->name\n" .
        'Order created at ' . date("d.m.Y H:i:s", $this->created),
    ]));

    $invoice = $this->invoice(1);
    $url = $invoice ? $invoice->editUrl() : '';
    $form->add($rm->inputfield([
      'type' => 'markup',
      'label' => 'Invoice',
      'value' => "<a href='$url'>$url</a>",
      'icon' => 'file-pdf-o',
      'notes' => 'API: $order->createInvoice()',
    ]));
    $form->add($rm->inputfield([
      'type' => 'markup',
      'name' => 'order-details',
      'label' => 'Order Details',
      'value' => $rm->renderTable($this->details()),
      'collapsed' => Inputfield::collapsedNo,
      'notes' => 'Additional information for the order.'
        . "\nAPI: \$order->details() or \$order->details('item')",
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
    if ($f = $form->get(self::field_paymentstatus)) {
      $url = $rc->url("order?id=" . $this->name);
      $f->notes = "[Status-Page]($url)";
    }
  }

  public function onCreate(): void
  {
    $this->status = 1;
    $this->name = $this->wire->pages->names()->uniqueRandomPageName([
      'min' => 20,
      'max' => 40,
    ]);
  }

  /** frontend */

  /**
   * Get date of order in given format
   */
  public function date(string $format = "d.m.Y"): string
  {
    return date($format, $this->created);
  }

  /**
   * Get order details
   *
   * Usage:
   * $order->details(); // get array of all order details
   * $order->details('forename'); // get single value
   */
  public function details($prop = null)
  {
    $details = $this->meta('details');
    if (!is_array($details)) $details = [];
    if ($prop) {
      if (array_key_exists($prop, $details)) return $details[$prop];
      return null;
    }
    return $details;
  }

  /** backend */

  public function getPageListLabel()
  {
    $date = $this->rockcommerce()->badge(date("d.m.Y H:i:s", $this->created));
    $price = $this->rockcommerce()->badge($this->price());
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

  /**
   * Create an invoice page for this order
   * If an invoice already exists we exit early.
   * This can happen if the webhook is triggered multiple times.
   */
  public function createInvoice($parentName = "rockcommerce"): Invoice
  {
    $invoice = $this->getInvoice();
    if ($invoice) return $invoice;

    $parent = $this->wire->pages->get([
      'parent' => $this->rockcommerce()->invoicesPage(),
      'name' => $parentName,
    ]);
    /** @var Invoice $invoice */
    $invoice = $this->wire->pages->newPage([
      'template' => Invoice::tpl,
      'parent' => $parent,
    ]);

    // save invoice so that we can add the reference to this order
    $invoice->save();

    // add reference to invoice
    $this->setAndSave(self::field_invoice, $invoice);

    // create the PDF now that the reference is in place
    // we flush the cache to make sure that all references are correct
    $this->wire->pages->uncacheAll();
    $invoice->createPdf();

    return $invoice;
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
        "redirectUrl" => $this->redirectUrl(),
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

  public function getInvoice(): Invoice|false
  {
    // use bracket syntax to make sure we get the single page object
    // see https://github.com/processwire/processwire-issues/issues/1795
    return $this->getFormatted(self::field_invoice . "[0]");
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
        self::field_invoice => [
          'type' => 'page',
          'label' => 'Invoice',
          'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
          'inputfield' => 'InputfieldPageListSelect',
          'findPagesSelector' => 'template=' . Invoice::tpl,
          'labelFieldName' => 'title',
          'collapsed' => Inputfield::collapsedHidden,
          'icon' => 'file-pdf-o',
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
            RockMigrations::field_email,
            self::field_invoice,
          ],
          'parentTemplates' => [
            Orders::tpl,
          ],
          'noParents' => 1,
          'noLang' => 1, // single language
        ],
      ],
    ]);
  }

  public function ___notifyAdmin()
  {
    $mailto = $this->rockcommerce()->adminmail;
    if (!$mailto) return;

    try {
      $host = $this->wire->config->httpHost;
      $url = $this->editUrl(true);
      $inv = $this->invoice()->editUrl(true);

      $body = "<h2>{$this->title}</h2>
        <p>Price: {$this->price()}</p>
        <p>E-Mail: {$this->email()}</p>
        <p>Order: <a href='$url'>$url</a></p>
        <p>Invoice: <a href='$inv'>$inv</a></p>
        ";

      $mail = new WireMail();
      $mail->from("RockCommerce@$host", "RockCommerce");
      $mail->to($mailto);
      $mail->subject("New Order at $host");
      $mail->bodyHTML($body);
      $mail->send();

      return $body;
    } catch (\Throwable $th) {
      $this->log($th->getMessage());
    }
  }

  public function paymentId(): string
  {
    return $this->getFormatted(self::field_payment);
  }

  public function redirectUrl(): string
  {
    return $this->rockcommerce()->url("order?id={$this->name}", true);
  }

  public function ___sendInvoice()
  {
    try {
      $html = $this->wire->files->fileGetContents(__DIR__ . "/../mails/rockcommerce.html");
      $html = str_replace('{{host}}', $this->wire->input->httpHostUrl(true), $html);

      $content = "<h2 style='text-align: center;'>Thank you for your order!</h2>
        <p>Please find your invoice attached to this email. <strong>Keep it in a save space as you find your License Key on it.</strong></p>
        <p>Keep rocking and see you in the ProcessWire stratosphere! 🚀<br>
        Bernhard</p>";
      $html = str_replace('{{body}}', $content, $html);

      $host = $this->wire->config->httpHost;

      $mail = new WireMail();
      $mail->from("RockCommerce@$host", "RockCommerce");
      $mail->to($this->email());
      $mail->subject("Thank you for your order!");
      $mail->bodyHTML($html);
      $mail->attachment($this->invoice()->pdf()->filename);
      $mail->send();

      return $html;
    } catch (\Throwable $th) {
      $this->log($th->getMessage());
    }
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
    return $this->rockcommerce()->badge($str, $cls);
  }
}
