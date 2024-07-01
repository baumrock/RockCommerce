<?php

namespace RockCommerce;

use Mollie\Api\Resources\Payment;
use ProcessWire\FieldtypeFile;
use ProcessWire\FieldtypePage;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldDatetime;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use ProcessWire\RockMollie;
use ProcessWire\WireData;
use ProcessWire\WireException;
use ProcessWire\WireMail;
use RockMigrations\MagicPage;
use RockMoney\Money;

use function ProcessWire\rockcommerce;

class Order extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_order";
  const prefix = "rockcommerce_order_";

  const status_paid = 'paid';
  const status_open = 'open';

  const field_price = self::prefix . "price";
  const field_payment = self::prefix . "payment";
  const field_paymentstatus = self::prefix . "paymentstatus";
  const field_invoice = self::prefix . "invoice";
  const field_attachments = self::prefix . "attachments";
  const field_cart = self::prefix . "cart";

  const field_net = self::prefix . "net";
  const field_vat = self::prefix . "vat";
  const field_total = self::prefix . "total";
  const field_paymentdate = self::prefix . "paymentdate";

  public function __construct()
  {
    try {
      $this->template = $this->rockmigrations()->getTemplate(self::tpl, true);
      $this->parent = rockcommerce()->ordersPage();
    } catch (\Throwable $th) {
    }
  }

  /** magic */

  public function editFormContent(InputfieldWrapper $form): void
  {
    // create invoice manually
    if ($this->wire->input->get('createinvoice')) {
      $this->createInvoice();
      $this->wire->session->redirect($this->editUrl());
    }
    // send invoice manually
    if ($this->wire->input->get('sendinvoice')) {
      $this->sendInvoice();
      $this->wire->session->redirect($this->editUrl());
    }

    $rm = $this->rockmigrations();
    $rc = rockcommerce();
    $form->prepend($rm->inputfield([
      'type' => 'markup',
      'label' => 'Order ID',
      'value' => "<div class='uk-text-truncate'>{$this->name}</div>",
      'columnWidth' => 50,
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
      'entityEncodeText' => false,
      'notes' => "API: \$order->createInvoice()
        Usually the invoice is created when the webhook is triggered.
        Manual actions:
        <a href='./?id=$this&createinvoice=1'>Create invoice manually</a>
        <a href='./?id=$this&sendinvoice=1'>Send invoice to client</a>
        ",
      'collapsed' => $url ? Inputfield::collapsedNo : Inputfield::collapsedYes,
    ]));
    $form->add($rm->inputfield([
      'type' => 'markup',
      'name' => 'order-details',
      'label' => 'Order Details',
      'icon' => 'table',
      'value' => $rm->renderTable($this->details()),
      'collapsed' => Inputfield::collapsedNo,
      'notes' => 'Additional information for the order.'
        . "\nAPI: \$order->details() or \$order->details('item')",
    ]));
    if ($f = $form->get(self::field_paymentstatus)) {
      $url = $rc->url("order?id=" . $this->name);
      $editlink = $rc->processUrl("edit-status/?id=$this");
      $f->entityEncodeText = false;
      $f->notes = "<a href=$url target=_blank>Public Status-Page</a>
        <a href=$editlink>Edit Status</a>";
    }

    // cost wrapper
    $rm->wrapFields($form, [
      self::field_net,
      self::field_vat,
      self::field_total,
    ], [
      'label' => 'Totals',
      'icon' => 'shopping-basket',
    ]);

    // payment wrapper
    $rm->wrapFields($form, [
      self::field_payment,
      self::field_paymentstatus,
      self::field_paymentdate,
      [
        'type' => 'markup',
        'name' => 'payment-details',
        'label' => 'Initial Payment Details',
        'value' => '<p>Payment details that have been received at the time of creating the order and payment.</p>'
          . $rm->renderTable($this->meta('payment'), [
            'tableclass' => 'uk-table-divider',
          ]),
        'collapsed' => Inputfield::collapsedYesAjax,
      ]
    ], [
      'label' => 'Payment',
      'icon' => 'money',
      'collapsed' => $this->paymentId() ? Inputfield::collapsedNo : Inputfield::collapsedYes,
    ]);

    // debug
    if ($this->wire->user->isSuperuser()) {
      $form->add([
        'type' => 'markup',
        'label' => 'Debug',
        'icon' => 'bug',
        'value' => $rm->renderTable([
          'redirectUrl' => $this->redirectUrl(),
        ]),
        'collapsed' => $this->wire->config->debug ? 0 : 1,
      ]);
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

  public function onSaveReady(): void
  {
    $paid = $this->paymentdate(2);
    if ($paid) $this->set(self::field_paymentstatus, self::status_paid);
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
   * Get or set order details
   *
   * Usage:
   * $order->details(); // get array of all order details
   * $order->details('forename'); // get single value
   *
   * Set detail foo to value bar:
   * $order->details('foo', 'bar');
   */
  public function details($prop = null, $val = null)
  {
    $details = $this->meta('details');
    if (!is_array($details)) $details = [];
    if ($prop) {
      if ($val !== null) {
        // set value
        $details[$prop] = $val;
        $this->meta('details', $details);
        return $val;
      }
      if (array_key_exists($prop, $details)) return $details[$prop];
      return null;
    }
    return $details;
  }

  /**
   * Get full name of person/company
   */
  public function name(): string
  {
    return trim($this->details('forename')
      . " " . $this->details('surname')
      . " " . $this->details('company'));
  }

  /** backend */

  public function getPageListLabel()
  {
    $rc = rockcommerce();
    $date = $rc->badge(date("d.m.Y H:i:s", $this->created));
    $status = $this->statusBadge();
    return $date
      . $status
      . $rc->badge($this->net())
      . $rc->badge($this->title)
      . $rc->badge($this->name());
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
      rockcommerce()->log($th->getMessage());
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
      'parent' => rockcommerce()->invoicesPage(),
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
    $rc = rockcommerce();
    try {
      $total = $this->total(1);
      if (!$total instanceof Money) throw new WireException("Invalid Price");
      $payment = $mollie->api()->payments->create([
        "amount" => [
          "currency" => (string)$total->money->getCurrency(),
          "value" => $total->formatMollie(),
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
        self::field_net => [
          'type' => 'RockMoney',
          'label' => 'Net',
          'icon' => '',
          'collapsed' => Inputfield::collapsedNoLocked,
          'tags' => 'RockCommerce',
        ],
        self::field_vat => [
          'type' => 'RockMoney',
          'label' => 'VAT',
          'icon' => '',
          'collapsed' => Inputfield::collapsedNoLocked,
          'tags' => 'RockCommerce',
        ],
        self::field_total => [
          'type' => 'RockMoney',
          'label' => 'Total',
          'icon' => '',
          'collapsed' => Inputfield::collapsedNoLocked,
          'tags' => 'RockCommerce',
          'notes' => '',
        ],
        self::field_payment => [
          'type' => 'text',
          'label' => 'Payment',
          'icon' => '',
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
        self::field_attachments => [
          'type' => 'file',
          'label' => 'Attachments',
          'maxFiles' => 0,
          'descriptionRows' => 0,
          'extensions' => 'pdf zip',
          'icon' => 'file-archive-o',
          'outputFormat' => FieldtypeFile::outputFormatArray,
          'collapsed' => Inputfield::collapsedBlank,
        ],
        self::field_paymentdate => [
          'type' => 'datetime',
          'label' => 'Payment-Date',
          'dateOutputFormat' => 'd.m.Y',
          'inputType' => 'text',
          'dateSelectFormat' => 'yMd',
          'datepicker' => InputfieldDatetime::datepickerFocus,
          'dateInputFormat' => 'j.n.y',
          'timeInputFormat' => '',
          'defaultToday' => 0,
        ],
        self::field_cart => [
          'type' => 'page',
          'label' => 'Cart',
          'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
          'inputfield' => 'InputfieldPageAutocomplete',
          'findPagesSelector' => 'id>0,template!=admin',
          'labelFieldName' => 'title',
          'collapsed' => Inputfield::collapsedBlankLocked,
          'icon' => 'shopping-cart',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields-' => [
            'title' => [
              'required' => false,
              'collapsed' => Inputfield::collapsedHidden,
              'columnWidth' => 50,
            ],
            RockMigrations::field_email => [
              'collapsed' => Inputfield::collapsedNoLocked,
              'columnWidth' => 50,
            ],

            self::field_net => [
              'columnWidth' => 33,
            ],
            self::field_vat => [
              'columnWidth' => 33,
            ],
            self::field_total => [
              'columnWidth' => 33,
            ],

            self::field_payment => [
              'columnWidth' => 33,
            ],
            self::field_paymentstatus => [
              'columnWidth' => 33,
            ],
            self::field_paymentdate => [
              'columnWidth' => 33,
            ],

            self::field_cart,

            self::field_invoice,
            self::field_attachments,
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
    $mailto = rockcommerce()->adminmail;
    if (!$mailto) return;

    try {
      $host = $this->wire->config->httpHost;
      $url = $this->editUrl(true);

      $body = "<h2>{$this->title}</h2>
        <p>Total: {$this->total(1)}</p>
        <p>E-Mail: {$this->email(1)}</p>
        <p>Order: <a href='$url'>$url</a></p>
        ";

      $mail = new WireMail();
      $rc = rockcommerce();
      $mail->from($rc->mailfrom(), $rc->mailfromName());
      $mail->to($mailto);
      $mail->subject("New Order at $host");
      $mail->bodyHTML($body);
      $mail->send();

      return $body;
    } catch (\Throwable $th) {
      $this->log("notifyAdmin: " . $th->getMessage());
    }
  }

  /**
   * Get formatted string of referenced payment id
   *
   * Returns empty string if no payment referenced.
   */
  public function paymentId(): string
  {
    return $this->getFormatted(self::field_payment);
  }

  public function redirectUrl(): string
  {
    return rockcommerce()->url("order?id={$this->name}", true);
  }

  /**
   * Send invoice and order attachments to client
   */
  public function ___sendInvoice()
  {
    try {
      $html = $this->wire->files->fileGetContents(__DIR__ . "/../mails/rockcommerce.html");
      $html = str_replace('{{host}}', $this->wire->input->httpHostUrl(true), $html);

      $licenseName = "";
      $license = $this->wire->pages->get("id=" . $this->details('license'));
      if ($license->id) $licenseName = " (" . $license->name() . ")";

      $content = "<p>Thank you for your order!</p>
        <h2>Your License Key: {$this->name}</h2>
        <h3>Download here: <a href=https://www.baumrock.com/rc/download>https://www.baumrock.com/rc/download</a></h3>

        <p style='margin-top:20px;'>Please find your invoice attached to this email.</p>
        <p>Keep rocking and see you in the ProcessWire stratosphere! 🚀<br>
        Bernhard</p>";
      $html = str_replace('{{body}}', $content, $html);

      $mail = new WireMail();
      $rc = rockcommerce();
      $mail->from($rc->mailfrom(), $rc->mailfromName());
      $mail->to($this->email(1));
      $mail->to('office@baumrock.com');
      $mail->subject("Thank you for your order!$licenseName");
      $mail->bodyHTML($html);

      // attach the invoice to this mail
      // we rename it to the plain number as the random hash is useless here
      $invoice = $this->getInvoice();
      $mail->attachment(
        $invoice->pdf(1)->filename,
        "invoice-" . $invoice->number() . ".pdf"
      );

      // attach terms of service
      $attachments = $this->getFormatted(self::field_attachments . "[]");
      foreach ($attachments as $file) $mail->attachment($file->filename);

      // send mail
      $mail->send();

      return $html;
    } catch (\Throwable $th) {
      $this->log("sendInvoice: " . $th->getMessage());
    }
  }

  /**
   * Hookable method that defines what happens if an order is marked as paid
   */
  public function ___setPaid(): void
  {
    if (!$this->paymentdate(2)) {
      $this->setAndSave(self::field_paymentdate, time());
    }
    $this->createInvoice();
    $this->sendInvoice();
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
    if ($status === 'paid') $this->setPaid();
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
    return rockcommerce()->badge($str, $cls);
  }

  public static function statuses(): array
  {
    return [
      self::status_open,
      self::status_paid,
    ];
  }
}
