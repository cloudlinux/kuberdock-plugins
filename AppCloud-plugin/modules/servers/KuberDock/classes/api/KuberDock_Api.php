<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace api;

use exceptions\CException;
use exceptions\NotFoundException;
use exceptions\UserNotFoundException;
use Exception;
use extensions\jwt\JWT;

/**
 * Class KuberDock_Api
 * @package api
 */
class KuberDock_Api {
    /**
     *
     */
    const PROTOCOL_HTTP = 'http';
    /**
     *
     */
    const PROTOCOL_HTTPS = 'https';
    /**
     * @var string
     */
    const DATA_TYPE_JSON = 'json';
    /**
     * @var string
     */
    const DATA_TYPE_PLAIN = 'plain';
    /**
     *
     */
    const API_URL = 'https://KUBERDOCK_MASTER_IP';
    /**
     * int seconds
     */
    const API_CONNECTION_TIMEOUT = 150;

    /**
     * int seconds
     */
    const JWT_LIFETIME = 3600;
    /**
     * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
     */
    const JWT_ALGORITHM = 'HS256';

    /**
     * @var string
     */
    protected $dataType;
    /**
     * @var string
     */
    protected $serverUrl;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $requestUrl;
    /**
     * @var string
     */
    protected $requestType = 'GET';
    /**
     * Request arguments
     * @var array
     */
    protected $arguments;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $token;
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @param string $username
     * @param string $password
     * @param string $url
     * @param bool $debug
     */
    public function __construct($username, $password, $url = null, $debug = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;
        $this->serverUrl = $url ? $url : self::API_URL;
        $this->dataType = self::DATA_TYPE_JSON;
    }

    /**
     * @param \KuberDock_Server $server
     * @return KuberDock_Api
     */
    public static function constructByServer($server)
    {
        $serverAttr = $server->getAttributes();
        return new self($serverAttr['username'], $server->decryptPassword(), $server->getApiServerUrl());
    }

