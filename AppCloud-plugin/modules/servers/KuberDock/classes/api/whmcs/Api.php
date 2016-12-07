<?php

namespace api\whmcs;


use components\BillingApi;

abstract class Api
{
    /** @var  object */
    protected $postFields;

    public static function call($vars)
    {
        return (new static())->callApi($vars);
    }

    public function callApi($vars)
    {
        try {
            $this->postFields = BillingApi::getApiParams($vars);

            foreach ($this->getRequiredParams() as $attr) {
                if (!isset($this->postFields->params->{$attr}) || !$this->postFields->params->{$attr}) {
                    throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
                }
            }

            return ['result' => 'success', 'results' => $this->answer()];
        } catch (\Exception $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function getParam($param)
    {
        return $this->postFields->params->{$param};
    }

    abstract protected function answer();

    abstract protected function getRequiredParams();
}