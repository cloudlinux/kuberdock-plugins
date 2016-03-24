<?php

namespace Kuberdock\classes\api;

use Kuberdock\classes\exceptions\ApiException;

abstract class API
{
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint have been removed, in our
     * or /<endpoint>/<arg0>
     */
    protected $args = array();

    /**
     * Property: file
     * Stores the input of the PUT request
     */
    protected $file = null;

    protected $redirect = null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     * @param $request string
     * @throws \Exception
     */
    public function __construct($request)
    {
        $this->args = explode('/', rtrim($request, '/'));
        $this->endpoint = array_shift($this->args);

        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new \Exception("Unexpected Header");
            }
        }

        switch($this->method) {
            case 'DELETE':
            case 'POST':
                $this->request = $this->cleanInputs($_POST);
                $this->file = file_get_contents("php://input");
                break;
            case 'GET':
                $this->request = $this->cleanInputs($_GET);
                break;
            case 'PUT':
                $this->request = $this->cleanInputs($_GET);
                $this->file = file_get_contents("php://input");
                break;
            default:
                throw new ApiException('Invalid Method', 405);
        }
    }

    public function run()
    {
        $endpoint = strtolower($this->method) . '_' . $this->endpoint;

        // additional parameter, like pods/search/nginx
        if (array_key_exists(0, $this->args) && method_exists($this, $endpoint . '_' . $this->args[0])) {
            $endpoint .= '_' . array_shift($this->args);
        }

        if (!method_exists($this, $endpoint)) {
            throw new ApiException("No Endpoint: $endpoint", 404);
        }

        set_error_handler(array($this, "warningHandler"), E_WARNING);
        $result = call_user_func_array(array($this, $endpoint), $this->args);
        restore_error_handler();

        Response::out($result, $this->redirect);
    }

    private function cleanInputs($data)
    {
        $clean_input = array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }

        return $clean_input;
    }

    /**
     * @param $arg
     * @throws ApiException
     */
    protected function checkNumeric($arg)
    {
        if (!is_numeric($arg) && !is_null($arg)) {
            throw new ApiException('Argument must be numeric');
        }
    }

    /**
     * To catch 'Missing argument' errors
     *
     * @param $errno
     * @param $errstr
     * @throws ApiException
     */
    public function warningHandler($errno, $errstr)
    {
        if (strpos($errstr, 'Missing argument') !== false) {
            throw new ApiException('Missing argument', $errno);
        }
    }

    /**
     * @param bool|false $assoc
     * @return mixed
     */
    protected function getJSONData($assoc = false)
    {
        return json_decode($this->file, $assoc);
    }
}