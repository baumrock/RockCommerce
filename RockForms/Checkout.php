<?php

namespace RockForms;

use ProcessWire\ModulePage;
use ProcessWire\RockCommerce;
use ProcessWire\WireException;
use RockPageBuilder\Block;

class Checkout extends RockForm
{

  public function buildForm()
  {
    // check get parameters for validity
    if ($this->checkInput() === false) return;

    // variables
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
      ->setHtmlAttribute("onkeyup", "updatePrice(event)")
      ->setHtmlAttribute("onchange", "updatePrice(event)");

    $form->addRadioList("type", $this->labels('type'), [
      'person' => 'Human Person',
      'company' => 'Company',
    ])
      ->setDefaultValue('company')
      ->addCondition($form::EQUAL, 'person')
      ->toggle('.person')
      ->elseCondition()
      ->toggle('.company');
    $form->addTextArea("recipient", $this->labels('recipient'))
      ->setHtmlAttribute("rows", 3)
      ->setHtmlAttribute("placeholder", "Some Street 123\n1234 Some City")
      ->setRequired();
    $form->addText("forename", $this->labels('forename'))
      ->addConditionOn($form['type'], $form::EQUAL, 'person')
      ->setRequired();
    $form->addText("surname", $this->labels('surname'))
      ->addConditionOn($form['type'], $form::EQUAL, 'person')
      ->setRequired();
    $form->addText("company", $this->labels('company'))
      ->addConditionOn($form['type'], $form::EQUAL, 'company')
      ->setRequired();
    $form->addText("vatid", $this->labels('vatid'));
    $form->addEmail("email", $this->labels('email'))->setRequired();

    $form->addCheckbox("instant", $this->labels('instant'))->setRequired();

    $file = $this->rockcommerce()->viewFile("main/.checkoutform.html");
    $markup = $this->wire->files->render($file);
    $markup = str_replace('{price}', $price, $markup);
    $markup = str_replace('{subject}', $this->subject(1), $markup);

    $module = $this->wire->pages->get($this->wire->input->get('module', 'int'));
    if (!$module instanceof ModulePage) throw new WireException("Invalid value for module");
    $markup = str_replace('{back}', $module->url, $markup);
    $form->addMarkup($markup);

    $form->addSubmit("submit", __("Kostenpflichtig bestellen"))
      ->getControlPrototype()
      ->addClass('uk-width-1-1');

    $form->addMarkup('<script>
      function updatePrice(e) {
        let util = UIkit.util;
        let amount = e.target.value;
        let $price = util.$("#price");
        let EUR = new Intl.NumberFormat("de-DE", {
          style: "currency",
          currency: "EUR",
        });
        $price.innerText = EUR.format(amount * util.data($price, "price"));
      }
      function showLoading(e) {
        if(!Nette.validateForm(e.target)) return;
        UIkit.modal.dialog("<div class=\'uk-padding uk-text-center\'><strong>Processing your request ...</strong><div class=uk-margin-top><span class=loader></span></div></div>");
      }
      </script>');
  }

  public function labels(string $prop = null)
  {
    $labels = [
      'type' => __("Order Type"),
      'recipient' => __("Recipient Address"),
      'forename' => __("Forename"),
      'surname' => __("Surname"),
      'company' => __("Company Name"),
      'vatid' => __("VAT ID"),
      'email' => __("E-Mail-Address"),
      'instant' => __("I want an instant fulfillment and acknowledge that I lose my right of withdrawal."),
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

    $price = $license->actualPrice() * $values->amount;

    // create order and payment
    $order = false;
    $payment = false;
    /** @var RockCommerce $rc */
    $rc = $this->wire->modules->get('RockCommerce');
    try {
      $order = $rc->createOrder(
        subject: $this->subject(),
        price: $price,
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
