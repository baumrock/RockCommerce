<?php

namespace RockCommerce;

use Mollie\Api\Resources\Payment;
use ProcessWire\FieldtypeFile;
use ProcessWire\FieldtypePage;
use ProcessWire\HookEvent;
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
use function ProcessWire\rockmigrations;
use function ProcessWire\wire;

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
  const field_cart = self::prefix . "cart";

  const field_net = self::prefix . "net";
  const field_vat = self::prefix . "vat";
  const field_total = self::prefix . "total";
  const field_paymentdate = self::prefix . "paymentdate";
  const field_customfields = self::prefix . "customfields";

  public function __construct()
  {
    try {
      $this->template = $this->rockmigrations()->getTemplate(self::tpl, true);
      $this->parent = rockcommerce()->ordersPage();
    } catch (\Throwable $th) {
    }
  }

  /** magic */

  public function editForm($form): void
  {
    // lock the name field
    if ($f = $form->get('_pw_page_name')) {
      $f->collapsed = Inputfield::collapsedLocked;
    }

    // show cart name and link to it
    if ($f = $form->get(self::field_cart)) {
      $id = $f->value;
      $url = wire()->pages->get(2)->url . "page/edit/?id=$id";
      $f->collapsed = Inputfield::collapsedYesLocked;
      $f->value = "<a href=$url>$id</a>";
      rockcommerce()->helpNote($f, "This links to the cart that was used for creating this order.
      IMPORTANT NOTE: Cart data is not persistant! Referenced products may be removed from the system or prices might get changed.");
    }
  }

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

    // ----- header section -----
    $f = $rm->inputfield([
      'type' => 'markup',
      'label' => 'Order ID/Name',
      'value' => "<div class='uk-text-truncate'>
        {$this->id}<br>
        {$this->name}
      </div>",
      'columnWidth' => 50,
      'notes' => 'Order created at ' . date("d.m.Y H:i:s", $this->created),
    ]);
    $form->prepend($f);

    // ----- totals section -----
    $rm->wrapFields($form, [
      self::field_net,
      self::field_vat,
      self::field_total,
    ], [
      'label' => 'Totals',
      'icon' => 'shopping-basket',
    ]);

    // ----- payment section -----
    // payment notes
    if ($f = $form->get(self::field_paymentdate)) {
      if ($f->value) $f->notes .= date('Y-m-d H:i:s', (int)$f->value) . "\n";
      $f->notes .= "You can change the payment date here. Note that the status will automatically be set to paid when the payment date is changed.";
    }
    $details = (string)$this->meta('payment');
    try {
      $details = json_decode($details, true);
    } catch (\Throwable $th) {
      $this->log($th->getMessage());
    }
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
          . rockmigrations()->dump($details),
        'collapsed' => Inputfield::collapsedYesAjax,
      ]
    ], [
      'label' => 'Payment',
      'icon' => 'money',
      'collapsed' => Inputfield::collapsedNo,
    ]);

    // ----- cart section -----

    // ----- invoice section -----
    $invoice = $this->invoice(1);
    $url = $invoice ? $invoice->editUrl() : '';
    $mail = $this->email(1);
    $f = $rm->inputfield([
      'type' => 'markup',
      'label' => 'Invoice',
      'value' => "<a href='$url'>$url</a>",
      'icon' => 'file-pdf-o',
      'entityEncodeText' => false,
      'notes' => "Usually the invoice is created when the webhook is triggered.
      Manual actions:
      <a href='./?id=$this&createinvoice=1'>Create invoice manually</a>
      <a href='./?id=$this&sendinvoice=1'>Send invoice to client $mail</a>",
      'collapsed' => $url ? Inputfield::collapsedNo : Inputfield::collapsedYes,
    ]);
    $form->insertAfter($f, $form->get(self::field_cart));
    rockcommerce()->apiNote($f, '$order->createInvoice()');

    // ----- custom fields section -----
    if ($f = $form->get(self::field_customfields)) {
      $fieldname = self::field_customfields;
      rockcommerce()->helpNote($f, "You can edit the field $fieldname and add custom fields as you need.");
    }

    // ----- order details section -----
    $f = $rm->inputfield([
      'type' => 'markup',
      'name' => 'order-details',
      'label' => 'Order Details',
      'icon' => 'table',
      'value' => $rm->dump($this->details()),
      'collapsed' => Inputfield::collapsedNo,
      'notes' => 'Additional information for the order.',
    ]);
    $form->add($f);
    rockcommerce()->apiNote($f, '$order->details() or $order->details("item")');

    if ($f = $form->get(self::field_paymentstatus)) {
      $url = rockcommerce()->url(
        rockcommerce()->publicOrderPageUrl . "?id=" . $this->name
      );
      $editlink = $rc->processUrl("edit-status/?id=$this");
      $f->entityEncodeText = false;
      $f->notes = "<a href=$url target=_blank>Public Status-Page</a>
        <a href=$editlink>Edit Status</a>";
      rockcommerce()->apiNote($f, '$order->paymentStatus()');
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

  /**
   * Show minimal info in the page list
   * An extensive list of orders is shown via RockGrid
   * @return string
   */
  public function getPageListLabel()
  {
    $date = date("d.m.Y H:i:s", $this->created);
    return "&nbsp;&nbsp;" . $date . "&nbsp;&nbsp;" . $this->net();
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

  public function createPayment(
    $redirectUrl,
  ): Payment|false {
    if ($this->paymentId()) throw new WireException("A payment for this order has already been created.");

    // create mollie payment
    /** @var RockMollie $mollie */
    $mollie = $this->wire->modules->get('RockMollie');
    $rc = rockcommerce();
    try {
      $total = $this->total(1);
      if (!$total instanceof Money) throw new WireException("Invalid Price");
      $data = [
        "amount" => [
          "currency" => (string)$total->money->getCurrency(),
          "value" => $total->formatMollie(),
        ],
        "description" => (string)$this->title,
        "redirectUrl" => $this->redirectUrl($redirectUrl),
        "webhookUrl" => $rc->webhookUrl(),
        "metadata" => ["orderid" => $this->name],
      ];
      $payment = $mollie->api()->payments->create($data);
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

    // cleanup
    $rm->deleteField('rockcommerce_order_attachments');

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
          'icon' => 'money',
        ],
        self::field_vat => [
          'type' => 'RockMoney',
          'label' => 'VAT',
          'icon' => '',
          'collapsed' => Inputfield::collapsedNoLocked,
          'tags' => 'RockCommerce',
          'icon' => 'percent',
        ],
        self::field_total => [
          'type' => 'RockMoney',
          'label' => 'Total',
          'icon' => '',
          'collapsed' => Inputfield::collapsedNoLocked,
          'tags' => 'RockCommerce',
          'notes' => '',
          'icon' => 'money',
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
          'icon' => 'money',
        ],
        self::field_paymentstatus => [
          'type' => 'text',
          'label' => 'Payment-Status',
          'icon' => '',
          'textformatters' => [
            'TextformatterEntities',
          ],
          'collapsed' => Inputfield::collapsedNoLocked,
          'icon' => 'money',
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
          'icon' => 'calendar',
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

        // field that allows users to add custom fields
        self::field_customfields => [
          'label' => 'Custom Fields',
          'type' => 'FieldtypeFieldsetPage',
          'icon' => 'cubes',
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
            self::field_customfields,

            // hidden
            self::field_invoice,
          ],
          'parentTemplates' => [
            Orders::tpl,
          ],
          'childTemplates' => [
            Item::tpl,
          ],
          'noParents' => 1,
          'noLang' => 1, // single language
          'icon' => 'shopping-bag',
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

  public function paymentStatus(): string
  {
    return $this->getFormatted(self::field_paymentstatus);
  }

  public function redirectUrl($url): string
  {
    return rockcommerce()->url($url . "?id={$this->name}", true);
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

  public function setPaymentData(
    string $id = '',
    string $status = '',
    int $date = 0,
    mixed $details = null,
  ): void {
    if ($id) $this->setAndSave(self::field_payment, $id);
    if ($status) $this->setAndSave(self::field_paymentstatus, $status);
    if ($date) $this->setAndSave(self::field_paymentdate, $date);
    if ($details) $this->meta('payment', json_encode($details, JSON_PRETTY_PRINT));
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

  public function total(): Money
  {
    return $this->getFormatted(self::field_total);
  }
}
