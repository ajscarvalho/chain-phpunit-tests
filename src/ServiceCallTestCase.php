<?php

namespace Sapo\TestAbstraction;

/**
 * way forward:
 * instead of creating a new object when calling a method on an object or by examineSubTree,
 * use a stack of objects under analysis and permit pop back
 * 
 * rename $serviceResult, getServiceCallResult(), examineSubTree(),
 * maybe rename call to invokeOnService, callObjectMethod to invokeOnObject
 */ 
class ServiceCallTestCase extends \PHPUnit_Framework_TestCase {

    private $serviceHandler;
    private $serviceResult;

    private $currentProperty;
    private $currentPropertyName;

    private $contextMessage = '';


    public function __construct($result = null) { $this->serviceResult = $result; }

    /** 
     * setUp is deprecated here, as it is a function that takes no parameters and is run before every test 
     * @deprecated use analyzeService instead
     */
     
    protected function setUp($serviceHandler) { $this->serviceHandler = $serviceHandler; }
    protected function analyzeService($serviceHandler) { $this->serviceHandler = $serviceHandler; return $this; }

    public function analyzeObject($object) { $this->serviceResult = $object; return $this; }
    public function getServiceCallResult() { return $this->serviceResult; }



#region expectations

    public function expectsInstanceOf($objectType) { return $this->expectsObject($objectType); }
    public function expectsObject($objectType)
    {
        $this->assertInstanceOf($objectType, $this->serviceResult, $this->contextMessage . "Must return instance of $objectType");
        return $this;
    }

    public function expectsNativeType($nativeType)
    {
        $this->assertInternalType($nativeType, $this->serviceResult, $this->contextMessage . "Must return instance of $nativeType");
        return $this;
    }

    public function expectsNullResult()
    {
        $this->assertNull($this->serviceResult, $this->contextMessage . "Must return null, actually returned: " . gettype($this->serviceResult));
        return $this;
    }

    public function expectsCardinality($expectedCardinality)
    {
        $actualCardinality = count($this->serviceResult);
        $this->assertEquals($actualCardinality, $expectedCardinality, $this->contextMessage . "Must return $expectedCardinality item(s) in result set, found $actualCardinality");
        return $this;
    }

    public function expectsProperty($property)
    {
        $this->assertObjectHasAttribute($property, $this->serviceResult, $this->contextMessage . "Expected " . get_class($this->serviceResult) . " to have attribute {$property}");
        $this->currentPropertyName = $property;
        $this->currentProperty = $this->serviceResult->$property;
        return $this;
    }

#end region expectations



#region value tests

    public function toReturn($expectedValue)
    {
        $this->assertEquals($this->serviceResult, $expectedValue, $this->contextMessage . "Expected result was $expectedValue, instead found {$this->serviceResult}");
        return $this;
    }

    public function toEqual($expectedValue)
    {
        $this->assertEquals($this->currentProperty, $expectedValue, $this->contextMessage . "Expected property {$this->currentPropertyName} to have $expectedValue, instead found {$this->currentProperty}");
        return $this;
    }

    public function toBeSimilarTo($expectedValue)
    {
        $this->assertNotFalse($this->currentProperty == $expectedValue, $this->contextMessage . "Expected property {$this->currentPropertyName} to have $expectedValue, instead found {$this->currentProperty}");
        return $this;
    }

    public function toBeGreaterThan($comparisionValue)
    {
        $this->assertGreaterThan($comparisionValue, $this->currentProperty, $this->contextMessage . "Expected property {$this->currentPropertyName} to be Greater than {$comparisionValue}, instead found {$this->currentProperty}");
        return $this;
    }

    public function toHaveValue()
    {
        $this->assertNotEmpty($this->currentProperty, $this->contextMessage . "Expected property {$this->currentPropertyName} to be filled (not empty)");
        return $this;
    }
    
    public function toEqualOneOf()
    {
        $args = func_get_args();
        $this->assertNotFalse(in_array($this->currentProperty, $args), $this->contextMessage . "Expected property {$this->currentPropertyName} to have one of the following values (" . implode(',', $args) . ") - instead found {$this->currentProperty}");
        return $this;
    }

    public function toBeInstanceOf($objectType)
    {
        $this->assertInstanceOf($objectType, $this->currentProperty, $this->contextMessage . "{$this->currentPropertyName} must be an instance of $objectType");
        return $this;
    }

    public function toBeNativeType($nativeType)
    {
        $this->assertInternalType($nativeType, $this->currentProperty, $this->contextMessage . "{$this->currentPropertyName} must be an instance of $nativeType");
        return $this;
    }

    /** 
     * tests that the result array is contained in the expectedArray, expectedArray may have more keys
     * values on each result array key must match values on the expected Array keys 
     * (it's not a set comparision)
     */
    public function toBeContainedIn($expectedArray)
    {
        $this->assertInternalType('array', $this->currentProperty, $this->contextMessage . "{$this->currentPropertyName} must be an instance of array");
        $this->assertInternalType('array', $expectedArray, $this->contextMessage . "Comparision value must be an instance of array");
        foreach($this->currentProperty as $key => $value)
            $this->assertEquals($expectedArray[$key], $this->currentProperty[$key], $this->contextMessage . "Array being compared is not included in the result (offending key {$key}) expected {$expectedArray[$key]}, got {$this->currentProperty[$key]}");

        return $this;        
    }

#region value tests



#region exception handling

