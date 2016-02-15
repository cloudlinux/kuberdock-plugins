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
     * @throws CException
     */
    public function getUserId()
    {
        $userInfo = $this->getUserInfo();

        if(!isset($userInfo['id'])) {
            throw new CException('Cannot get billing user id');
        }

        return $userInfo['id'];
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
     * @param int $id
     * @return array
     * @throws CException
     */
    public function getProductByKuberId($id)
    {
        foreach($this->getProducts() as $row) {
            if($row['kuber_product_id'] == $id) {
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
     * @throws CException
     */
    public function getUserCredit()
    {
        $userInfo = $this->getUserInfo();

        if(!isset($userInfo['credit'])) {
            throw new CException('Cannot get billing user balance');
        }

        return $userInfo['credit'];
    }

    /**
     * array
     */
    public function getKubes()
    {
        $kubes = array();
        $currency = $this->getCurrency();
        $packageAttributes = array('paymentType', 'pricePersistentStorage', 'priceIP');

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
        $conf = KcliCommand::getConfig();

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
     * @param int $orderId
     * @param bool $autoSetup
     * @return mixed
     * @throws CException
     */
    public function acceptOrder($orderId, $autoSetup = true) {
        $data = $this->request(array(
            'orderid' => $orderId,
            'autosetup' => $autoSetup,
            'sendemail' => true,
        ), 'acceptorder');

        if($data['result'] == 'error') {
            throw new CException($data['message']);
        }

        return $data;
    }

    /**
     * @param int $serviceId
     * @return mixed
     * @throws CException
     */
    public function moduleCreate($serviceId) {
        $data = $this->request(array(
            'accountid' => $serviceId,
        ), 'modulecreate');

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
            switch($status['http_code']) {
                case HTTPStatusCode::HTTP_FORBIDDEN:
                    throw new CException('Invalid credential for billing server');
                case HTTPStatusCode::HTTP_NOT_FOUND:
                    throw new CException('Invalid billing server URL\IP');
                default:
                    throw new CException(sprintf('%s: %s', HTTPStatusCode::getMessageByCode($status['http_code']), $url));
            }
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
        $kuberDockInfo = $this->getKuberDockInfo();

        if(isset($ownerData['defaults'])) {
            return array(
                'packageId' => $kuberDockInfo['default']['packageId']['id'],
                'kubeType' => $kuberDockInfo['default']['kubeType']['id'],
            );
        } else {
            throw new CException('Cannot get default values. Please fill in defaults via administrator area.');
        }
    }

    /**
     * @return array
     * @throws CException
     */
    public function getConfigData()
    {
        $data = Base::model()->panel->uapi('KuberDock', 'getConfigData', array());
        return $this->parseModuleResponse($data);
    }

    /**
     * @return array
     * @throws CException
     */
    public function getGlobalTemplates()
    {
        $data = Base::model()->panel->uapi('KuberDock', 'getGlobalTemplates', array());
        return $this->parseModuleResponse($data);
    }

    /**
     * @param int id
     * @return mixed
     * @throws CException
     */
    public function getGlobalTemplate($id)
    {
        $data = Base::model()->panel->uapi('KuberDock', 'getGlobalTemplate', array('id' => $id));
        return $this->parseModuleResponse($data);
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
     * @throws CException
     */
    public function getUserDomains()
    {
        $response = Base::model()->panel->uapi('DomainInfo', 'domains_data', array('format' => 'hash'));
        return $this->getResponseData($response);
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
    public function getHostingAuthData()
    {
        return array(self::HOSTING_PANEL_LOGIN, self::HOSTING_PANEL_PASSWORD, '');
    }

    /**
     * @param $response
     * @return mixed
     * @throws CException
     */
    private function parseModuleResponse($response)
    {
        $data = $this->getResponseData($response);
        $json = json_decode($data, true);

        if(isset($json['status']) && $json['status'] == 'ERROR') {
            throw new CException(sprintf('%s', $json['message']));
        }

        return $json;
    }

    /**
     * @param $response
     * @return mixed
     * @throws CException
     */
    private function  getResponseData($response)
    {
        if(!isset($response['cpanelresult']['result'])) {
            throw new CException(sprintf('Undefined response from %s:%s',
                $response['cpanelresult']['module'], $response['cpanelresult']['func']));
        }

        $result = $response['cpanelresult']['result'];

        if($result['errors']) {
            throw new CException(implode("\n", $result['errors']));
        }

        return $result['data'];
    }

    /**
     * @return KcliCommand
     */
    public function getCommand()
    {
        list($username, $password, $token) = $this->getAuthData();
        $command = new KcliCommand($username, $password, $token);
        $command->setConfig();

        return $command;
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