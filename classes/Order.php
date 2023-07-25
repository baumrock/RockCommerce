<?php

namespace RockCommerce;

use Mollie\Api\Resources\Payment;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class Order extends Page
{
  use MagicPage;
  use RockCommercePage;

  const tpl = "rockcommerce_order";
  const prefix = "rockcommerce_order_";

  const field_price = self::prefix . "price";
  const field_payment = self::prefix . "payment";

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
      'label' => 'Payment Details',
      'value' => $rm->renderTable($this->meta('payment'), [
        'tableclass' => 'uk-table-divider',
      ]),
      'collapsed' => Inputfield::collapsedYesAjax,
      'notes' => 'Payment details that have been received at the time of creating the order and payment.',
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

  public function getPageListLabel()
  {
    $rm = $this->rockmigrations();
    $date = $rm->pageListBadge(date("d.m.Y H:i:s", $this->created));
    $price = $rm->pageListBadge($this->price(), ['muted' => false]);
    return $date . $price . $this->title;
  }

  /** backend */

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
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'required' => false,
              'collapsed' => Inputfield::collapsedHidden,
              'columnWidth' => 33,
            ],
            self::field_price => [
              'columnWidth' => 33,
            ],
            self::field_payment => [
              'columnWidth' => 33,
            ],
          ],
          'parentTemplates' => [
            Orders::tpl,
          ],
          'noLang' => 1, // single language
        ],
      ],
    ]);
    $rm->createViewFile($this);
  }

  public function setPayment(Payment $payment): void
  {
    $this->setAndSave(self::field_payment, $payment->id);
    $this->meta('payment', json_encode($payment));
  }
}
