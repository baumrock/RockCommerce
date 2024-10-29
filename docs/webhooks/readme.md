# Webhooks

When handling payments, it's often necessary to react to webhooks that are triggered by payment providers. RockCommerce provides a simple way to handle webhooks and also provides tools to make working with and debugging them easier.

## Endpoint

The endpoint for webhooks is `/rockcommerce/webhooks`.

## Logs

RockCommerce will log all requests sent to this endpoint in the `rockcommerce-webhooks` log file. You can view the logs in the ProcessWire Backend under `Logs` > `rockcommerce-webhooks`.

## Webhook Pages

RockCommerce will also create a page for each webhook request in the `Webhooks` page that you can use to view the request and response:

<img src=https://i.imgur.com/S3f1R5A.png class=blur height=450>

## Security

Be sure to not expose any sensitive information in the webhook handler! The best is to not output anything unless really necessary.

### $webhook->echo()

The `$webhook->echo()` method is a simple helper that will echo the received data to the output and also log it to the logs. Note that this will only output data if `$config->debug` is enabled for security reasons!

This is handy, because you can safely use this method during development to quickly inspect what's going on and on proudction it will only log the data to the logs but will not expose any information to the webhook response.

## Webhook Actions

By default, RockCommerce will create the page and save the request input and info and then use an Mollie as Payment Provider to update the order status.

You can use a hook to adjust this to your needs, as every sent request might be different and you might want to handle it differently in your project.

To override the default behaviour, you can replace the `handleWebhook` action:

```php
// update payment status after webhook
wire()->addHookBefore('RockCommerce::handleWebhook', function (HookEvent $event) {
  // don't execute the default action
  $event->replace = true;

  // get webhook page
  $webhook = $event->arguments(0);

  // caution: this is unsanitized input!
  $input = $event->arguments(1);

  // get payment ID from input
  $paymentID = $input->id;

  // find order
  $order = rockcommerce()->getOrderByPaymentID($paymentID);
  if (!$order) {
    $webhook->echo('No order found for payment ' . $paymentID);
  }
  else {
    $webhook->echo("Updating order status for order " . $order->id);

    // update order status
    // get order status from mollie and then write it to the order
    // note: you might wonder why mollie does not send the status with the webhook.
    // The reason is security. In that way it is impossible to manipulate the
    // order status from a manipulated webhook request.
    $api = wire()->modules->get('RockMollie')->api();
    $payment = $api->payments->get($paymentID);
    $order->setStatus($payment->status);
  }
});
```

## Debugging / Developing

For debugging and developing webhooks, you need to send POST requests to the `/rockcommerce/webhooks` endpoint. For this I can highly recommend using an extension like [Postcode](https://marketplace.visualstudio.com/items?itemName=rohinivsenthil.postcode) for VSCode (or your favourite IDE).

<img src=https://i.imgur.com/bg3Ah9J.png class=blur height=400>
