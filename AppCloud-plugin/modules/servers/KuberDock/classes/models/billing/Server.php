<?php


namespace models\billing;


use api\KuberDock_Api;
use components\BillingApi;
use models\Model;

class Server extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblservers';

    /**
     * @return string
     */
    public function getUrl()
    {
        $scheme = $this->secure == 'on' ? KuberDock_Api::PROTOCOL_HTTPS : KuberDock_Api::PROTOCOL_HTTP;
        $domain = $this->hostname ? $this->hostname : $this->ipaddress;

        return sprintf('%s://%s', $scheme, $domain);
    }

    /**
     * @return KuberDock_Api
     */
    public function getApi()
    {
        $url = $this->getUrl();
        $password = BillingApi::model()->decryptPassword($this->password);;

        $api = new KuberDock_Api($this->username, $password, $url);

        if ($this->accesshash) {
            $api->setToken($this->accesshash);
        }

        return $api;
    }
}