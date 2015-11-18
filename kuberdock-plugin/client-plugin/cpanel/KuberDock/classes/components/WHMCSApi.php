<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class WHMCSApi extends Base {
    /**
     *
     */
    const HOSTING_PANEL_LOGIN = 'hostingPanel';
    /**
     *
     */
    const HOSTING_PANEL_PASSWORD = 'hostingPanel';

    /**
     * Path to reseller list
     */
    const OWNER_PATH = '/etc/trueuserowners';
    /**
     *
     */
    const CONFIG_PATH = '/var/cpanel/apps/kuberdock_whmcs.json';

    /**
     * @var array
     */
    private $_data = array();

    /**
     * @throws CException
     */
    public function __construct()
    {
        $this->setKuberDockInfo();
    }

    /**
     * @return array
     */
    public function getUserInfo()
    {
        return $this->_data['userDetails'];
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->getUserInfo()['id'];
    }

    /**
     * @return array
     */
    public function getCurrency()
    {
        return $this->_data['currency'];
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->_data['products'];
    }

    /**
     * @return array
     */
    public function getProduct()
    {
        return $this->getProducts() ? current($this->getProducts()) : array();
    }

    /**
     * @param int $id
     * @return array
     * @throws CException
     */
    public function getProductById($id)
    {
        foreach($this->getProducts() as $row) {
            if($row['id'] == $id) {
                return $row;
            }
        }

        throw new CException(sprintf('Package with id: %s not founded', $id));
    }

    /**
     * @return array
     */
    public function getServices()
    {
        return $this->_data['userServices'];
    }

    /**
     * @return array
     */
    public function getService()
    {
        return $this->getServices() ? current($this->_data['userServices']) : array();
    }

    /**
     * @return float
     */
    public function getUserCredit()
    {
        return $this->getUserInfo()['credit'];
    }

    /**
     * array
     */
    public function getKubes()
    {
        $kubes = array();
        $currency = $this->getCurrency();
        $packageAttributes = array('paymentType');

        foreach($this->getProducts() as $row) {
            $kubes[$row['id']]['currency'] = $currency;
            $kubes[$row['id']]['kubes'] = Tools::getKeyAsField($row['kubes'], 'kuber_kube_id');
            $kubes[$row['id']]['product_id'] = $row['id'];
            foreach($packageAttributes as $attr) {
                $kubes[$row['id']][$attr] = $row[$attr];
            }
        }

        return $kubes;
    }

    /**
     * @return array
     * @throws CException
     */
    public function getKuberDockInfo()
    {
        $conf = KcliCommand::getConfFile();

        $data = $this->request(array(
            'user' => $_ENV['USER'],
            'userDomains' => implode(',', $this->getUserDomain()),
            'kdServer' => $conf['url'],
        ), 'getkuberdockinfo');

        if($data['result'] != 'success') {
            throw new CException($data['message']);
        }

        return $data['results'];
    }

    /**
     * @return $this
     * @throws CException
     */
    public function setKuberDockInfo()
    {
        $this->_data = $this->getKuberDockInfo();
        return $this;
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
     * @throws CException
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
        $status = curl_getinfo($ch);

        if($status['http_code'] != HTTPStatusCode::HTTP_OK) {
            throw new CException(sprintf('%s: %s', HTTPStatusCode::getMessageByCode($status['http_code']), $url));
        }

        curl_close($ch);
        $arr = json_decode($response, true);

        return $arr ? $arr : $response;
    }

    /**
     * @return array
     * @throws CException
     */
    public function getOwnerData() {
        $owner = 'ALL';
        $ownerData = $this->getConfigData();

        if(isset($ownerData[$owner])) {
            $ownerData[$owner]['owner'] = $owner;
            return $ownerData[$owner];
        } else {
            throw new CException('WHMCS settings file not exists or empty. Please fill in WHMCS settings.');
        }
    }

    /**
     * @return array
     * @throws CException
     */
    public function getDefaults() {
        $ownerData = $this->getConfigData();

        if(isset($ownerData['defaults'])) {
            return $ownerData['defaults'];
        } else {
            throw new CException('Config file is broken or empty. Please fill in defaults via administrator area.');
        }
    }

    /**
     * @return array
     * @throws CException
     */
    public function getConfigData()
    {
        $data = Base::model()->panel->uapi('KuberDock', 'getConfigData', array());

        if(!isset($data['cpanelresult']['result'])) {
            throw new CException('Undefined response from Cpanel/API/KuberDock');
        }

        $result = $data['cpanelresult']['result'];

        if($result['errors']) {
            throw new CException(implode("\n", $result['errors']));
        }

        return json_decode($result['data'], true);
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
     * @return array
     */
    public function getAuthData()
    {
        if($service = $this->getService()) {
            return array(
                $service['username'],
                $service['password'],
                $service['token'],
            );
        } else {
            return $this->getHostingAuthData();
        }
    }

    /**
     * @return array
     */
    public function getAdminAuthData()
    {
        $product = $this->getProduct();

        return array(
            $product['server']['username'],
            $product['server']['password'],
            $product['server']['accesshash'],
        );
    }

    /**
     * @return array
     */
    public function getHostingAuthData()
    {
        return array(self::HOSTING_PANEL_LOGIN, self::HOSTING_PANEL_PASSWORD, '');
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