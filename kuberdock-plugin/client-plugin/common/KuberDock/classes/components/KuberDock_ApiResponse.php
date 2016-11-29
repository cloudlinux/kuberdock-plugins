<?php

namespace Kuberdock\classes\components;

class KuberDock_ApiResponse {
    /**
     * @var string
     */
    public $raw = '';
    /**
     * @var array
     */
    public $parsed = array();

    /**
     * Getter method
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if(isset($this->{$name})) {
            return $this->{$name};
        } elseif(method_exists($this, 'get'.ucfirst($name))) {
            return $this->{'get'.ucfirst($name)}();
        }
    }

    /**
     * @return bool
     */
    public function getStatus()
    {
        return isset($this->parsed['status']) && ($this->parsed['status'] === 'OK');
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        if (isset($this->parsed['message'])) {
            return $this->parsed['message'];
        } elseif (isset($this->parsed['status'])) {
            if (isset($this->parsed['data']) && $this->parsed['data']) {
                if (is_array($this->parsed['data'])) {
                    $response = array();
                    $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($this->parsed['data']));
                    foreach ($iterator as $row) {
                        $path = array();
                        foreach (range(0, $iterator->getDepth()) as $depth) {
                            $path[] = $iterator->getSubIterator($depth)->key();
                        }
                        $variable = array(implode(': ', $path) . ' - ' . $row);
                        $response = array_merge($response, $variable);
                    }
                    return implode('<br>', $response);
                } else {
                    return $this->parsed['data'];
                }
            } else {
                return $this->parsed['status'];
            }
        } else {
            return 'Undefined response message';
        }
    }

    /**
     * @param string $message
     */
    private function setMessage($message)
    {
        $this->parsed['message'] = $message;
    }

    /**
     * @return array()
     */
    public function getData()
    {
        return isset($this->parsed['data']) ? $this->parsed['data'] : array();
    }
} 