    private $expectingException = false;
    private $expectingExceptionCode = null; // compare using $e->getCode()
    private $expectingExceptionType = null; // compare using get_class($e)
    private $expectingExceptionMesg = null; // compare using $e->getMessage()
    
    /**
     * calling methods may throw exceptions. 
     * These exceptions can be handled if a call to expectException is done before the call that actually throws the exception 
     * you can also pre-determined the expected exception code/type or message (TODO)
     */
    public function expectException($code = null, $type = null, $mesg = null)
    {
        $this->expectingException = true;
        if ($code) $this->expectingExceptionCode = $code;
        if ($type) $this->expectingExceptionType = $type;
        if ($mesg) $this->expectingExceptionMesg = $mesg;
        
        return $this;
    }

    public function resetExpectException()
    {
        $this->expectingException = false;
        $this->expectingExceptionCode = $this->expectingExceptionType = $this->expectingExceptionMesg = null;
    }

    public function handleException($e)
    {
        if ($this->expectingExceptionCode) 
            $this->assertEquals($this->expectingExceptionCode, $e->getCode(), 
                $this->contextMessage . "Expecting Exception code {$this->expectingExceptionCode}, got {$e->getCode()}");

        if ($this->expectingExceptionType) {
            $exceptionType = get_class($e);
            $this->assertEquals($this->expectingExceptionType, $exceptionType, 
                $this->contextMessage . "Expecting Exception type {$this->expectingExceptionType}, got {$exceptionType}");
        }

        if ($this->expectingExceptionMesg)
            $this->assertContains($this->expectingExceptionMesg, $e->getMessage(), 
                $this->contextMessage . "Expecting Exception Message Containing {$this->expectingExceptionMesg}, got {$e->getMessage()}");

        $this->resetExpectException();
    }

#end region exception handling



#region navigating result, calling methods on service / result objects

    public function examineSubTree($property, $arrayIndex = null, $contextMessage = null)
    {
        $obj = $this->serviceResult;
        if ($property !== null) {
            $this->assertObjectHasAttribute($property, $obj, $this->contextMessage . "Expected Object to have attribute $property");
            $obj = $obj->$property;
        }
        if ($arrayIndex !== null) {
            $propName = $property ? $property : ($this->currentPropertyName ? $this->currentPropertyName : 'result');
            $this->assertInternalType('array', $obj, $this->contextMessage . "{$propName} must be instance of array");
            $this->assertArrayHasKey($arrayIndex, $obj, $this->contextMessage . "{$propName} must have key {$arrayIndex}");
            $obj = $obj[$arrayIndex];
        }
        $result = new self($obj);
        if ($contextMessage) $result->proving($contextMessage);
        return $result;
    }

    /**
     * A convenient way to call methods on result objects is to call methods on this object as if the method was defined here
     * Another convenience is to call methods on the service handler as they were defined here
     */
    public function __call($method, $arguments) {
        $result = null;
        $methodNotFound = false;

        if ($this->serviceResult && method_exists($this->serviceResult, $method))
            $result =  $this->callObjectMethod($method, $arguments);
        else if (method_exists($this->serviceHandler, $method))
            $result = $this->call($method, $arguments);
        else 
            $methodNotFound = true;

        if ($methodNotFound) throw new \Exception($this->contextMessage . 'Invalid method call: ' . $method);

//      if (!$result)  // DO SOMETHING?
        if ($this->expectingException) throw new \Exception($this->contextMessage . 'Was expecting an Exception to be thrown');

        return $result;
    }

    public function call($function, $params)
    {
        try {
            $this->serviceResult = call_user_func_array(array($this->serviceHandler, $function), $params);

        } catch(\Exception $e) { 

            if (!$this->expectingException || $e->getCode() == 666) 
                return $this->fail($this->contextMessage . 'Call to operation ' . get_class($this->serviceHandler) . '::' . $function . 
                    " failed with exception: {$e->getCode()}\n{$e->getMessage()}\nTrace: " . 
                    (($e->getCode() < 100) ? $e->getTraceAsString() : '')); 

            return $this->handleException($e);
        }
        return $this;
    }


    public function callObjectMethod($method, $arguments)
    {
        $this->assertNotFalse(method_exists($this->serviceResult, $method), $this->contextMessage . 'O método ' . $method . ' deve existir no Objecto ' . get_class($this->serviceResult));
        try {
            $result = call_user_func_array(array($this->serviceResult, $method), $arguments);
        } 
        catch(\Exception $e)
        { 
            if (!$this->expectingException) throw $e;
            return $this->handleException($e);
        }
        return new self($result);
    }

#end navigating result, calling methods on service / result objects



#region messaging and debugging

    protected function proving($contextMessage) 
    {
        if (!$contextMessage) $this->contextMessage = '';
        else $this->contextMessage = ' * ' .$contextMessage . ' => '; 
        return $this; 
    }

    public function stopAndDebug() { echo "\n\nstopAndDebug called. Exiting...\nLast Result:\n" . print_r($this->serviceResult, 1); exit(1); }

#end region messaging and debugging

}

