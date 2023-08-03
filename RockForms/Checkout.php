<?php

namespace RockForms;

use ProcessWire\ModulePage;
use ProcessWire\RockCommerce;
use ProcessWire\RockMigrations;
use ProcessWire\WireException;
use RockMoney\Money;
use RockPageBuilder\Block;

class Checkout extends RockForm
{

  public function buildForm()
  {
    // check get parameters for validity
    if ($this->checkInput() === false) return;

    $module = $this->wire->pages->get($this->wire->input->get('module', 'int'));
    if (!$module instanceof ModulePage) throw new WireException("Invalid value for module");

    // variables
    /** @var Site $site */
    $site = $this->wire->modules->get('Site');
    $price = $this->license()->actualPrice();

    // setup form
    $form = $this;
    $form->setRockFormsRenderer('UIkit');
    $form->getElementPrototype()
      ->setAttribute("onsubmit", "showLoading(event)");

    $form->addInteger("amount")
      ->setDefaultValue(1)
      ->setRequired()
      ->setHtmlAttribute("min", 1)
      ->setHtmlAttribute("onkeyup", "updatePrice()")
      ->setHtmlAttribute("onchange", "updatePrice()");

    $form->addRadioList("type", $this->labels('type'), [
      'individual' => 'Individual',
      'company' => 'Company',
    ])
      ->setDefaultValue('company')
      ->setHtmlAttribute("onchange", "updatePrice()")
      ->addCondition($form::EQUAL, 'individual')
      ->toggle('.individual')
      ->elseCondition()
      ->toggle('.company');
    $form->addTextArea("recipient", $this->labels('recipient'))
      ->setHtmlAttribute("rows", 3)
      ->setHtmlAttribute("placeholder", "Your Street 123\n1234 Your City")
      ->setRequired();
    $form->addText("forename", $this->labels('forename'))
      ->addConditionOn($form['type'], $form::EQUAL, 'individual')
      ->setRequired();
    $form->addText("surname", $this->labels('surname'))
      ->addConditionOn($form['type'], $form::EQUAL, 'individual')
      ->setRequired();
    $form->addText("company", $this->labels('company'))
      ->addConditionOn($form['type'], $form::EQUAL, 'company')
      ->setRequired();
    $form->addSelect("country", $this->labels('country'), $site->countries())
      ->setHtmlAttribute("onchange", "updatePrice()")
      ->setPrompt('Please select a country')
      ->setRequired();
    $form->addText("vatid", $this->labels('vatid'));
    $form->addEmail("email", $this->labels('email'))->setRequired();

    $form->addCheckbox("instant", $this->labels('instant'))->setRequired();

    $file = $this->rockcommerce()->viewFile("main/.checkoutform.html");
    $markup = $this->wire->files->render($file);
    $markup = str_replace('{price}', $price, $markup);
    $markup = str_replace('{subject}', $this->subject(1), $markup);

    $markup = str_replace('{back}', $module->url, $markup);
    $form->addMarkup($markup);

    $form->addSubmit("submit", __("Kostenpflichtig bestellen"))
      ->getControlPrototype()
      ->addClass('uk-width-1-1');
  }

  public function labels(string $prop = null)
  {
    $labels = [
      'type' => __("Order Type"),
      'recipient' => __("Recipient Address"),
      'forename' => __("Forename"),
      'surname' => __("Surname"),
      'company' => __("Company Name"),
      'country' => __("Country"),
      'vatid' => __("VAT ID"),
      'email' => __("E-Mail-Address"),
      'instant' => __("I understand and agree to waive my right of withdrawal for instant fulfillment."),
    ];
    if (!$prop) return $labels;
    if (!array_key_exists($prop, $labels)) return;
    return $labels[$prop];
  }

  public function processInput()
  {
    $input = $this->wire->input;
    $module = $this->wire->pages->get($input->get('module', 'int'));
    if (!$module instanceof ModulePage) throw new WireException("Invalid value for module");
    $license = $this->wire->pages->get($input->get('license', 'int'));
    if (!$license instanceof Block) throw new WireException("Invalid value for license");

    // prepare data
    $values = $this->getValues();
    $values->module = $module->id;
    $values->license = $license->id;
    $values->until = $license->until();

    $net = new Money($license->actualPrice());
    $vat = new Money(0);
    if ($values->type === 'individual' and $values->country === 'aut') {
      $vat = $net->times(0.2);
    }
    $total = $net->plus($vat);

    // create order and payment
    $order = false;
    $payment = false;
    /** @var RockCommerce $rc */
    $rc = $this->wire->modules->get('RockCommerce');
    try {
      $order = $rc->createOrder(
        subject: $this->subject(),
        net: $net,
        vat: $vat,
        total: $total,
        email: $values->email,
        meta: (array)$values,
      );
      $payment = $order->createPayment();
    } catch (\Throwable $th) {
      $this->addError("Something went wrong - error has been logged");
      $this->wire->log->save($rc, $th->getMessage());
    }

    // notify admin about order in any case
    // this is to monitor if anything goes wrong often
    $order->notifyAdmin();

    // redirect to mollie checkout
    if ($payment) {
      $this->wire->session->redirect($payment->_links->checkout->href);
    } else {
      if ($order and $order->id) $order->delete();
      $this->addError("Something went wrong - please try again!");
    }
  }

  public function renderSuccess($values)
  {
    return false;
  }

  /* helpers */

  /**
   * Check if input parameters are valid
   * If not they throw an exception and this method makes sure that the exception
   * does not expose any sensitive information to guest users.
   */
  private function checkInput()
  {
    try {
      $this->module();
      $this->license();
    } catch (\Throwable $th) {
      $this->addMarkup("<div class='uk-alert uk-alert-danger uk-text-center uk-margin-remove'>Invalid Module or License</div>");
      return false;
    }
  }

  public function rockcommerce(): RockCommerce
  {
    return $this->wire->modules->get('RockCommerce');
  }

  public function subject(): string
  {
    $license = $this->license()->name(1);
    if ($license == 'RockSuite') return $license;
    return $this->module()->title . " - " . $license;
  }

  private function module(): ModulePage
  {
    return $this->wire->pages->get($this->wire->input->get('module', 'int'));
  }
  private function license(): Block
  {
    return $this->wire->pages->get($this->wire->input->get('license', 'int'));
  }
}
