<?php

namespace Sapo\TestAbstraction\Validator;

use Sapo\TestAbstraction\ResultChecker;
use PHPUnit_Framework_TestCase;
use DateTime;

class DateValidator extends PHPUnit_Framework_TestCase
{
    protected $resultChecker;
    protected $variableName;
    /** @var  DateTime */
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
     * @param string $dateTimeRepresentation DateTime representation
     * @return ResultChecker
     */
    public function newerThan($dateTimeRepresentation)
    {
        $compareDateTime = new DateTime($dateTimeRepresentation);
        $diff = $this->variableValue->diff($compareDateTime, $absolute = false);
        $this->assertEquals(1, $diff->invert, "DateTime in {$this->variableName} ({$this->variableValue->format("Y-m-d H:i:s")}) must be newer than {$dateTimeRepresentation}");

        return $this->resultChecker;
    }


}