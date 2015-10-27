<?php

namespace Sapo\TestAbstraction\Validator;

use Sapo\TestAbstraction\ResultChecker;
use PHPUnit_Framework_TestCase;

class NumericalValidator extends PHPUnit_Framework_TestCase
{
    protected $resultChecker;
    protected $variableName;
    protected $variableValue;
    protected $contextMessage;

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
     * @param $expectedValue
     * @return ResultChecker
     */
    public function thatEquals($expectedValue)
    {
        $expectedType = gettype($expectedValue);
        $actualType = gettype($this->variableValue);
        $this->assertInternalType($expectedType, $this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to be of type {$expectedType}, instead found $actualType");
        $this->assertEquals($expectedValue, $this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to have $expectedValue, instead found {$this->variableValue}");
        return $this->resultChecker;
    }

    /**
     * Checks that the variable under analysis has a value under a determined threshold
     * @param mixed $comparisonValue the value that tops our value
     * @return ResultChecker
     */
    public function thatIsLessThan($comparisonValue)
    {
        $this->assertLessThan($comparisonValue, $this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to be Less than {$comparisonValue}, instead found {$this->variableValue}");
        return $this->resultChecker;
    }


    /**
     * Checks that the variable under analysis has a value over a determined threshold
     * @param mixed $comparisonValue the value we should top
     * @return ResultChecker
     */
    protected function thatIsGreaterThan($comparisonValue)
    {
        $this->assertGreaterThan($comparisonValue, $this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to be Greater than {$comparisonValue}, instead found {$this->variableValue}");
        return $this->resultChecker;
    }

}