<?php

namespace Sapo\TestAbstraction\Validator;

use Sapo\TestAbstraction\ResultChecker;
use PHPUnit_Framework_TestCase;
use DateTime;

class ConditionalValidator extends PHPUnit_Framework_TestCase
{
    protected $resultChecker;
    protected $variableName;
    /** @var  DateTime */
    protected $variableValue;
    protected $contextMessage;
    protected $conditionalResult;
    /**
     * @param ResultChecker $rc
     * @param string $name name of the variable to be tested
     * @param string $value value of the variable to be tested
     * @param string $contextMessage
     * @return NumericalValidator
     */
    public static function factory(ResultChecker $rc, $name, $value, $contextMessage = '')
    {
        $test = new self;
        $test->resultChecker = $rc;
        $test->variableName = $name;
        $test->variableValue = $value;
        $test->contextMessage = $contextMessage;
        return $test;
    }

    /**
     * @param string $stringValue
     * @return ResultChecker
     */
    public function equals($stringValue)
    {
        $this->conditionalResult = ($this->variableValue == $stringValue);
        return $this;
    }

    public function __call($method, $arguments)
    {
        if ($this->conditionalResult)
            $this->resultChecker = call_user_func_array(array($this->resultChecker, $method), $arguments);
        return $this;
    }

    public function endConditional() { return $this->resultChecker; }
    public function end() { return $this->resultChecker; }
}