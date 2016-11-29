<?php


namespace components;


use exceptions\NotEnoughFundsException;
use models\billing\Admin;
use models\billing\Client;
use models\billing\Config;
use models\billing\Invoice;
use models\billing\Order;
use models\billing\Package;
use models\billing\Service;

class BillingApi extends Component
{
    /**
     * @param string $method
     * @param array $values
     * @return array
     * @throws \Exception
     */
    public static function request($method, $values)
    {
        $admin = Admin::getCurrent();

        $results = localAPI($method, $values, $admin->username);

        if ($results['result'] != 'success') {
            throw new \Exception($results['message']);
        }

        return $results;
    }

    /**
     * @param string $password
     * @return string
     * @throws \Exception
     */
    public function decryptPassword($password)
    {
        $response = BillingApi::request('decryptpassword', [
            'password2' => $password,
        ]);

        return $response['password'];
    }

    /**
     * @param string $password
     * @return string
     * @throws \Exception
     */
    public function encryptPassword($password)
    {
        $response = BillingApi::request('encryptpassword', [
            'password2' => $password,
        ]);

        return $response['password'];
    }

    /**
     * @param Service $service
     * @throws \Exception
     */
    public function createModule(Service $service)
    {
        BillingApi::request('modulecreate', [
            'accountid' => $service->id,
        ]);
    }

    /**
     * @param Service $service
     * @param string $reason
     * @throws \Exception
     */
    public static function suspendModule(Service $service, $reason = null)
    {
        BillingApi::request('modulesuspend', [
            'accountid' => $service->id,
            'suspendreason' => $reason,
        ]);
    }

    /**
     * @param Service $service
     * @throws \Exception
     */
    public static function unSuspendModule(Service $service)
    {
        BillingApi::request('moduleunsuspend', [
            'accountid' => $service->id,
        ]);
    }

    /**
     * @param Service $service
     * @throws \Exception
     */
    public function terminateModule(Service $service)
    {
        BillingApi::request('moduleterminate', [
            'accountid' => $service->id,
        ]);
    }

    /**
     * @param Client $client
     * @param InvoiceItemCollection $items
     * @param bool $autoApplyCredit
     * @param \DateTime|null $dueDate
     * @param string|null $gateway
     * @return Invoice
     * @throws \Exception
     */
    public function createInvoice(Client $client, InvoiceItemCollection $items, $autoApplyCredit = true,
                                  \DateTime $dueDate = null, $gateway = null)
    {
        $template = Config::get()->Template;

        $date = date('Ymd', time());

        $values['userid'] = $client->id;
        $values['date'] = $date;
        $values['duedate'] = $dueDate ? $dueDate->format('Y-m-d') : $date;
        $values['paymentmethod'] = $gateway ? $gateway : $client->getGateway();
        $values['sendinvoice'] = true;

        $count = 0;
        $values['notes'] = '';
        foreach ($items as $item) {
            if ($item->getTotal()==0) {
                continue;
            }

            $count++;

            $values['itemdescription' . $count] = $item->getDescription();
            $values['itemamount' . $count] = $item->getTotal();

            if ($item->getTaxed()) {
                $values['itemtaxed' . $count] = true;
            }

            if (!$item->isShort() && $template == 'kuberdock') {
                $values['notes'] .= $item->getHtml($count);
            }
        }

        $values['autoapplycredit'] = $autoApplyCredit;

        $results = BillingApi::request('createinvoice', $values);

        return Invoice::find($results['invoiceid']);
    }

    /**
     * @param Invoice $invoice
     * @param InvoiceItemCollection $invoiceItems
     * @return Invoice
     */
    public function editInvoice(Invoice $invoice, InvoiceItemCollection $invoiceItems)
    {
        $template = Config::get()->Template;

        $values['invoiceid'] = $invoice->id;
        $values['date'] = date('Ymd', time());
        $values['duedate'] = date('Ymd', time());

        $values['notes'] = '';

        foreach ($invoice->items as $item) {
            $values['deletelineids'][$item->id] = $item->id;
        }

        foreach ($invoiceItems as $k => $item) {
            if ($item->getTotal() == 0) {
                continue;
            }

            $values['newitemdescription'][$k] = $item->getDescription();
            $values['newitemamount'][$k] = $item->getTotal();
            $values['newitemtaxed'][$k] = (int) $item->getTaxed();

            if (!$item->isShort() && $template == 'kuberdock') {
                $values['notes'] .= $item->getHtml($k);
            }
        }

        BillingApi::request('updateinvoice', $values);

        return Invoice::find($invoice->id);
    }

