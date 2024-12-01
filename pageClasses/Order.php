<?php

namespace RockCommerce;

use Mollie\Api\Resources\Payment;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockCommerce;
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

  const status_paid = 'paid';
  const status_open = 'open';

  public function __construct()
  {
    try {
      $this->template = $this->rockmigrations()
        ->getTemplate(RockCommerce::template_order, true);
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
  }

  public function editFormContent(InputfieldWrapper $form): void
  {
    $rm = $this->rockmigrations();

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
      RockCommerce::field_net,
      RockCommerce::field_vat,
      RockCommerce::field_total,
    ], [
      'label' => 'Totals',
      'icon' => 'shopping-basket',
    ]);

    // ----- payment section -----
    // payment notes
    if ($f = $form->get(RockCommerce::field_paymentdate)) {
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
      RockCommerce::field_paymentid,
      RockCommerce::field_paymentstatus,
      RockCommerce::field_paymentdate,
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
    rockcommerce()->apiNote(
      $form->get(RockCommerce::field_paymentid),
      '$order->paymentID()',
    );
    rockcommerce()->apiNote(
      $form->get('payment-details'),
      '$order->paymentData()',
    );

    // ----- cart section -----

    // ----- custom fields section -----
    if ($f = $form->get(RockCommerce::field_orderfields)) {
      $fieldname = RockCommerce::field_orderfields;
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

    if ($f = $form->get(RockCommerce::field_paymentstatus)) {
      $url = rockcommerce()->url(
        rockcommerce()->publicOrderPageUrl . "?id=" . $this->name
      );
      // $editlink = $rc->processUrl("edit-status/?id=$this");
      // $f->entityEncodeText = false;
      // $f->notes = "<a href=$url target=_blank>Public Status-Page</a>
      //   <a href=$editlink>Edit Status</a>";
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
    if ($paid) $this->set(RockCommerce::field_paymentstatus, self::status_paid);
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
    return $this->getFormatted(RockCommerce::field_paymentid);
  }

  public function paymentStatus(): string
  {
    return $this->getFormatted(RockCommerce::field_paymentstatus);
  }

  public function paymentData(): array
  {
    try {
      return json_decode($this->meta('payment'), true);
    } catch (\Throwable $th) {
      return [];
    }
  }

  public function redirectUrl($url): string
  {
    return rockcommerce()->url($url . "?id={$this->name}", true);
  }

  /**
   * Hookable method that defines what happens if an order is marked as paid
   */
  public function ___setPaid(): void
  {
    if (!$this->paymentdate(2)) {
      $this->setAndSave(RockCommerce::field_paymentdate, time());
    }
  }

  public function setPaymentData(
    string $id = '',
    string $status = '',
    int $date = 0,
    mixed $details = null,
  ): void {
    if ($id) $this->setAndSave(RockCommerce::field_paymentid, $id);
    if ($status) $this->setAndSave(RockCommerce::field_paymentstatus, $status);
    if ($date) $this->setAndSave(RockCommerce::field_paymentdate, $date);
    if ($details) $this->meta('payment', json_encode($details, JSON_PRETTY_PRINT));
  }

  /**
   * Save payment ID and payment info to this order
   */
  public function setPayment(Payment $payment): void
  {
    $this->setAndSave(RockCommerce::field_paymentid, $payment->id);
    $this->meta('payment', json_encode($payment));
    $this->setPaymentStatus($payment->status);
  }

  public function setPaymentStatus(string $status): void
  {
    $this->setAndSave(RockCommerce::field_paymentstatus, $status);
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
    return $this->getFormatted(RockCommerce::field_total);
  }
}
