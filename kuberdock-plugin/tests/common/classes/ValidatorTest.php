<?php

namespace tests\Kuberdock\classes;


use PHPUnit\Framework\TestCase;
use Kuberdock\classes\Validator;

class ValidatorTest extends TestCase
{
    /**
     * @dataProvider runProvider
     * @param array $rules
     * @param string $value
     * @param array $expected
     */
    public function testRun($rules, $value, $expected)
    {
        $validator = new Validator([
            'APP_NAME' => [
                'name' => 'application name',
                'rules' => $rules,
            ],
        ]);

        $result = $validator->run(array(
            'APP_NAME' => $value,
        ));

        $this->assertInternalType('bool', $result);
        $this->assertEquals($result, (bool) empty($expected));
        $this->assertEquals($validator->getErrorsAsArray(), $expected);
    }

    public function runProvider()
    {
        return [
            'min' => [ ['min' => 3], 'ddfd', [] ],
            'min_lacks' => [ ['min' => 3], 'dd', ['Minimum length of "application name" should be 3 symbols']],
            'max' => [ ['max' => 5], 'd345', [] ],
            'max_exceeds' => [ ['max' => 5], 'xvsdsvsd', ['Maximum length of "application name" should be 5 symbols']],
            'required' => [ ['required' => true,], 'df', []],
            'required_missing' => [ ['required' => true,], null, ['Empty "application name"'] ],
            'alphanum' => [ ['alphanum' => true,], 'df545dfg', []],
            'alphanum_underscore_minus' => [ ['alphanum' => true,], 'df_-545dfg', []],
            'alphanum_percent' => [ ['alphanum' => true,], 'df545%',
                ['Only alphanum characters, minus and underscore allowed in "application name"']
            ],
            'alpha' => [ ['alpha' => true,], 'dffg', []],
            'alpha_underscore_minus' => [ ['alphanum' => true,], 'df_-dfg', []],
            'alpha_digits' => [ ['alpha' => true,], 'df545',
                ['Only alphabetic characters, minus and underscore allowed in "application name"']
            ],
        ];
    }

    public function testErrorsAsString()
    {
        $validator = new Validator([
            'APP_NAME' => [
                'name' => 'application name',
                'rules' => ['min' => 3, 'alpha' => true],
            ],
        ]);

        $result = $validator->run(array(
            'APP_NAME' => 'f^',
        ));

        $expected = 'Minimum length of "application name" should be 3 symbols<br>'
            . 'Only alphabetic characters, minus and underscore allowed in "application name"';

        $this->assertInternalType('bool', $result);
        $this->assertEquals($result, false);
        $this->assertEquals($validator->getErrorsAsString(), $expected);
    }

    /**
     * @dataProvider requiredProvider
     * @param array $value
     * @param array $expected
     */
    public function testRequired($value, $expected)
    {
        $validator = new Validator(array(
            'title' => array(
                'name' => 'App name',
                'rules' => array(
                    'required' => true,
                    'min' => 1,
                    'max' => 10,
                    'alpha' => true,
                ),
            ),
            'template' => array(
                'name' => 'YAML',
                'rules' => array(
                    'required' => true,
                ),
            ),
        ));

        $result = $validator->run($value);

        $this->assertInternalType('bool', $result);
        $this->assertEquals($result, (bool) empty($expected));
        $this->assertEquals($validator->getErrorsAsArray(), $expected);
    }

    public function requiredProvider()
    {
        return [
            'both_fields_satisfy' => [
                ['title' => 'sds', 'template' => 'sdfs'],
                []
            ],
            'required_field_not_isset' => [
                ['title' => 'sds'],
                ['Empty "YAML"']
            ],
            'both_required_fields_not_isset' => [
                ['wrong_title' => 'sds'],
                ['Empty "App name"','Empty "YAML"']
            ],

        ];
    }
}