    /**
     * @param InvoiceItem $invoiceItem
     * @return InvoiceItem
     * @throws \Exception
     */
    public function addCredit(InvoiceItem $invoiceItem)
    {
        BillingApi::request('addcredit', [
            'clientid' => $invoiceItem->userid,
            'description' => $invoiceItem->description,
            'amount' => $invoiceItem->amount,
        ]);

        return InvoiceItem::find($invoiceItem->id);
    }

    /**
     * @param InvoiceItem $invoiceItem
     * @return InvoiceItem
     * @throws \Exception
     */
    public function removeCredit(InvoiceItem $invoiceItem)
    {
        BillingApi::request('addcredit', [
            'clientid' => $invoiceItem->userid,
            'description' => $invoiceItem->description . ' (Remove)',
            'amount' => -$invoiceItem->amount,
        ]);

        return InvoiceItem::find($invoiceItem->id);
    }

    /**
     * @param Invoice $invoice
     * @return Invoice
     * @throws \Exception
     */
    public function applyCredit(Invoice $invoice)
    {
        if (!$invoice->exists) {
            return $invoice;
        }

        try {
            BillingApi::request('applycredit', [
                'invoiceid' => $invoice->id,
                'amount' => $invoice->total,
            ]);
        } catch (\Exception $e) {
            if (stripos($e->getMessage(), 'Amount exceeds') !== false) {
                throw new NotEnoughFundsException($e->getMessage());
            }

            throw $e;
        }

        return Invoice::find($invoice->id);
    }

    /**
     * @param Client $client
     * @param Package $package
     * @param float|null $price
     * @return Service
     * @throws \Exception
     */
    public function createOrder(Client $client, Package $package, $price = null)
    {
        $values['clientid'] = $client->id;
        $values['pid'] = $package->id;
        $values['paymentmethod'] = $client->getGateway();

        if ($price) {
            $values['priceoverride'] = $price;
        }

        $response = BillingApi::request('addorder', $values);

        return Service::find($response['productids']);
    }

    /**
     * @param Order $order
     * @param bool $autoSetup
     * @param bool $sendEmail
     * @return Order
     * @throws \Exception
     */
    public function acceptOrder(Order $order, $autoSetup = true, $sendEmail = true)
    {
        BillingApi::request('acceptorder', [
            'orderid' => $order->id,
            'autosetup' => $autoSetup,
            'sendemail' => $sendEmail,
        ]);

        return Order::find($order->id);
    }

    /**
     * Related ID
     * General Email Type = Client ID (tblclients.id)
     * Product Email Type = Service ID (tblhosting.id)
     * Domain Email Type = Domain ID (tbldomains.id)
     * Invoice Email Type = Invoice ID (tblinvoices.id)
     * Support Email Type = Ticket ID (tbltickets.id)
     * Affiliate Email Type = Affiliate ID (tblaffiliates.id)
     * @param int $id
     * @param string $name
     * @param array $params
     * @throws \Exception
     */
    public function sendPreDefinedEmail($id, $name, $params = [])
    {
        BillingApi::request('sendemail', [
            'messagename' => $name,
            'customvars' => base64_encode(serialize($params)),
            'id' => $id,
        ]);
    }

    /**
     * @param array $vars
     * @return object
     */
    public static function getApiParams($vars) {
        $param = [
            'action' => [],
            'params' => []
        ];
        $param['action'] = $vars['_POST']['action'];
        unset($vars['_POST']['username']);
        unset($vars['_POST']['password']);
        unset($vars['_POST']['action']);
        $param['params'] = (object) $vars['_POST'];

        return (object) $param;
    }

    /**
     * @param int $productId
     */
    public function addProductToCart($productId)
    {
        $sessionProducts = &$_SESSION['cart']['products'];

        $sessionProducts = [[
            'pid' => $productId,
            'domain' => '',
            'billingcycle' => null,
            'configoptions' => null,
            'customfields' => null,
            'addons' => null,
            'server' => null,
        ]];
    }


    /**
     * @param int $productId
     */
    public function deleteProductFromCart($productId)
    {
        foreach ($_SESSION['cart']['products'] as $k => $row) {
            if ($row['pid'] == $productId) {
                unset($_SESSION['cart']['products'][$k]);
            }
        }
    }

    /**
     * @param string $url
     * @param Client $client
     * @return string
     */
    public static function generateAutoAuthLink($url, Client $client)
    {
        global $autoauthkey;

        $config = Config::get();

        if (isset($autoauthkey) && $autoauthkey) {
            $loginUrl = $config->SystemURL . '/dologin.php';
            $timestamp = time();
            $hash = sha1($client->email . $timestamp . $autoauthkey);
            return sprintf('%s?email=%s&timestamp=%s&hash=%s&goto=%s',
                $loginUrl, $client->email, $timestamp, $hash, urlencode($url));
        } else {
            return $config->SystemURL . '/' . $url;
        }
    }
}