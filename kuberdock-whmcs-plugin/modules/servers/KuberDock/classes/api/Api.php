<?php

namespace api;

use exceptions\CException;
use exceptions\NotFoundException;
use Exception;
use Firebase\JWT\JWT;

/**
 * Class Api
 * @package api
 */
class Api {
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
     * @var int
     */
    protected $timeout = self::API_CONNECTION_TIMEOUT;

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
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param array $params
     * @param string $type
     * @return ApiResponse
     * @throws CException
     * @throws NotFoundException
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
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        if (!$this->token) {
            curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch);

        $err = ucwords(curl_error($ch));
        curl_close($ch);

        if ($status['http_code'] != ApiStatusCode::HTTP_OK) {
            switch ($status['http_code']) {
                case ApiStatusCode::HTTP_BAD_REQUEST:
                case ApiStatusCode::HTTP_CONFLICT:
                    break;
                case ApiStatusCode::HTTP_FORBIDDEN:
                    throw new CException(sprintf('Invalid credential for KuberDock server %s', $this->url));
                case ApiStatusCode::HTTP_NOT_FOUND:
                    throw new NotFoundException();
                default:
                    if ($err) {
                        $msg = sprintf('%s (%s): %s', ApiStatusCode::getMessageByCode($status['http_code']),
                            $err, $this->url);
                    } else {
                        $msg = sprintf('%s: %s', ApiStatusCode::getMessageByCode($status['http_code']), $this->url);
                    }
                    throw new CException($msg);
            }
        }

        $this->parseResponse($response);

        if (KUBERDOCK_DEBUG_API) {
            $this->log(print_r($this->response->raw, true));
        }

