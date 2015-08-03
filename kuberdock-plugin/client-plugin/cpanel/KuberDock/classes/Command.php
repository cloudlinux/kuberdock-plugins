<?php

class Command {
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
     * List of available command and their separators
     *
     * @var array
     */
    protected $paramSeparators = array();
    /**
     * Current command response data type
     *
     * @var string
     */
    protected $dataType = self::DATA_TYPE_JSON;

    /**
     * Execute command
     *
     * @param array|string $params
     * @return array
     * @throws CException
     */
    public function execute($params = array())
    {
        $this->commandString = is_array($params) ? $this->getCommandString($params) : $params;
        //echo $this->commandString."\n";

        ob_start();
        passthru($this->commandString, $code);
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
        $commandParams = '';

        foreach($params as $param => $value) {
            if(is_numeric($param)) {
                $param = $value;
                $value = '';
            }

            $separator = isset($this->paramSeparators[$param]['separator']) ?
                $this->paramSeparators[$param]['separator'] : ' ';
            $prefix = isset($this->paramSeparators[$param]['prefix']) ?
                $this->paramSeparators[$param]['prefix'] : $this->paramsPrefix;

            $commandParams .= ' '. $prefix . $param . $separator . $value;
        }

        $command = $this->commandPath . ' '. $commandParams;

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
        $parsedResponse = json_decode($response, true);

        if(empty($parsedResponse) && $response) {
            throw new CException($response);
        }

        if(isset($parsedResponse['status']) && $parsedResponse['status'] == self::STATUS_ERROR) {
            throw new CException('Command execution error.'. (SELECTOR_DEBUG ? ' '.$this->commandString : '') .
                (isset($parsedResponse['message']) ? ' '. $parsedResponse['message'] : ''));
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