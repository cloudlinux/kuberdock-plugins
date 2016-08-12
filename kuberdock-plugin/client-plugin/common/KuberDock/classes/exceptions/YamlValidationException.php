<?php

namespace Kuberdock\classes\exceptions;

class YamlValidationException extends \Exception
{
    private $errors = array();

    public function __construct(array $errors){
        if (isset($errors['customFields'])) {
            $this->flattenCustomFields($errors['customFields']);
        }

        if (isset($errors['appPackages'])) {
            $this->flatten($errors['appPackages']);
        }

        if (isset($errors['common'])) {
            $this->flatten($errors['common']);
        }

        if (isset($errors['schema']['kuberdock'])) {
            $this->flatten($errors['schema']['kuberdock']);
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

    private function flattenCustomFields($fields)
    {
        foreach ($fields as $name => $field) {
            $this->errors[] = $name . ': ' . $field['message'] . ' (line: ' . $field['line'] . ', column: ' .$field['column'] . ')';
        }
    }
}