# Payment Webhooks

Payment webhooks are used to change the payment status of an order:

<img src="https://i.imgur.com/HtCN8cX.png" class=blur>

When using Mollie as payment provider, you set the webhook URL when creating the payment. Docs about mollie webhooks can be found at https://docs.mollie.com/reference/webhooks.

As you can see in the image above, the webhook URL is `https://rockcommerce.ddev.site/rockcommerce/webhook/`, which will obviously not work, as this is not a publicly resolvable URL. It is only available on our local development machine and can therefore not be accessed by Mollie.

To overcome this technical limitation, you have two options:

1. Send the webhook requests manually from your local machine.
2. Use a service like NGROK to make your local development machine publicly accessible.

## Sending webhook requests manually

One option is to send the webhook requests manually from your local machine. You can do that, for example, using PostCode for VSCode.

First, we create a new request:

<img src=https://i.imgur.com/R7qvfzM.png class=blur>

Then we send a POST request to the webhook URL:

<img src=https://i.imgur.com/AaI82Ug.png class=blur>

As you can see in the bottom section of the screenshot, the request was successful but it did not find an order. That's because we did not provide a payment ID, so let's add that to our request in the "Body" tab:

<img src=https://i.imgur.com/dknrklb.png class=blur>

Now the request was successful and the payment status was updated:

<img src=https://i.imgur.com/H3ShruU.png class=blur>

## Using NGROK

Another option is to use NGROK. If you have never heard of NGROK before, it is a tool that creates a secure URL to access your local machine from the public internet. See https://ngrok.com/ for more information.

If you are using DDEV for local development, all you have to do is run `ddev share` and you'll get a public URL to access your local machine:

<img src=https://i.imgur.com/0KzmNqN.png class=blur>

Next, place an order from your store, but be sure to access the store from the NGROK address! If you do that, the webhook should work and you should get the payment status updated by Mollie automatically and almost instantly:

<img src=https://i.imgur.com/tVelB76.png class=blur>

## Security notes

The webhook URL is **not authenticated by default**. Anyone who can reach `/rockcommerce/webhook` can trigger logging and PSP API lookups. Payment status still comes from the provider API (not from the POST body), but unauthenticated access enables abuse (rate limits, enumeration, log spam).

- RockCommerce applies a **per-IP rate limit** before creating webhook log pages.
- Implement provider verification in a hook, for example Mollie signing or IP allowlists:

```php
$wire->addHookAfter('RockCommerce::verifyWebhookRequest', function ($event) {
  // return false to respond with HTTP 403
});
```

- With `$config->debug` enabled, webhook debug output may leak order/payment hints in HTTP responses. Keep debug off on internet-facing staging/production hosts.
- Manual POST testing (Postman, etc.) is for local development only; never expose debug staging to the public without extra protection.

