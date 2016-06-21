<?php

namespace Kuberdock\classes\exceptions;

class YamlValidationException extends \Exception
{
    private $errors;

    public function __construct(array $errors){
        $this->flattenCustomFields($errors['customFields']);
        $this->flatten($errors['appPackages']);
        $this->flatten($errors['common']);
        $this->flatten($errors['schema']['kuberdock']);
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