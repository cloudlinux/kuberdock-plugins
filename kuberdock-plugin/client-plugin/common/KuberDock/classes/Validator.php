<?php

namespace Kuberdock\classes;

class Validator
{
    private $errors = array();
    private $rules = array();

    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    public function run($data)
    {
        foreach ($data as $field => $value) {
            if (isset($this->rules[$field])) {
                $field_name = $this->rules[$field]['name'];
                foreach ($this->rules[$field]['rules'] as $validator => $mustBe) {
                    try {
                        $this->{$validator . 'Action'}($field_name, $value, $mustBe);
                    } catch (\Exception $e) {
                        $this->errors[] = $e->getMessage();
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function getErrorsAsString()
    {
        return implode('<br>', $this->errors);
    }

    private function minAction($field, $value, $mustBe)
    {
        if ((strlen($value) < $mustBe)) {
            throw new \Exception(sprintf('Minimum length of "%s" should be %d symbols', $field, $mustBe));
        }
    }

    private function maxAction($field, $value, $mustBe)
    {
        if ((strlen($value) > $mustBe)) {
            throw new \Exception(sprintf('Maximum length of "%s" should be %d symbols', $field, $mustBe));
        }
    }

    private function requiredAction($field, $value, $mustBe)
    {
        if (strlen($value) == 0) {
            throw new \Exception(sprintf('Empty "%s"', $field));
        }
    }

    private function alphanumAction($field, $value, $mustBe)
    {
        $validSymbols = array('-', '_');
        if(!ctype_alnum(str_replace($validSymbols, '', $value))) {
            throw new \Exception(sprintf('Only alphanum characters, minus and underscore allowed in "%s"', $field));
        }
    }
}