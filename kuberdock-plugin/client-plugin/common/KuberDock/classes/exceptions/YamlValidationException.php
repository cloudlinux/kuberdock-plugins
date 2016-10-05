<?php

namespace Kuberdock\classes\exceptions;

class YamlValidationException extends \Exception
{
    private $errors = array();

    public function __construct(array $errors){
        if (isset($errors['customFields'])) {
            $this->errors[] = $errors['customFields'];
        }

        $keys = array('appPackages', 'common', 'schema');

        foreach ($keys as $key) {
            if (isset($errors[$key])) {
                $this->flatten($errors[$key]);
            }
        }

        parent::__construct(implode('<br>', $this->errors), 0);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function flatten($value, $field = null)
    {
        if (is_array($value)) {
            foreach ($value as $name => $item) {
                if ($field) {
                    $name = is_numeric($name) ? $field : ($field . ' - ' . $name);
                }
                $this->flatten($item, $name);
            }
        } else {
            if ($field) {
                $value = $field . ': ' . $value;
            }
            if ($value) {
                $this->errors[] = $value;
            }
        }
    }
}