<?php

namespace Sapo\TestAbstraction;

use GuzzleHttp\Exception\RequestException;
use Sapo\GuzzleWrapper\GuzzleWrapper;

class ServiceCallTestCase extends \PHPUnit_Framework_TestCase {

    private $serviceHandler;
    private $serviceResult;

    public function __construct($result) { $this->serviceResult = $result; }

    public function setUp($serviceHandler) { $this->serviceHandler = $serviceHandler; }

    public function call($function, $params)
    {
        try {
            $this->serviceResult = call_user_func_array(array($this->serviceHandler, $function), $params);
            return $this;
        } catch(RequestException $e) { 
            $this->fail('Call to operation ' . get_class($this->serviceHandler) . '::' . $function . 
                " failed with exception: \n". GuzzleWrapper::parseExceptionMessage($e)); 
        }
    }

    public function expectsObject($objectType, $propertyValidation)
    {
        $this->assertInstanceOf($objectType, $this->serviceResult, "Must return instance of $object");
        foreach($propertyValidation as $property => $expectedValue)
        {
            $actualValue = $this->serviceResult->$property;
            $this->assertEquals($actualValue, $expectedValue, "Expected property $property to have $expectedValue, instead found $actualValue");
        }
    }

    public function expectsNativeType($nativeType)
    {
        $this->assertInternalType($nativeType, $this->serviceResult, "Must return instance of $nativeType");
    }

    public function sub($property, $arrayIndex = null)
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

