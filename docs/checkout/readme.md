# Checkout

## VAT Number Verification

If your local tax regulations require VAT number verification (like in the EU), you can implement this functionality in your checkout process. Here's how to do it:

1. Add a VAT number field to your checkout form
2. Implement validation using the VIES (VAT Information Exchange System) API
3. Store the verified VAT number with the order

I had to do quite some research how this can be done in the EU. There seem to be many services that charge money for a service that is actually provided for free by the EU but is just badly documented.

All you need to do is to send a SOAP request to their service - here using a code snippet that you can use in the Tracy Console:

```php
// check the vat number of google ireland: IE6388047V
$client = new \SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
d($client->checkVat(['countryCode' => 'IE', 'vatNumber' => '6388047V']));

// RESULT:
// stdClass
//   countryCode: 'IE'
//   vatNumber: '6388047V'
//   requestDate: '2025-05-30+02:00'
//   valid: true
//   name: 'GOOGLE IRELAND LIMITED'
//   address: '3RD FLOOR, GORDON HOUSE, BARROW STREET, DUBLIN 4'
```
