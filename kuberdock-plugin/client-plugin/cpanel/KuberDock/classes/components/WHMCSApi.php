<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class WHMCSApi extends Base {
    /**
     * Path to reseller list
     */
    const OWNER_PATH = '/etc/trueuserowners';
    /**
     *
     */
    const OWNER_DATA_PATH = '/var/cpanel/apps/kuberdock_whmcs.json';

    /**
     * @var array
     */
    private $_kuberProduct = array();
    /**
     * @var array
     */
    private $_kuberKubes = array();

    /**
     * @return mixed
     */
    public function getUserKuberDockProduct() {
        $clientId = $this->getWHMCSClientId();
        $kuberProducts = $this->getKuberDockProducts();
        $userProducts = $this->searchClientProducts(array('clientid' => $clientId));

        foreach($userProducts as $row) {
            if(isset($kuberProducts[$row['pid']])) {
                $this->_kuberProduct[$row['pid']] = $kuberProducts[$row['pid']];
                $this->_kuberProduct[$row['pid']]['server'] = $row;
            }
        }

        return $this->_kuberProduct;
    }

    /**
     * @return array
     * @throws CException
     */
    public function getKuberDockProducts() {
        $data = $this->request(array(), 'getkuberproducts');
        $packageAttributes = array('paymentType');

        if($data['result'] != 'success') {
            throw new CException($data['message']);
        }

        $products = array();
        foreach($data['results'] as $row) {
            $products[$row['id']] = $row;
            $this->_kuberKubes[$row['id']]['currency'] = $row['currency'];
            $this->_kuberKubes[$row['id']]['kubes'] = Tools::getKeyAsField($row['kubes'], 'kuber_kube_id');
            foreach($packageAttributes as $attr) {
                $this->_kuberKubes[$row['id']][$attr] = $row[$attr];
            }
        }
        return $products;
    }

    /**
     * @param $clientId
     * @return mixed
     * @throws CException
     */
    public function getClientDetails($clientId) {
        $data = $this->request(array('clientid' => $clientId), 'getclientsdetails');

        if($data['result'] == 'error') {
            throw new CException($data['message']);
        }

        return $data;
    }

    /**
     * @return mixed
     * @throws CException
     */
    public function getWHMCSClientId()
    {
        $userDomains = $this->getUserDomain();
        $cPanelProduct = $this->searchClientProducts(array('username2' => $_ENV['USER']));

        foreach($cPanelProduct as $row) {
            if(in_array($row['domain'], $userDomains)) {
                $whmcsClientId = $row['clientid'];
                break;
            }
        }

        if(!isset($whmcsClientId)) {
            throw new CException('User has no cPanel service in WHMCS');
        }

        return $whmcsClientId;
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CException
     */
    public function searchClientProducts($params = array()) {
        $data = $this->request($params, 'getclientsproducts');

        if(isset($data['products']['product'])) {
            return $data['products']['product'];
        }

        throw new CException('Can not find assigned WHMCS product (user has no cPanel product). Reason: ' . $data['message']);
    }

    /**
     * @param int $clientId
     * @return mixed
     * @throws CException
     */
    public function searchClientKuberProducts($clientId) {
        $data = $this->request(array(
            'clientid' => $clientId,
        ), 'getclientskuberproducts');


        if(isset($data['results']) && $data['results']) {
            return $data['results'];
        }

        throw new CException('Can not find assigned WHMCS product (user has no cPanel product). Reason: ' . $data['message']);
    }

    /**
     * @param $clientId
     * @param $productId
     * @return mixed
     * @throws CException
     */
    public function addOrder($clientId, $productId) {
        $paymentMethod = current($this->getPaymentMethods());

        $data = $this->request(array(
            'clientid' => $clientId,
            'pid' => $productId,
            'paymentmethod' => $paymentMethod['module'],
            'billingcycle' => 'free',
        ), 'addorder');

        if($data['result'] == 'error') {
            throw new CException($data['message']);
        }

        return $data;
    }

    /**
     * @param $orderId
     * @return mixed
     * @throws CException
     */
    public function acceptOrder($orderId) {
        $data = $this->request(array(
            'orderid' => $orderId,
            'autosetup' => true,
            'sendemail' => true,
        ), 'acceptorder');

        if($data['result'] == 'error') {
            throw new CException($data['message']);
        }

        return $data;
    }

    /**
     * @return mixed
     * @throws CException
     */
    public function getPaymentMethods() {
        $data = $this->request(array(), 'getpaymentmethods');

        if($data['result'] == 'error') {
            throw new CException($data['message']);
        }

        return $data['paymentmethods']['paymentmethod'];
    }

    /**
     * @param int $invoiceId
     * @return mixed
     * @throws CException
     */
    public function getInvoice($invoiceId) {
        $data = $this->request(array(
            'invoiceid' => $invoiceId,
        ), 'getinvoice');

        if($data['status'] == 'error') {
            throw new CException($data['message']);
        }

        return $data;
    }

    /**
     * @param $data
     * @param $action
     * @return mixed
     */
    public function request($data, $action) {
        $ownerData = $this->getOwnerData();

        $url = $ownerData['server'] . '/includes/api.php';
        $username = $ownerData['username'];
        $password = $ownerData['password'];

        $post = array();
        $post['username'] = $username;
        $post['password'] = md5($password);
        $post['action'] = $action;
        $post['responsetype'] = 'json';
        $post = array_merge($post, $data);

        $query_string = '';
        foreach ($post AS $k=>$v) $query_string .= "$k=".urlencode($v).'&';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);

        if (curl_error($ch)) die('Connection Error: '.curl_errno($ch).' - '.curl_error($ch));
        curl_close($ch);

        $arr = json_decode($response, true);

        return $arr ? $arr : $response;
    }

    /**
     * @return array
     * @throws CException
     */
    public function getOwnerData() {
        $currentUser = $_ENV['USER'];
        $owner = 'ALL';

        if(!file_exists(self::OWNER_DATA_PATH)) {
            throw new CException('WHMCS settings file not exists. Please fill in WHMCS settings.');
        }

        $ownerData = file_get_contents(self::OWNER_DATA_PATH);
        $ownerData = $ownerData ? json_decode($ownerData, true) : array();
        /*
        $lines = file(self::OWNER_PATH);
        foreach($lines as $line) {
            $tmp = explode(':', $line);
            if(count($tmp) != 2) continue;

            list($user, $owner) = $tmp;
            if(trim($user) == $currentUser) {
                $owner = trim($owner);
                break;
            }
        }*/

        if(isset($ownerData[$owner])) {
            $ownerData[$owner]['owner'] = $owner;
            return $ownerData[$owner];
        } else {
            throw new CException('WHMCS settings file not exists or empty. Please fill in WHMCS settings.');
        }
    }

    /**
     * @return array
     */
    public function getUserDomain()
    {
        $domains = array();
        $domain = Base::model()->panel->api2('DomainLookup', 'getmaindomain');

        foreach($domain['cpanelresult']['data'] as $row) {
            if(isset($row['main_domain'])) {
                $domains[] = $row['main_domain'];
            }
        }

        return $domains;
    }

    /**
     * @param null|int $productId
     * @return array
     */
    public function getAuthData($productId = null)
    {
        if($productId && isset($this->_kuberProduct[$productId])) {
            return array(
                $this->_kuberProduct[$productId]['server']['username'],
                $this->_kuberProduct[$productId]['server']['password'],
            );
        } elseif($this->_kuberProduct) {
            $product = current($this->_kuberProduct);
            if(empty($product['server']['username'])) {
                $conf = KcliCommand::getConfFile();
                return array($conf['user'], $conf['password']);
            } else {
                return array(
                    $product['server']['username'],
                    $product['server']['password'],
                );
            }
        } else {
            $conf = KcliCommand::getConfFile();
            return array($conf['user'], $conf['password']);
        }
    }

    /**
     * @return array
     */
    public function getAdminAuthData()
    {
        $conf = KcliCommand::getConfFile(true);

        return array($conf['user'], $conf['password']);
    }

    /**
     * @return array
     */
    public function getKuberKubes()
    {
        if(!$this->_kuberKubes) {
            $this->getKuberDockProducts();
        }

        return $this->_kuberKubes;
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
}