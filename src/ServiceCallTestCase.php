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

    public function expectsInstanceOf($objectType) { return $this->expectsObject($objectType); }
    public function expectsObject($objectType)
    {
        $this->assertInstanceOf($objectType, $this->serviceResult, "Must return instance of $objectType");
        return $this;
    }

    public function expectsNativeType($nativeType)
    {
        $this->assertInternalType($nativeType, $this->serviceResult, "Must return instance of $nativeType");
        return $this;
    }

    public function toReturn($expectedValue)
    {
        $this->assertEquals($this->serviceResult, $expectedValue, "Expected result was $expectedValue, instead found {$this->serviceResult}");
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

  public function toBeSimilarTo($expectedValue)
    {
        $this->assertNotFalse($this->currentProperty == $expectedValue, "Expected property {$this->currentPropertyName} to have $expectedValue, instead found {$this->currentProperty}");
        return $this;
    }

    public function toBeGreaterThan($comparisionValue)
    {
        $this->assertGreaterThan($comparisionValue, $this->currentProperty, "Expected property {$this->currentPropertyName} to be Greater than {$comparisionValue}, instead found {$this->currentProperty}");
        return $this;
    }

    public function toHaveValue()
    {
        $this->assertNotEmpty($this->currentProperty, "Expected property {$this->currentPropertyName} to be filled (not empty)");
        return $this;
    }
    
    public function toEqualOneOf()
    {
        $args = func_get_args();
        $this->assertNotFalse(in_array($this->currentProperty, $args), "Expected property {$this->currentPropertyName} to have one of the following values (" . implode(',', $args) . ") - instead found {$this->currentProperty}");
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
            $propName = $property ? $property : ($this->currentPropertyName ? $this->currentPropertyName : 'result');
            $this->assertInternalType('array', $obj, "{$propName} must be instance of array");
            $this->assertArrayHasKey($arrayIndex, $obj, "{$propName} must have key {$arrayIndex}");
            $obj = $obj[$arrayIndex];
        }
        return new self($obj);
    }

    public function callObjectMethod($method, $params)
    {
        $this->assertNotFalse(method_exists($this->serviceResult, $method), 'O método ' . $method . ' deve existir no Objecto ' . get_class($this->serviceResult));
        $result = call_user_func_array(array($this->serviceResult, $method), $params);
        return new self($result);
    }
}

