# Billplz Payment Gateway for Laravel

This package provides a simple and easy-to-use interface to interact with the **Billplz Payment Gateway API (v3 and v5)** in Laravel applications. It includes methods for managing collections, payments, and retrieving payment-related information.

---

## Installation

You can install the package via Composer:

```bash
composer require bajjour/billplz
```

## Configuration

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --provider="YourVendor\BillplzPaymentGateway\BillplzServiceProvider" --tag="config"
```

Update your .env file with your Billplz API credentials:

```bash
BILLPLZ_API_KEY="your-api-key"
BILLPLZ_API_VERSION="v5"
BILLPLZ_XSIGNATURE="your-x-signature" #applied in v5 only
BILLPLZ_API_URL="https://www.billplz-sandbox.com/api" #in production use live url
```

## Usage

`Initialize the Service`

You can initialize the Billplz service in your controller:

```bash
use Billplz\Services\BillplzService;


public function __construct(BillplzService $billplz)
{
    $this->billplz = $billplz;
}
```

`List Available Payment Gateways`

Retrieve a list of available payment gateways.

```bash
$gateways = $this->billplz->list_gateways();
```

Response
```bash
//v5 response
{
  "payment_gateways":[{
    "code":"BP-BST01", "active":true, "category":"boost",
    "extras": { "name":null, "visibility":true, "available":true, "isFpx":null, "isObw":null, "isConsent":null }
  },{
    "code":"MBBM2U2", "active":false, "category":"fpx", 
    "extras":{ "name":null, "visibility":false, "available":true, "isFpx":null, "isObw":null, "isConsent":null }
  }, ......
}
//v3 response
{
  "banks":[
    {"name":"MBBM2U2", "active":false},
    {"name":"MB2U0227", "active":true},
    {"name":"PBB0233", "active":true}, 
    ......
}
```

`Create a Collection`

Create a new collection for payments.

```bash
$response = $this->billplz->createCollection(
    title: 'Campaign {id}',
    callback_url: 'https://example.com/callback'
);
```

Response
```bash
//v5 response
{
  "id":"ab66c9f0-9944-4373-a586-86c58420b27b",
  "title":"Campaign {id}",
  "payment_orders_count":0,
  "paid_amount":0,
  "status":"active",
  "callback_url":"https:\/\/your-callback.url\/"
}
//v3 response
{
  "id":"h6bltqha",
  "title":"CAMPAIGN {ID}",
  "logo":{"thumb_url":null,"avatar_url":null},
  "split_payment":{"email":null,"fixed_cut":null,"variable_cut":null,"split_header":false}
}
```

`Get Collection Details`

Retrieve details of a specific collection.

```bash
$response = $this->billplz->getCollection('your_collection_id');
```

Response
```bash
//v5 response
{
  "id":"ab66c9f0-9944-4373-a586-86c58420b27b",
  "title":"Campaign {id}",
  "payment_orders_count":0,
  "paid_amount":0,
  "status":"active",
  "callback_url":"https:\/\/your-callback.url\/"
}
//v3 response
{
  "id":"h6bltqha",
  "title":"CAMPAIGN {ID}",
  "logo":{"thumb_url":null,"avatar_url":null},
  "split_payment":{"email":null,"fixed_cut":null,"variable_cut":null,"split_header":false},
  "status":"active"
}
```

`Get Payment Order Limit`

Available only for v5 and used to retrieve the payment order limit for your account.

```bash
$response = $this->billplz->paymentOrderLimit();
```

Response
```bash
//v5 response
{"total":99700}
```

`Create a Payment`

Create a new payment.

for bank_code in sandbox use "DUMMYBANKVERIFIED", for live use the code returned from list_gateways() function.
```bash
//v5 parameters
$response = $this->billplz->createPayment([
  'payment_order_collection_id' => 'ab66c9f0-9944-4373-a586-86c58420b27b',
  'bank_code' => 'DUMMYBANKVERIFIED',
  'bank_account_number' => 'customer bank account number',
  'name' => 'customer name',
  'description' => 'payment description',
  'total' => 'amount', //smallest currency unit (e.g 100 cents to charge RM 1.00)
]);
//v3 parameters
$response = $this->billplz->createPayment([
  'collection_id' => 'h6bltqha',
  'email' => 'customer email',
  'name' => 'customer name',
  'amount' => 'amount', //smallest currency unit (e.g 100 cents to charge RM 1.00)
  'callback_url' => 'https://example.com/callback',
  'description' => 'payment description'
]);
```

Response
```bash
//v5 response
{
  "id":"88da8ef2-c1cf-45c0-891a-9ac2ee0be03b",
  "payment_order_collection_id":"ab66c9f0-9944-4373-a586-86c58420b27b",
  "bank_code":"DUMMYBANKVERIFIED",
  "bank_account_number":"customer bank account number",
  "name":"customer name",
  "description":"payment description",
  "email":"customer email",
  "status":"enquiring",
  "notification":false,
  "recipient_notification":true,
  "reference_id":null,
  "display_name":null,
  "total":2000
}
//v3 response
{
  "id":"5a0b8533516768cb",
  "collection_id":"h6bltqha",
  "paid":false,
  "state":"due",
  "amount":100,
  "paid_amount":0,
  "due_at":"2025-3-18",
  "email":"bajjour.89@gmail.com",
  "mobile":null,
  "name":"BADR AJJOUR",
  "url":"https:\/\/www.billplz-sandbox.com\/bills\/5a0b8533516768cb",
  "reference_1_label":"Reference 1",
  "reference_1":null,
  "reference_2_label":"Reference 2",
  "reference_2":null,
  "redirect_url":null,
  "callback_url":"shamel.site",
  "description":"101 RM for Campaign 10",
  "paid_at":null
}
```
`Get Payment Details`

Retrieve details of a specific payment.

in v3 use paid = true to know if bill has paid.

in v5 use status = completed to know if bill has paid.

```bash
$this->billplz->getPayment(payment_id: 'your_payment_id')
```

Response
```bash
//v5 response
{
  "id":"88da8ef2-c1cf-45c0-891a-9ac2ee0be03b",
  "payment_order_collection_id":"ab66c9f0-9944-4373-a586-86c58420b27b",
  "bank_code":"DUMMYBANKVERIFIED",
  "bank_account_number":"customer bank account number",
  "name":"customer name",
  "description":"payment description",
  "email":"customer email",
  "status":"completed",
  "notification":false,
  "recipient_notification":true,
  "reference_id":null,
  "display_name":"customer name",
  "total":2000
}
//v3 response
{
  "id":"5a0b8533516768cb",
  "collection_id":"h6bltqha",
  "paid":false,
  "state":"due",
  "amount":100,
  "paid_amount":0,
  "due_at":"2025-3-18",
  "email":"customer email",
  "mobile":null,
  "name":"customer name",
  "url":"https:\/\/www.billplz-sandbox.com\/bills\/5a0b8533516768cb",
  "reference_1_label":"Reference 1",
  "reference_1":null,
  "reference_2_label":"Reference 2",
  "reference_2":null,
  "redirect_url":null,
  "callback_url":"https://example.com/callback",
  "description":"payment description",
  "paid_at":null
}
```

## API Documentation

For more details about the Billplz API, refer to the official documentation:

[billplz API](https://www.billplz-sandbox.com/api#introduction)

## License

[MIT](https://choosealicense.com/licenses/mit/)