<?php

namespace Sapo\TestAbstraction;

use GuzzleHttp\Exception\RequestException;
use Sapo\GuzzleWrapper\GuzzleWrapper;

class ServiceCallTestCase extends \PHPUnit_Framework_TestCase {

    private $serviceHandler;
    private $serviceResult;

    private $currentProperty;
    private $currentPropertyName;

    public function __construct($result = null) { $this->serviceResult = $result; }

    protected function setUp($serviceHandler) { $this->serviceHandler = $serviceHandler; }

    public function call($function, $params)
    {
        try {
            $this->serviceResult = call_user_func_array(array($this->serviceHandler, $function), $params);

        } catch(RequestException $e) { 
            $this->fail('Call to operation ' . get_class($this->serviceHandler) . '::' . $function . 
                " failed with exception: \n". GuzzleWrapper::parseExceptionMessage($e)); 
        }
        return $this;
    }
    
    public function getServiceCallResult() { return $this->serviceResult; }

    public function expectsObject($objectType)//, $propertyValidation)
    {
        $this->assertInstanceOf($objectType, $this->serviceResult, "Must return instance of $objectType");
/*
        foreach($propertyValidation as $property => $expectedValue)
        {
            $this->assertObjectHasAttribute($property, $this->serviceResult, "Expected $objectType to have attribute $property");
            $actualValue = $this->serviceResult->$property;
            $this->assertEquals($actualValue, $expectedValue, "Expected property $property to have $expectedValue, instead found $actualValue");
        }
*/
        return $this;
    }

    public function expectsNativeType($nativeType)
    {
        $this->assertInternalType($nativeType, $this->serviceResult, "Must return instance of $nativeType");
        return $this;
    }

    public function expectsProperty($property)
    {
        $this->assertObjectHasAttribute($property, $this->serviceResult, "Expected " . get_class($this->serviceResult) . " to have attribute {$property}");
        $this->currentPropertyName = $property;
        $this->currentProperty = $this->serviceResult->$property;
        return $this;
    }

    public function toEqual($expectedValue)
    {
        $this->assertEquals($this->currentProperty, $expectedValue, "Expected property {$this->currentPropertyName} to have $expectedValue, instead found {$this->currentProperty}");
        return $this;
    }

    public function toHaveValue()
    {
        $this->assertNotEmpty($this->currentProperty, "Expected property {$this->currentPropertyName} to be filled (not empty)");
        return $this;
    }

    public function toBeInstanceOf($objectType)
    {
        $this->assertInstanceOf($objectType, $this->currentProperty, "{$this->currentPropertyName} must be an instance of $objectType");
        return $this;
    }

    public function toBeNativeType($nativeType)
    {
        $this->assertInternalType($nativeType, $this->currentProperty, "{$this->currentPropertyName} must be an instance of $nativeType");
        return $this;
    }

    public function examineSubTree($property, $arrayIndex = null)
    {
        $obj = $this->serviceResult;
        if ($property !== null) {
            $this->assertObjectHasAttribute($property, $obj, "Expected Object to have attribute $property");
            $obj = $obj->$property;
        }
        if ($arrayIndex !== null) {
            $this->assertInternalType('array', $obj, $property . " must be instance of array");
            $obj = $obj[$arrayIndex];
        }
        return new self($obj);
    }
}