    /**
     * Get Token
     * @return string
     * @throws Exception
     */
    public function getToken() {
        $token = $this->token;
        $this->setToken('');

        $this->url = $this->serverUrl . '/api/auth/token';
        $response = $this->call();
        $this->setToken($token);

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return isset($response->parsed['token']) ? $response->parsed['token'] : 'Undefined';
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * @return bool
     */
    public function getDebugMode()
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function setDebugMode($debug = false)
    {
        $this->debug = (bool) $debug;

        return $this;
    }

    /**
     * @return array
     */
    public function getRequestArguments()
    {
        return $this->arguments;
    }

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function setLogin($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * @param array $params
     * @param string $type
     * @return KuberDock_ApiResponse
     * @throws CException
     */
    public function call($params = array(), $type = 'GET')
    {
        if (!in_array($type, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE'))) {
            throw new CException('Undefined request type: '.$type);
        }

        $ch = curl_init();
        $this->arguments = $params;
        $this->requestType = $type;

        switch ($type) {
            case 'POST':
            case 'PUT':
            case 'PATCH':
                $this->requestUrl = $this->url;
                $strData = json_encode($params);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $strData);
                $headers = array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($strData)
                );

                if ($this->token) {
                    $this->requestUrl .= '?token=' . $this->token;
                }

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                break;
            default:
                if ($this->token) {
                    $this->requestUrl = $params ? $this->url .'?token='. $this->token .'&' . http_build_query($params)
                        : $this->url . '?token=' . $this->token;
                } else {
                    $this->requestUrl = $params ? $this->url .'?'. http_build_query($params) : $this->url;
                }

                break;
        }

        curl_setopt($ch, CURLOPT_URL, $this->requestUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::API_CONNECTION_TIMEOUT);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        if(!$this->token) {
            curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch);

        if ($status['http_code'] != KuberDock_ApiStatusCode::HTTP_OK) {
            $err = ucwords(curl_error($ch));
            curl_close($ch);

            switch ($status['http_code']) {
                case KuberDock_ApiStatusCode::HTTP_BAD_REQUEST:
                    break;
                case KuberDock_ApiStatusCode::HTTP_FORBIDDEN:
                    throw new CException(sprintf('Invalid credential for KuberDock server %s', $this->url));
                case KuberDock_ApiStatusCode::HTTP_NOT_FOUND:
                    throw new NotFoundException();
                default:
                    if ($err) {
                        $msg = sprintf('%s (%s): %s', KuberDock_ApiStatusCode::getMessageByCode($status['http_code']),
                            $err, $this->url);
                    } else {
                        $msg = sprintf('%s: %s', KuberDock_ApiStatusCode::getMessageByCode($status['http_code']), $this->url);
                    }
                    throw new CException($msg);
            }
        }

        curl_close($ch);
        $this->parseResponse($response);

        if (KUBERDOCK_DEBUG_API) {
            $this->log(print_r($this->response->raw, true));
        }

        return $this->response;
    }

    /**
     * @param $response
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    private function parseResponse($response)
    {
        $this->response = new KuberDock_ApiResponse();
        $this->response->raw = $response;

        if($this->dataType == self::DATA_TYPE_JSON) {
            $this->response->parsed = json_decode($response, true);
        } elseif($this->dataType == self::DATA_TYPE_PLAIN) {
            if(preg_match_all('/(.+)/m', $response, $match)) {
                foreach($match[1] as $row) {
                    list($key, $value) = explode(':', $row);
                    $this->response->parsed[$key] = $value;
                }
            } else {
                $error = 'Unable to parse plain data';
                $this->logError($error);
                throw new Exception($error);
            }
        } else {
            $error = 'Unknown API data type';
            $this->logError($error);
            throw new Exception($error);
        }

        return $this->response;
    }

    /**
     * @param $values
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function createUser($values)
    {
        $this->url = $this->serverUrl . '/api/users/all';
        $response = $this->call($values, 'POST');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());

            if(strpos($response->getMessage(), 'email - has already been taken') !== false) {
                throw new Exception(sprintf('Cannot create KuberDock user with username: %s because email - has already been taken',
                    $values['username']));
            } else {
                throw new Exception($response->getMessage());
            }
        }

        return $response;
    }

    /**
     * @param $values
     * @param $user
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function updateUser($values, $user)
    {
        $this->url = $this->serverUrl . '/api/users/all/' . $user;
        $response = $this->call($values, 'PUT');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @return \api\KuberDock_ApiResponse
     * @throws Exception
     */
    public function getDefaultKubeType()
    {
        $this->url = $this->serverUrl . '/api/pricing/kubes/default';
        $response = $this->call();

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @return \api\KuberDock_ApiResponse
     * @throws Exception
     */
    public function getDefaultPackageId()
    {
        $this->url = $this->serverUrl . '/api/pricing/packages/default';
        $response = $this->call();

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param string $user
     * @param bool $force
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function deleteUser($user, $force = false)
    {
        $this->url = $this->serverUrl . '/api/users/all/' . $user;
        $response = $this->call(array('force' => $force), 'DELETE');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param string $user
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function unDeleteUser($user)
    {
        $this->url = $this->serverUrl . '/api/users/undelete/' . $user;
        $response = $this->call(array(), 'POST');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param $user
     * @return KuberDock_ApiResponse
     * @throws Exception, UserNotFoundException
     */
    public function getUser($user)
    {
        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->url = $this->serverUrl . '/api/users/all/' . $user;
        try {
            $response = $this->call();
        } catch (NotFoundException $e) {
            throw new UserNotFoundException();
        }

        if (!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getUsers()
    {
        $this->url = $this->serverUrl . '/api/users/all';
        $response = $this->call();

        if (!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param string $user
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return \api\KuberDock_ApiResponse
     * @throws Exception
     */
    public function getUsage($user, \DateTime $dateFrom, \DateTime $dateTo)
    {
        $this->url = $this->serverUrl . "/api/usage/$user";

        $response = $this->call(array(
            'date_from' => $dateFrom->format(\DateTime::ISO8601),
            'date_to' => $dateTo->format(\DateTime::ISO8601),
        ));

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param $date
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getAllUsage($date)
    {
        $this->url = $this->serverUrl . "/api/usage-all/$date";
        $response = $this->call();

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $id
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getKube($id)
    {
        $this->url = sprintf($this->serverUrl . '/api/pricing/kubes/%d', $id);
        $response = $this->call(array(), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getKubes()
    {
        $this->url = $this->serverUrl . '/api/pricing/kubes';
        $response = $this->call(array(), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param string $name
     * @return array|bool
     * @throws Exception
     */
    public function getKubesByName($name)
    {
        $kubes = $this->getKubes()->getData();
        foreach($kubes as $row) {
            if(strtolower($row['name']) == strtolower($name)) {
                return $row;
            }
        }

        return false;
    }

    /**
     * @param int $packageId
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getPackageKubesById($packageId)
    {
        $this->url = sprintf($this->serverUrl . '/api/pricing/packages/%d/kubes-by-id', $packageId);
        $response = $this->call(array(), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $packageId
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getPackageKubesByName($packageId)
    {
        $this->url = sprintf($this->serverUrl . '/api/pricing/packages/%d/kubes-by-name', $packageId);
        $response = $this->call(array(), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $packageId
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getPackageKubes($packageId)
    {
        $this->url = sprintf($this->serverUrl . '/api/pricing/packages/%d/kubes', $packageId);
        $response = $this->call(array(), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $packageId
     * @param array $values
     * @return KuberDock_ApiResponse
     * @throws CException
     */
    public function createPackageKube($packageId, $values)
    {
        $this->url = sprintf($this->serverUrl . '/api/pricing/packages/%d/kubes', $packageId);
        $response = $this->call($values, 'POST');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new CException($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $packageId
     * @param int $kubeId
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function deletePackageKube($packageId, $kubeId)
    {
        $this->url = sprintf($this->serverUrl . '/api/pricing/packages/%d/kubes/%d', $packageId, $kubeId);
        $response = $this->call(array(), 'DELETE');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $packageId
     * @param int $kubeId
     * @param float $kubePrice
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function addKubeToPackage($packageId, $kubeId, $kubePrice = 0.0)
    {
        $this->url = sprintf($this->serverUrl . '/api/pricing/packages/%d/kubes/%d', $packageId, $kubeId);
        $response = $this->call(array(
            'kube_price' => $kubePrice,
        ), 'PUT');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param array $values
     * @return KuberDock_ApiResponse
     * @throws CException
     */
    public function createKube($values)
    {
        $this->url = $this->serverUrl . '/api/pricing/kubes';
        $response = $this->call($values, 'POST');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new CException($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $id
     * @param array $values
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function updateKube($id, $values)
    {
        $this->url = $this->serverUrl . '/api/pricing/kubes/' . $id;
        $params = http_build_query($values);
        $response = $this->call($values, 'PUT');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $id
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function deleteKube($id)
    {
        $this->url = $this->serverUrl . '/api/pricing/kubes/' . $id;
        $response = $this->call(array(), 'DELETE');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param bool $withKubes
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getPackages($withKubes = false)
    {
        $this->url = $this->serverUrl . '/api/pricing/packages';
        $response = $this->call(array(
            'with_kubes' => $withKubes,
        ), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $id
     * @param bool $withKubes
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getPackageById($id, $withKubes = false)
    {
        $this->url = $this->serverUrl . '/api/pricing/packages/' . $id;
        $response = $this->call(array(
            'with_kubes' => $withKubes,
        ), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param name $name
     * @return array|bool
     * @throws Exception
     */
    public function getPackageByName($name)
    {
        $packages = $this->getPackages()->getData();

        foreach($packages as $row) {
            if($row['name'] == $name) {
                return $row;
            }
        }

        return false;
    }

    /**
     * @param array $values
     * @return KuberDock_ApiResponse
     * @throws CException
     */
    public function createPackage($values)
    {
        $this->url = $this->serverUrl . '/api/pricing/packages';
        $response = $this->call($values, 'POST');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new CException($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $id
     * @param array $values
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function updatePackage($id, $values)
    {
        $this->url = $this->serverUrl . '/api/pricing/packages/' . $id;
        $response = $this->call($values, 'PUT');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param int $id
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function deletePackage($id)
    {
        $this->url = $this->serverUrl . '/api/pricing/packages/' . $id;
        $response = $this->call(array(), 'DELETE');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param $values
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function setKubeWeight($values)
    {
        $this->url = $this->serverUrl . '/api/set-kube/weight';
        $response = $this->call($values, 'POST');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getUserRoles()
    {
        $this->url = $this->serverUrl . '/api/users/roles';
        $response = $this->call();

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param string $yaml
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function createPodFromYaml($yaml)
    {
        $this->url = $this->serverUrl . '/api/yamlapi';
        $response = $this->call(array(
            'data' => $yaml,
        ), 'POST');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getPods()
    {
        $this->url = $this->serverUrl . '/api/podapi';
        $response = $this->call(array(), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPod($id)
    {
        $response = $this->getPods();

        foreach($response->getData() as $row) {
            if($row['id'] == $id) {
                return $row;
            }
        }

        return false;
    }

    /**
     * @param $podId
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function startPod($podId)
    {
        $this->url = $this->serverUrl . '/api/podapi/' . $podId;
        $response = $this->call(array(
            'command' => 'start',
        ), 'PUT');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param $podId
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function stopPod($podId)
    {
        $this->url = $this->serverUrl . '/api/podapi/' . $podId;
        $response = $this->call(array(
            'command' => 'stop',
        ), 'PUT');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param string $podId
     * @param array $attributes
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function updatePod($podId, $attributes)
    {
        $data['command'] = 'set';
        $data['commandOptions'] = $attributes;
        $this->url = $this->serverUrl . '/api/podapi/' . $podId;
        $response = $this->call($data, 'PUT');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param string $podId
     * @param array $attributes
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function redeployPod($podId, $attributes)
    {
        $this->url = $this->serverUrl . '/api/podapi/' . $podId;
        $attributes['command'] = 'redeploy';
        $response = $this->call($attributes, 'PUT');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function getNodes()
    {
        $this->url = $this->serverUrl . '/api/nodes';
        $response = $this->call(array(), 'GET');

        if(!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param array $data
     * @param bool $auth
     * @return string
     * @throws CException
     */
    public function getJWTToken($data = array(), $auth = true)
    {
        global $autoauthkey;

        if (!isset($autoauthkey) || !$autoauthkey) {
            throw new CException('Secret key not set. http://docs.whmcs.com/AutoAuth');
        }

        if ($auth) {
            $data = array_merge($data, array(
                'username' => $this->username,
                'auth' => true,
            ));
        }

        return JWT::encode($data, $autoauthkey, self::JWT_ALGORITHM, null, array(
            'exp' => time() + self::JWT_LIFETIME,
        ));
    }

    /**
     * @param string $error
     */
    private function logError($error)
    {
        if($this->debug) {
            $this->log($error);
        }
    }

    /**
     * @param string $message
     */
    private function log($message = '')
    {
        if(function_exists('logModuleCall')) {
            logModuleCall(KUBERDOCK_MODULE_NAME, strtoupper($this->requestType).': '.$this->url,
                print_r($this->arguments, true), '', $message, array($this->username, $this->password));
        }
    }
}
