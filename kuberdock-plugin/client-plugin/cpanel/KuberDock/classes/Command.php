<?php

abstract class Command {
    /**
     * JSON data type
     */
    const DATA_TYPE_JSON = 'json';
    /**
     * PLAIN data type
     */
    const DATA_TYPE_PLAIN  = 'plain';
    /**
     * Command execution success response value
     */
    const STATUS_SUCCESS = 'OK';
    /**
     * Command execution error response value
     */
    const STATUS_ERROR = 'ERROR';
    /**
     * Command execution partial response value
     */
    const STATUS_PARTIAL = 'PARTIAL';

    /**
     * Absolute command path
     *
     * @var string
     */
    protected $commandPath;
    /**
     * String representation of command
     *
     * @var string
     */
    protected $commandString;
    /**
     * Command params prefix
     *
     * @var string
     */
    protected $paramsPrefix = '--';
    /**
     * @var
     */
    protected $params;
    /**
     * Current command response data type
     *
     * @var string
     */
    protected $dataType = self::DATA_TYPE_JSON;

    /**
     * Get auth data for command authorization
     *
     * Token auth - array('token' => 'some_token')
     * Plain auth - array('username' => 'some_login', 'password' => 'some_pass')
     * @return array
     */
    abstract protected function getAuth();

    /**
     * Execute command
     *
     * @param array|string $params
     * @return array
     * @throws CException
     */
    public function execute($params = array())
    {
        $params = array_merge($this->getAuth(), $params);
        $this->commandString = is_array($params) ? $this->getCommandString($params) : $params;
        //echo $this->commandString."\n";

        ob_start();
        passthru($this->commandString . ' 2>&1');
        $response = ob_get_contents();
        ob_end_clean();

        return $this->parseResponse($response);
    }

    /**
     * @param self::DATA_TYPE_PLAIN|self::DATA_TYPE_JSON $type
     * @return $this
     */
    public function setDataType($type)
    {
        $this->dataType = $type;

        return $this;
    }

    /**
     * Get command string representation
     *
     * @param array $params
     * @return string
     */
    protected function getCommandString($params = array())
    {
        $commandParams = array();

        foreach($params as $param => $value) {
            if(is_numeric($param)) {
                $commandParams[] = $value;
            } else {
                $commandParams[] = sprintf('%s %s', $param, $value);
            }
        }

        $command = $this->commandPath . ' '. implode(' ', $commandParams);

        return $command;
    }

    /**
     * Get parsed command response depending of current data type
     *
     * @param string $response
     * @return array
     */
    private function parseResponse($response)
    {
        $data = array();

        switch($this->dataType) {
            case self::DATA_TYPE_JSON:
                $response = $this->parseJsonResponse($response);
                break;
            case self::DATA_TYPE_PLAIN:
                $data = $response;
                break;
        }

        if(isset($response['data']) && is_array($response['data'])) {
            $data = $response['data'];
        } elseif($response) {
            $data = $response;
        }

        return $data;
    }

    /**
     * Get parsed command JSON response
     *
     * @param string $response
     * @return array JSON decoded value
     * @throws CException
     */
    private function parseJsonResponse($response)
    {
        // TODO: temp. Need fixes in kcli
        if(in_array(trim(preg_replace('/\s+/', '', $response)), array(
            '{"status":"pending"}{"status":"ERROR","message":"401ClientError:UNAUTHORIZED"}', ''))) {
            return '';
        }

        $parsedResponse = json_decode($response, true);

        if(!is_array($parsedResponse) && empty($parsedResponse) && $response) {
            throw new CException((SELECTOR_DEBUG ? $this->commandString . '<br>' : '') . $response);
        }

        if(isset($parsedResponse['status']) && $parsedResponse['status'] == self::STATUS_ERROR) {
            if(is_array($parsedResponse['message'])) {
                $message = '';
                array_walk_recursive($parsedResponse['message'], function($e, $k) use (&$message) {
                    if(!is_array($e)) {
                        $message .= "$k: $e<br>";
                    }
                });
                throw new CException($message);
            } else {
                throw new CException('Command execution error.' . (SELECTOR_DEBUG ? ' ' . $this->commandString : '') .
                    (isset($parsedResponse['message']) ? ' ' . $parsedResponse['message'] : ''));
            }
        }

        if(isset($parsedResponse['status']) && $parsedResponse['status'] == self::STATUS_PARTIAL) {
            throw new CException('Command execution partial error.'. (SELECTOR_DEBUG ? ' '.$this->commandString : '') .
                (isset($parsedResponse['message']) ? ' '. $parsedResponse['message'] : ''));
        }

        if(isset($parsedResponse['status']) && count($parsedResponse) == 1 && $parsedResponse['status'] != 'OK' && !in_array($parsedResponse['status'], array('pending', 'stopped'))) {
            throw new CException($parsedResponse['status']);
        }

        return $parsedResponse;
    }
} 