        return $this->response;
    }

    /**
     * @param string $apiRoute
     * @param array $values
     * @param string $type
     * @return ApiResponse
     * @throws CException
     * @throws Exception
     * @throws NotFoundException
     */
    public function makeCall($apiRoute, $values = array(), $type = 'GET')
    {
        $args = func_get_args();

        if (count($args) == 2 && is_string($args[1])) {
            return $this->makeCall($apiRoute, array(), $args[1]);
        }

        $this->url = $this->serverUrl . $apiRoute;
        $response = $this->call($values, $type);

        if (!$response->getStatus()) {
            $this->logError($response->getMessage());
            throw new Exception($response->getMessage());
        }

        return $response;
    }

    /**
     * @param $values
     * @return ApiResponse
     * @throws Exception
     */
    public function createUser($values)
    {
        $this->url = $this->serverUrl . '/api/users/all';
        $response = $this->call($values, 'POST');

        if (!$response->getStatus()) {
            $this->logError($response->getMessage());

            if (strpos($response->getMessage(), 'email - has already been taken') !== false) {
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
     * @return ApiResponse
     * @throws Exception
     */
    public function updateUser($values, $user)
    {
        return $this->makeCall('/api/users/all/' . $user, $values, 'PUT');
    }

    /**
     * @return \api\ApiResponse
     * @throws Exception
     */
    public function getDefaultKubeType()
    {
        return $this->makeCall('/api/pricing/kubes/default');
    }

    /**
     * @return \api\ApiResponse
     * @throws Exception
     */
    public function getDefaultPackageId()
    {
        return $this->makeCall('/api/pricing/packages/default');
    }

    /**
     * @param string $user
     * @param bool $force
     * @return ApiResponse
     * @throws Exception
     */
    public function deleteUser($user, $force = false)
    {
        return $this->makeCall('/api/users/all/' . $user, array('force' => $force), 'DELETE');
    }

    /**
     * @param string $user
     * @return ApiResponse
     * @throws Exception
     */
    public function unDeleteUser($user)
    {
        return $this->makeCall('/api/users/undelete', array(
            'email' => $user,
        ), 'POST');
    }

    /**
     * @param $user
     * @return ApiResponse
     * @throws Exception, NotFoundException
     */
    public function getUser($user)
    {
        return $this->makeCall('/api/users/all/' . $user);
    }

    /**
     * @return ApiResponse
     * @throws Exception
     */
    public function getUsers()
    {
        return $this->makeCall('/api/users/all', 'GET');
    }

    /**
     * @param string $user
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return \api\ApiResponse
     * @throws Exception
     */
    public function getUsage($user, \DateTime $dateFrom, \DateTime $dateTo)
    {
        return $this->makeCall("/api/usage/$user", array(
            'date_from' => $dateFrom->format(\DateTime::ISO8601),
            'date_to' => $dateTo->format(\DateTime::ISO8601),
        ));
    }

    /**
     * @param $date
     * @return ApiResponse
     * @throws Exception
     */
    public function getAllUsage($date)
    {
        return $this->makeCall("/api/usage-all/$date");
    }

    /**
     * @param int $id
     * @return ApiResponse
     * @throws Exception
     */
    public function getKube($id)
    {
        return $this->makeCall('/api/pricing/kubes/' . $id);
    }

    /**
     * @return ApiResponse
     * @throws Exception
     */
    public function getKubes()
    {
        return $this->makeCall('/api/pricing/kubes');
    }

    /**
     * @param string $name
     * @return array|bool
     * @throws Exception
     */
    public function getKubesByName($name)
    {
        $kubes = $this->getKubes()->getData();

        foreach ($kubes as $row) {
            if (strtolower($row['name']) == strtolower($name)) {
                return $row;
            }
        }

        return false;
    }

    /**
     * @param int $packageId
     * @return ApiResponse
     * @throws Exception
     */
    public function getPackageKubesById($packageId)
    {
        return $this->makeCall(sprintf('/api/pricing/packages/%d/kubes-by-id', $packageId));
    }

    /**
     * @param int $packageId
     * @return ApiResponse
     * @throws Exception
     */
    public function getPackageKubesByName($packageId)
    {
        return $this->makeCall(sprintf('/api/pricing/packages/%d/kubes-by-name', $packageId));
    }

    /**
     * @param int $packageId
     * @return ApiResponse
     * @throws Exception
     */
    public function getPackageKubes($packageId)
    {
        return $this->makeCall(sprintf('/api/pricing/packages/%d/kubes', $packageId));
    }

    /**
     * @param int $packageId
     * @param array $values
     * @return ApiResponse
     * @throws CException
     */
    public function createPackageKube($packageId, $values)
    {
        return $this->makeCall(sprintf('/api/pricing/packages/%d/kubes', $packageId), $values, 'POST');
    }

    /**
     * @param int $packageId
     * @param int $kubeId
     * @return ApiResponse
     * @throws Exception
     */
    public function deletePackageKube($packageId, $kubeId)
    {
        return $this->makeCall(sprintf('/api/pricing/packages/%d/kubes/%d', $packageId, $kubeId), 'DELETE');
    }

    /**
     * @param int $packageId
     * @param int $kubeId
     * @param float $kubePrice
     * @return ApiResponse
     * @throws Exception
     */
    public function addKubeToPackage($packageId, $kubeId, $kubePrice = 0.0)
    {
        return $this->makeCall(sprintf('/api/pricing/packages/%d/kubes/%d', $packageId, $kubeId), array(
            'kube_price' => $kubePrice,
        ), 'PUT');
    }

    /**
     * @param array $values
     * @return ApiResponse
     * @throws CException
     */
    public function createKube($values)
    {
        return $this->makeCall('/api/pricing/kubes', $values, 'POST');
    }

    /**
     * @param int $id
     * @param array $values
     * @return ApiResponse
     * @throws Exception
     */
    public function updateKube($id, $values)
    {
        return $this->makeCall('/api/pricing/kubes/' . $id, $values, 'PUT');
    }

    /**
     * @param int $id
     * @return ApiResponse
     * @throws Exception
     */
    public function deleteKube($id)
    {
        return $this->makeCall('/api/pricing/kubes/' . $id, 'DELETE');
    }

    /**
     * @param bool $withKubes
     * @return ApiResponse
     * @throws Exception
     */
    public function getPackages($withKubes = false)
    {
        return $this->makeCall('/api/pricing/packages', array(
            'with_kubes' => $withKubes,
        ));
    }

    /**
     * @param int $id
     * @param bool $withKubes
     * @return ApiResponse
     * @throws Exception
     */
    public function getPackageById($id, $withKubes = false)
    {
        return $this->makeCall('/api/pricing/packages/' . $id, array(
            'with_kubes' => $withKubes,
        ));
    }

    /**
     * @param string $name
     * @return array
     * @throws Exception
     */
    public function getPackageByName($name)
    {
        $packages = $this->getPackages()->getData();

        foreach ($packages as $row) {
            if ($row['name'] == $name) {
                return $row;
            }
        }

        return [];
    }

    /**
     * @param array $values
     * @return ApiResponse
     * @throws CException
     */
    public function createPackage($values)
    {
        return $this->makeCall('/api/pricing/packages', $values, 'POST');
    }

    /**
     * @param int $id
     * @param array $values
     * @return ApiResponse
     * @throws Exception
     */
    public function updatePackage($id, $values)
    {
        return $this->makeCall('/api/pricing/packages/' . $id, $values, 'PUT');
    }

    /**
     * @param int $id
     * @return ApiResponse
     * @throws Exception
     */
    public function deletePackage($id)
    {
        return $this->makeCall('/api/pricing/packages/' . $id, 'DELETE');
    }

    /**
     * @param string $yaml
     * @return ApiResponse
     * @throws Exception
     */
    public function createPodFromYaml($yaml)
    {
        return $this->makeCall('/api/yamlapi', array(
            'data' => $yaml,
        ), 'POST');
    }

    /**
     * @return ApiResponse
     * @throws Exception
     */
    public function getPods()
    {
        return $this->makeCall('/api/podapi');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPod($id)
    {
        return $this->makeCall('/api/podapi/' . $id)->getData();
    }

    /**
     * @param $podId
     * @return ApiResponse
     * @throws Exception
     */
    public function startPod($podId)
    {
        return $this->makeCall('/api/podapi/' . $podId, array(
            'command' => 'start',
        ), 'PUT');
    }

    /**
     * @param $podId
     * @return ApiResponse
     * @throws Exception
     */
    public function stopPod($podId)
    {
        try {
            return $this->makeCall('/api/podapi/' . $podId, array(
                'command' => 'stop',
            ), 'PUT');
        } catch(\Exception $e) {
            //
        }
    }

    /**
     * @param string $podId
     * @param array $attributes
     * @return ApiResponse
     * @throws Exception
     */
    public function updatePod($podId, $attributes)
    {
        $data['command'] = 'set';
        $data['commandOptions'] = $attributes;

        return $this->makeCall('/api/podapi/' . $podId, $data, 'PUT');
    }

    /**
     * @param string $podId
     * @param array $attributes
     * @return ApiResponse
     * @throws Exception
     */
    public function redeployPod($podId, $attributes)
    {
        $attributes['command'] = 'redeploy';

        return $this->makeCall('/api/podapi/' . $podId, $attributes, 'PUT');
    }

    /**
     * @param string $podId
     * @return ApiResponse
     */
    public function applyEdit($podId)
    {
        $attributes['commandOptions']['applyEdit'] = true;

        return $this->redeployPod($podId, $attributes);
    }

    /**
     * @param string $podId
     * @param string $plan
     * @return ApiResponse
     */
    public function switchPodPlan($podId, $plan)
    {
        return $this->makeCall(sprintf('/api/yamlapi/switch/%s/%s', $podId, $plan), 'PUT');
    }

    /**
     * @return ApiResponse
     * @throws Exception
     */
    public function getNodes()
    {
        return $this->makeCall('/api/nodes');
    }

    /**
     * @return ApiResponse
     * @throws CException
     * @throws Exception
     * @throws NotFoundException
     */
    public function getPD()
    {
        return $this->makeCall('/api/pstorage');
    }

    /**
     * @param int $id
     * @param bool $force
     * @return ApiResponse
     * @throws CException
     * @throws Exception
     * @throws NotFoundException
     */
    public function deletePD($id, $force = false)
    {
        return $this->makeCall('/api/pstorage/' . $id, [
            'force' => $force,
        ], 'DELETE');
    }

    /**
     * @param string $podId
     * @return ApiResponse
     * @throws Exception
     */
    public function unbindIP($podId)
    {
        return $this->makeCall('/api/podapi/' . $podId, array(
            'command' => 'unbind-ip',
        ), 'PUT');
    }

    /**
     * @return ApiResponse
     * @throws Exception
     */
    public function getIpPoolStat()
    {
        return $this->makeCall('/api/ippool/userstat');
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
        if ($this->debug) {
            $this->log($error);
        }
    }

    /**
     * @param string $message
     */
    private function log($message = '')
    {
        if (function_exists('logModuleCall')) {
            logModuleCall(KUBERDOCK_MODULE_NAME, strtoupper($this->requestType).': '.$this->url,
                print_r($this->arguments, true), '', $message, array($this->username, $this->password));
        }
    }

    /**
     * @param $response
     * @return ApiResponse
     * @throws Exception
     */
    private function parseResponse($response)
    {
        $this->response = new ApiResponse();
        $this->response->raw = $response;

        if ($this->dataType == self::DATA_TYPE_JSON) {
            $this->response->parsed = json_decode($response, true);
        } elseif ($this->dataType == self::DATA_TYPE_PLAIN) {
            if (preg_match_all('/(.+)/m', $response, $match)) {
                foreach ($match[1] as $row) {
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
}
