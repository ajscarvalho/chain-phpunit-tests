<?php

namespace Sapo\TestAbstraction;

use ReflectionObject;
use Exception;
use ReflectionException;
use PHPUnit_Framework_TestCase;
use Sapo\TestAbstraction\Validator\ConditionalValidator;
use Sapo\TestAbstraction\Validator\DateValidator;
use Sapo\TestAbstraction\Validator\NumericalValidator;

use DateTime;

/**
 */ 
class ResultChecker extends PHPUnit_Framework_TestCase {

    const DEFAULT_NAME = 'Result'; // default name for unnamed properties/objects

#region properties

    private $api; // api under analysis
    private $analyzeStack = []; // stack of objects under analysis --- @TODO the element in the stack, could be a tuple (object, objectName, contextMessage)

    private $variableValue; // variable or object property under analysis
    private $variableName; // the name of the variable under analysis (used on error messages)

    private $contextMessage = ''; // gives context on the errors
    //private $contextStack = []; // stacks context messages // @TODO remove?

    // handle searching objects on lists
    private $searchBy = null;
    private $searching = null;

#end region properties


    public function testMeAsPhpUnitTriesToTestThisClass(){ $this->assertFalse(false); }

#region setup of things to be analyzed

    /**
     * this region provides functions that setup things for analysis, or fetches things under analysis
     */

    /**
     * set an API/Object for analysis
     * @param stdClass $api Any Object that provides an interface that we can call to obtain a result
     * @return self (chainable)
     * TESTED in ApiCallTestCase
     */
    protected function checkAPI($api) 
    {
        $this->api = $api; 
        return $this;
    }
    
    /**
     * setup an Object for analysis 
     * clears the analyze stack and push the object on it (the analysis stack)
     * 
     * @param stdClass $object Any Object
     * @return self (chainable)
     *
     * @TODO add example
     * UNTESTED
     */
    protected function checkObject($object, $objectName = null, $context = null) 
    { 
        $this->analyzeStack = [];
        return $this->addObjectToStack($object, $objectName = null, $context = null);
    }

    /**
     * setup an Object for analysis 
     * clears the analyze stack and push the object on it (the analysis stack)
     * should be private
     * 
     * @param stdClass $object Any Object
     * @return self (chainable)
     * TESTED by $this->checkThatProperty, checkThatIndex, callAPI, callObjectMethod in ApiCallTestCase 
     *      (function is private)
     */
    private function addObjectToStack($object, $objectName = null, $context = null)
    {
        if (!$objectName) $objectName = self::DEFAULT_NAME;

        // updates contextMessage to/from function/localVar
        if ($context) $this->contextMessage = $context;
        else $context = $this->contextMessage;

        $this->analyzeStack[] = ['obj' => $object, 'name' => $objectName, 'ctx' => $context]; 
        return $this; 
    }

    /**
     * Terminates analysis on the object, return to the previous object
     * @return self (chainable)
     * TESTED in testStackingAnalysis
     */
    protected function endObjectAnalysis() 
    { 
        array_pop($this->analyzeStack);
        $this->contextMessage = $this->getContextMessage();
        return $this;
    }

    /**
     * get the current object under analysis on the stack
     * @return the current object under analysis (top of the stack)
     * TESTED everywhere, because it's used internally
     */
    protected function underAnalysis() 
    {
        if (empty($this->analyzeStack)) return false;
        return $this->analyzeStack[count($this->analyzeStack) -1]['obj']; 
    }

    /**
     * get the name of the object under analysis on the stack
     * method should be private but for testing purposes it must be protected
     * @return string name
     * TESTED by checkThatIndex, 
     */
    protected function getPropertyNameUnderAnalysis() 
    {
        if (empty($this->analyzeStack)) return false;
        return $this->analyzeStack[count($this->analyzeStack) -1]['name']; 
    }

    /**
     * getters to enable cross testing variable dependencies
     * @return mixed variable value (simple native type)
     * UNTESTED!
     */
    protected function getVariableValue() { return $this->variableValue; }
    protected function getVariableName() { return $this->variableName; }

    /**
     * get the context message for the test being performed related to the object under analysis on the stack
     * method should be private but for testing purposes it must be protected
     * @return string message
     * TESTED in testStackingAnalysis
     */
    protected function getContextMessage() 
    {
        if (empty($this->analyzeStack)) return false;
        return $this->analyzeStack[count($this->analyzeStack) -1]['ctx'];
    }

    /**
     * analyze a variable, setting it's name (so we can reuse the same reporting)
     * @param string $name Variable Name (For Error Reporting)
     * @param mixed $value Variable Value
     * @return self (chainable)
     * UNTESTED
     */
    protected function checkVariable($name, $value) { 
        $this->variableName = $name; 
        $this->variableValue = $value;
        return $this; 
    }

    /**
     * @TODO checkStaticBehavior
     * need to change some methods to allow calling methods on this class statically
     * UNTESTED
     */
    protected function checkStaticBehavior($className)
    {
        throw new Exception("Not implemented: checkStaticBehavior");
    }

    /**
     * @TODO checkFunction
     * need to change some methods to allow calling independent functions
     * UNTESTED
     */
    protected function checkFunction($functionName)
    {
        throw new Exception("Not implemented: checkFunction");
    }

#end region setup of things to be analyzed



#region return value assertions, examined properties

    /**
     * This region features assertions run on the top of the analysis stack
     * 
     * $this->someMethodName()->returnsInstanceOf(...)
     */


    /**
     * Checks if the object under analysis is an instance of the specified objectType
     * @param string $objectType
     * @return self (chainable)
     * <code> $this->someMethodName()->returnsInstanceOf('Car'); </code>
     * TESTED everywhere, e.g. testObjectResult
     */
    protected function returnsInstanceOf($objectType) {
        $this->assertInstanceOf($objectType, $this->underAnalysis(), $this->contextMessage . "Must return instance of $objectType");
        return $this;
    }

    /**
     * Checks if the object under analysis is an instance of a native type (int, float, string, array)
     * if it matches, also runs analyzeVariable on the value
     * @param string $nativeType
     * @return self (chainable)
     * <code> $this->someMethodName()->returnsNativeType('int'); </code>
     * TESTED in testIntegerResult
     */
    protected function returnsNativeType($nativeType)
    {
        $value = $this->underAnalysis();
        $this->assertInternalType($nativeType, $value, $this->contextMessage . "Must return $nativeType");
        // as it's returning a Native Type, lets also fill variable* properties
        return $this->checkVariable(self::DEFAULT_NAME, $value);
    }
    /** TESTED in testAcessorGet */
    protected function returnsString() { return $this->returnsNativeType('string'); }
    /** TESTED in testIntegerResult */
    protected function returnsInteger() { return $this->returnsNativeType('int'); }
    /** TESTED in testCheckThatIndex */
    protected function returnsArray() { return $this->returnsNativeType('array'); }
    /** TESTED in testBooleanResult */
    protected function returnsBoolean() { return $this->returnsNativeType('boolean'); }
    /** TESTED in testDouble - this is not supported in phpunit */
    protected function returnsDouble() { return $this->returnsFloatingPointNumber(); }
    protected function returnsFloat() { return $this->returnsFloatingPointNumber(); }
    protected function returnsFloatingPointNumber()
    {
        $value = $this->underAnalysis();
        $this->assertEquals('double', gettype($value)); // it's double for this version of php, will it be the same in any version?
        return $this->checkVariable(self::DEFAULT_NAME, $value);
    }

    /**
     * Checks that the object under analysis is Null (Function/method return value)
     *
     * @return self (chainable)
     * <code> $this->someMethodName()->returnsNull(); </code>
     * TESTED in testReturnsNull
     */
    protected function returnsNull()
    {
        $value = $this->underAnalysis();
        $this->assertNull($value, $this->contextMessage . "Must return null, actually returned: " . gettype($value));
        return $this->checkVariable(self::DEFAULT_NAME, $value); // yep fills with Null so we can test it!
    }

    /**
     * Checks that the object under analysis is an array of a determined size (Function/method return value)
     * @param int $expectedCardinality
     * @return self (chainable)
     * <code> $this->giveMeAPage($offset = 0, $size = 3)->returnsArrayOfSize(3); // if there are suficient elements in the list </code>
     * TESTED by testCheckThatIndex
     */
     protected function returnsArrayOfSize($expectedCardinality)
     {
        $this->returnsArray(); // check that the result is an Array
        $actualCardinality = count($this->underAnalysis());
        $this->assertCount($expectedCardinality, $this->underAnalysis(), $this->contextMessage . "Must return $expectedCardinality item(s) in result set, found $actualCardinality");
        return $this;
    }

    /**
     * checks if a property is public or accessible by magic __get
     * TESTED (this is private) by  $this->withExistingProperty, $this->withVirtualProperty
     */
    private function assertPropertyIsPubliclyAvailable($property)
    {
        $object = $this->underAnalysis();
        $objReflect = new ReflectionObject($object);

        try {
            $propReflect = $objReflect->getProperty($property);
            if ($propReflect->isPublic()) return $object->$property;
        } catch(ReflectionException $e) { /* echo $e->getMessage(); var_dump($object); */}

        try {
            $methodReflect = $objReflect->getMethod('__get');
            return $object->$property;
        } catch(ReflectionException $e) {}

        $this->fail($this->contextMessage . "property $property is neither public nor accessible by __get");
    }

    /**
     * Checks that the object under analysis has a determined property (Function/method return value)
     * if it matches, also runs analyzeVariable on the value
     * @param string $property
     * @return self (chainable)
     * <code> $this->getCar()->withExistingProperty('engine')-> ...do something with engine...; </code>
     * TESTED by testWithExistingPublicProperty
     */
    protected function withExistingProperty($property)
    {
        $object = $this->underAnalysis();
        $this->assertObjectHasAttribute($property, $object, $this->contextMessage . "Expected " . get_class($object) . " to have attribute {$property}");

        $this->assertPropertyIsPubliclyAvailable($property);

        // this function will chain into an analysis of the property, so setup the property for analysis
        return $this->checkVariable($property, $object->$property);
    }

    /**
     * Checks that the object under analysis can access some property by use of a magic __get method
     * if it matches, also runs analyzeVariable on the value
     * @param string $property
     * @return self (chainable)
     * <code> $this->getCar()->withVirtualProperty('engine')-> ...do something with engine...; </code>
     * TESTED by testWithVirtualPropertyByMagicGet
     */
    protected function withVirtualProperty($property)
    {
        $object = $this->underAnalysis();

        $this->assertPropertyIsPubliclyAvailable($property);

        // this function will chain into an analysis of the property, so setup the property for analysis
        return $this->checkVariable($property, $object->$property);
    }

    /**
     * matches current value against a virtual property (fetch by magic __get)
     * @TODO documentation
     * UNTESTED
     */
    protected function matchingVirtualProperty($property)
    {
        $object = $this->underAnalysis();

        $this->assertPropertyIsPubliclyAvailable($property);

        $this->assertEquals($this->variableValue, $object->$property);

        return $this;
    }

#end region return value assertions, examined properties



#region Validators instantiation

    /**
     * Takes the value being analyzed and checks the length of either string or array
     * @TODO documentation
     * UNTESTED
     */
    protected function ofLength()
    {
        $variable = $this->variableValue;
        if (gettype($variable) == 'string') return NumericalValidator::factory($this, $this->variableName . ' string length',     strlen($variable), $this->contextMessage);
        if (gettype($variable) == 'array' ) return NumericalValidator::factory($this, $this->variableName . ' array cardinality', count($variable),  $this->contextMessage);

        throw new Exception($this->variableName . ' must be either string or array to run ofLength function');
    }

    protected function beingDate()
    {
        $this->beingInstanceOf('DateTime');
        return DateValidator::factory($this, $this->variableName, $this->variableValue, $this->contextMessage);
    }

    protected function ofDate()
    {
        return DateValidator::factory($this, $this->variableName, new DateTime($this->variableValue), $this->contextMessage);
    }

    protected function when($property)
    {
        $object = $this->underAnalysis();
        return ConditionalValidator::factory($this, $property, $object->$property, $this->contextMessage);
    }

#end region Validators instantiation


#region value tests

    /**
     * Assertions run on variable values
     * <code> $this->getFerrari()->returnsInstanceOf('Car')->withExistingProperty('engine')->thatEquals('V12'); </code>
     * 
     * @TODO implement in __call some magic that converts shouldXPTO to thatXPTO
     */


    /**
     * Checks that the variable under analysis matches type and value with the argument (simple types only)
     * @param mixed $expectedValue
     * @return self (chainable)
     * <code> $this->getFerrari()->returnsInstanceOf('Car')->withExistingProperty('engine')->thatEquals('V12'); </code>
     * TESTED by testThatEqualsFails
     */
    protected function thatEquals($expectedValue)
    {
        $expectedType = gettype($expectedValue);
        $actualType = gettype($this->variableValue);
        $this->assertInternalType($expectedType, $this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to be of type {$expectedType}, instead found $actualType");
        $this->assertEquals($expectedValue, $this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to have $expectedValue, instead found {$this->variableValue}");
        return $this;
    }

    /**
     * Checks that the variable under analysis matches the value of the argument (simple types only)
     * @param mixed $expectedValue
     * @return self (chainable)
     * <code> $this->getFerrari()->returnsInstanceOf('Car')->withExistingProperty('numberOfDoors')->thatIsSimilarTo('2'); // actually should return 2 </code>
     * TESTED by testThatIsSimilarToFails
     */
    protected function thatIsSimilarTo($expectedValue)
    {
        $this->assertEquals($expectedValue, $this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to have $expectedValue, instead found {$this->variableValue}");
        return $this;
    }

    /**
     * Checks that the variable under analysis has a value over a determined threshold
     * @param mixed $comparisionValue the value we should top
     * @return self (chainable)
     * <code> $this->getFerrari()->returnsInstanceOf('Car')->withExistingProperty('topSpeedKmH')->thatIsGreaterThan('300'); // e.g. 363 (Km/h) </code>
     * TESTED by testThatIsGreaterThanFails
     */
    protected function thatIsGreaterThan($comparisionValue)
    {
        $this->assertGreaterThan($comparisionValue, $this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to be Greater than {$comparisionValue}, instead found {$this->variableValue}");
        return $this;
    }

    /**
     * Checks that the variable under analysis has some non-null value
     * @return self (chainable)
     * <code> $this->getFerrari()->returnsInstanceOf('Car')->withExistingProperty('color')->thatHasSomeValue(); // e.g. "red" </code>
     * TESTED by testThatHasSomeValueFails
     */
    protected function thatHasSomeValue()
    {
        switch(gettype($this->variableValue))
        {
            case 'string': 
            case 'array':
                $this->assertNotEmpty($this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to be filled (not empty)");
                return $this;
            case 'NULL': 
                $this->fail($this->contextMessage . "Expected property {$this->variableName} to be filled (not null)");
        }
        $this->assertFalse(false, $this->contextMessage . "variable is filled with some Value"); // just to mark test passing
        return $this;
    }

    /**
     * Checks that the variable under analysis has some null value
     * @return self (chainable)
     * <code> $this->getFerrari()->returnsInstanceOf('Car')->withExistingProperty('motorOil')->thatIsEmpty(); // e.g. "red" </code>
     * UNTESTED
     */
    protected function returnsEmpty()    
    {
        switch(gettype($this->variableValue))
        {
            case 'string': 
            case 'array':
                $this->assertEmpty($this->variableValue, $this->contextMessage . "Expected property {$this->variableName} to be empty");
                return $this;
            case 'NULL': 
                $this->assertFalse(false); // marks as passed
                return $this;
        }
        $this->fail($this->contextMessage . "Expected property {$this->variableName} to be empty");
    }

    /**
     * Match a value against a list of possible values
     * @param mixed $values,... variable value is checked against the argument list to see if it matches one of the arguments
     * @return self (chainable)
     * <code> $this->getFerrari(2010)->returnsInstanceOf('Car')->withExistingProperty('model')->beingOneOf('599 GTO', 'SA APERTA', '458 Challenge', 'Ferrari California 30'); </code>
     * TESTED by testbeingOneOfFails
     */
    protected function beingOneOf()
    {
        $args = func_get_args();
        //$this->assertFalse(!in_array($this->variableValue, $args), $this->contextMessage . "Expected property {$this->variableName} to have one of the following values (" . implode(',', $args) . ") - instead found {$this->variableValue}");
        $this->assertContains($this->variableValue, $args, $this->contextMessage . "Expected property {$this->variableName} to have one of the following values (" . implode(',', $args) . ") - instead found {$this->variableValue}");
        return $this;
    }

    /**
     * tests if a variable/property is an instance of an object
     * @param string $objectType
     * @return self (chainable)
     * <code> $this->getRandomPersonWithPet('cat')->returnsInstanceOf('Person')->withExistingProperty('pet')->beingInstanceOf("Cat"); </code>
     * TESTED by testBeingInstanceOf
     */
    protected function beingInstanceOf($objectType)
    {
        $this->assertInstanceOf($objectType, $this->variableValue, $this->contextMessage . "{$this->variableName} must be an instance of $objectType");
        return $this;
    }

    /**
     * tests if a variable/property is of the internal type specified by the argument
     * @param string $nativeType
     * @return self (chainable)
     * <code> $this->getRandomPerson()->returnsInstanceOf('Person')->withExistingProperty('age')->beingOfNativeType("int"); </code>
     * TESTED by testBeingInstanceOf
     */
    protected function beingOfNativeType($nativeType)
    {
        $this->assertInternalType($nativeType, $this->variableValue, $this->contextMessage . "{$this->variableName} must be an instance of $nativeType");
        return $this;
    }

    protected function beingABoolean()   { return $this->beingBoolean(); }
    protected function beingBoolean()    { return $this->beingOfNativeType('boolean'); }
    protected function beingAString()   { return $this->beingString(); }
    protected function beingString()    { return $this->beingOfNativeType('string'); }
    protected function beingAnInteger() { return $this->beingInteger(); }
    protected function beingInteger()   { return $this->beingOfNativeType('int'); }
    protected function beingAnArray()   { return $this->beingArray(); }
    protected function beingArray()     { return $this->beingOfNativeType('array'); }
    protected function beingADouble()   { return $this->beingFloatingPointNumber(); }
    protected function beingDouble()    { return $this->beingFloatingPointNumber(); }
    protected function beingAFloat()    { return $this->beingFloatingPointNumber(); }
    protected function beingFloat()     { return $this->beingFloatingPointNumber(); }

    protected function beingFloatingPointNumber()
    {
        $value = $this->underAnalysis();
        $this->assertEquals('double', gettype($this->variableValue), "{$this->variableValue} must be a double, found ". gettype($this->variableValue)); // it's double for this version of php, will it be the same in any version?
        return $this;
    }

    protected function beingNumeric()
    {
        $this->beingOfNativeType('string');
        $this->assertRegExp('/^\d+$/', $this->variableValue, $this->contextMessage . "{$this->variableName} must be numeric, instead found {$this->variableValue}");
        return $this;
    }

    /** 
     * Tests that the result array is contained in the expectedArray.
     * the values on each result array key must match values on the expected Array keys (on the same key)
     * Note: The expectedArray may have more keys than the result (it's not a set comparision)
     * <code>
     *      $this->getZoo()->withExistingProperty('felines')->thatIsSubHashTableOf(["Cat", "Tiger"]); // the zoo may only have Cats or Tigers in the felines area
     *      $this->getZoo()->withExistingProperty('felines')->beingOfNativeType('array')->thatIsSubHashTableOf(["Cat", "Tiger"]);
     * </code>
     * 
     * @param array $expectedArray the list of values that can possibly be returned in the return array
     * @return self (chainable)
     * TESTED by testThatIsSubHashTableOfFails
     */
    protected function thatIsSubHashTableOf($expectedArray)
    {
        $this->assertInternalType('array', $this->variableValue, $this->contextMessage . "{$this->variableName} must be an instance of array");
        $this->assertInternalType('array', $expectedArray, $this->contextMessage . "Comparision value must be an instance of array");
        foreach($this->variableValue as $key => $value)
        {
            $this->assertFalse(!array_key_exists($key, $expectedArray), $this->contextMessage . " key $key should exist on expectedArray");
            $expectedValue = @$expectedArray[$key];
            $this->assertEquals($expectedValue, $this->variableValue[$key], $this->contextMessage . "Array being compared is not included in the result (offending key {$key}) expected {$expectedValue}, got {$this->variableValue[$key]}");
        }
        return $this;        
    }

    /** 
     * Tests if the examined varaible is a subset of the expectedSet
     * @TODO
     */
    protected function thatIsASubsetOf($expectedSet)
    {
        throw new Exception('Operation not Implemented: thatIsASubsetOf');
    }

    /** 
     * Tests that the resulting boolean is true.
     * <code>
     *      $this->getZoo()->getLion()->isFeline()->returnsBoolean()->thatIsTrue()
     * </code>
     * 
     * @return self (chainable)
     * UNTESTED
     */
    protected function thatIsTrue()
    { 
        $this->assertNotFalse($this->variableValue, $this->contextMessage . "{$this->variableName} must be true");
        return $this; 
    }

    /** 
     * Tests that the resulting boolean is false.
     * <code>
     *      $this->getZoo()->getMonkey()->isFeline()->returnsBoolean()->thatIsFalse()
     * </code>
     * 
     * @return self (chainable)
     * UNTESTED
     */
    protected function thatIsFalse()
    {
        $this->assertFalse($this->variableValue, $this->contextMessage . "{$this->variableName} must be false");
        return $this; 
    }
    
    /**
     * Just acts as documentation
     * @return self (chainable)
     */
    protected function thatIsOptional() { return $this; }
    
    /** 
     * tests that the result string starts with the expectedPrefix
     * @param string $expectedPrefix the variable must start with this string
     * @return self (chainable)
     * <code> $this->openGift()->returnsInstanceOf('Car')->withExistingProperty('name')->thatStartsWith('Ferra'); // Yes, it's a Ferrari </code>
     * TESTED by testThatStartsWithFails
     */
    protected function thatStartsWith($expectedPrefix)
    {
        $this->assertInternalType('string', $this->variableValue, $this->contextMessage . "{$this->variableName} must be an instance of string");
        $this->assertInternalType('string', $expectedPrefix, $this->contextMessage . "Comparision value must be an instance of string");
        $this->assertStringStartsWith($expectedPrefix, $this->variableValue, $this->contextMessage . "{$this->variableName} doesn't start with {$expectedPrefix}, instead starts with " . substr($this->variableValue, 0, strlen($expectedPrefix)) );

        return $this;        
    }

#region value tests



#region search

    /**
     * Setup search for a particular object in an array returning the first instance that matches
     * to do the actual search you must call the "matching" function
     * 
     * @param $propertyName the property to analyze
     * @return self (chainable)
     * <code> 
     *      $this->getFriendList()->searchItemWith('name')->matching('Peter')
     *      //Or
     *      $this->getFriendList()->searchItemByName('Peter')
     * </code>
     */
    protected function searchItemWith($propertyName)
    {
        $this->searchBy = $propertyName;
        $this->searching = true;
        return $this;
    }

    /** 
     * the negative preposition of searchItemWith -> setup a search that must fail
     * 
     * @see searchItemWith
     * @param $propertyName the property to analyze
     * @return self (chainable)
     */
    protected function failToSearchItemWith($propertyName)
    {
        $this->searchBy = $propertyName;
        $this->searching = false;
        return $this;
    }

   /**
     * run search for a particular object in an array returning the first instance that matches
     * to do the actual search you must first call the "searchItemWith" function
     * 
     * @param $propertyValue the value to match agains the setup property
     * @return self (chainable)
     * <code> 
     *      $this->getFriendList()->searchItemWith('name')->matching('Peter')
     *      //Or
     *      $this->getFriendList()->searchItemByName('Peter')
     * </code>
     */
    protected function matching($propertyValue)
    {
        $this->assertNotFalse($this->searchBy && is_bool($this->searching), $this->contextMessage . "must setup PropertyName ({$this->searchBy}) before executing search");
        $element = $this->executePropertySearch($this->underAnalysis(), $this->searchBy, $propertyValue);

        if ($this->searching) {
            $assert = 'assertNotNull';
            $message = $this->contextMessage . "didn't find any object whoose {$this->searchBy} matches {$propertyValue}";
        } else {
            $assert = 'assertNull';
            $message = $this->contextMessage . "found an object whoose {$this->searchBy} matches {$propertyValue}";
        }

        $this->$assert($element, $message);

        if ($element)
            $this->addObjectToStack($element, "element with {$this->searchBy} = {$propertyValue}", "digging into element with {$this->searchBy} = {$propertyValue}");
        $this->searchBy = null;
        $this->searching = null;
        return $this;
    }

    /**
     * (internal) actually executes the search of an item in a list (no references to this)
     * 
     * @param array $list
     * @param string $propName
     * @param mixed $propValue
     * @return mixed item matched or null if none matches
     */
    private static function executePropertySearch(array $list, $propName, $propValue)
    {
        foreach($list as $item)
        {
            if ($item->$propName === $propValue) return $item;
        }
        return null;
    }

    /**
     * (internal) resolves __call functions whoose method starts with searchItemBy
     * 
     * searchItemByFoo('bar') is the same as calling searchItemWith('foo')->matching('bar')
     */
    private function callSearchItemBy($method, $paramValue)
    {
        $paramName = preg_replace_callback('/^searchItemBy(\w)/', function($match) { return strtolower($match[1]); }, $method);
        return $this->searchItemWith($paramName)->matching($paramValue);
    }

    /**
     * (internal) resolves __call functions whoose method starts with searchItemBy
     * 
     * searchItemByFoo('bar') is the same as calling searchItemWith('foo')->matching('bar')
     */
    private function callFailToSearchItemBy($method, $paramValue)
    {
        $paramName = preg_replace_callback('/^failToSearchItemBy(\w)/', function($match) { return strtolower($match[1]); }, $method);
        return $this->failToSearchItemWith($paramName)->matching($paramValue);
    }

#end region search



#region exception handling

    /**
     * calling methods may throw exceptions. 
     * These exceptions can be handled if a call to expectException is done before the call that actually throws the exception 
     * you can also pre-determine the expected exception code/type or message
     * TESTED by ...
     */

    private $expectingException = false; // state variable that indicates if we're expecting an exception to be thrown
    private $expectingExceptionCode = null; // if we're expecting an exception then it must match this code, calling $e->getCode()
    private $expectingExceptionType = null; // if we're expecting an exception then it must match this type, calling get_class($e)
    private $expectingExceptionMesg = null; // if we're expecting an exception then it must match this Message, by matching it to $e->getMessage()
    
    /**
     * declare that the next method/function call either on an API or object will result in an exception being thrown
     * Note: If no exception is thrown, that is flagged as an error (it should throw)
     * 
     * @param int $code The Exception Code
     * @param string $type The Exception Class Name
     * @param string $mesg A significant part of the Exception Message (You are not obliged to reproduce the whole message)
     * @return self (chainable)
     * @see __call, call, callObjectMethod below
     * <code> $this->expectException(404, 'NotFoundException', 'Unknown Ferrari')->getFerrari('Polo'); // ref to VW Polo </code> 
     */
    protected function expectException($code = null, $type = null, $mesg = null)
    {
        $this->expectingException = true;
        if ($code) $this->expectingExceptionCode = $code;
        if ($type) $this->expectingExceptionType = $type;
        if ($mesg) $this->expectingExceptionMesg = $mesg;
        
        return $this;
    }

    /**
     * internal function to reset state variables 
     */
    private function resetExpectException()
    {
        $this->expectingException = false;
        $this->expectingExceptionCode = $this->expectingExceptionType = $this->expectingExceptionMesg = null;
    }

    /**
     * internal function to handle thrown exceptions, trying to match code, class and message
     * resets Exception expectation at the end
     * @param Exception $e
     * @return $this allowing exception throwing methods to be chainable
     */
    private function handleException($e)
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

        return $this;
    }

#end region exception handling



#region navigating result, calling methods on service / result objects

    /**
     * abstract calling methods on API's or objects on the top of the stack by allowing that 
     * $this->xpto() may be "redirected" to $this->api->xpto() or $this->underAnalysis()->xpto()
     * 
     * allow navigation complex return objects by pushing a subObject on the top of the analysisStack
     */

    /**
     * Analyze a property from the object on the top of the stack by adding it to the stack
     * Also checks if the property exists on the object
     * @param string $property the object property to analyze
     * @param string $contextMessage a context to add to error reports
     * @return self (chainable)
     * <code> $this->getPerson('Miguel')->checkThatProperty('father')->returnsInstanceOf('Person'); </code>
     */
    protected function checkThatProperty($property, $contextMessage = null)
    {
        $obj = $this->underAnalysis();
        if ($property === null) $this->fail('checkThatProperty requires a property name');

        $this->assertObjectHasAttribute($property, $obj, $this->contextMessage . "Expected Object to have attribute $property");
        $obj = $obj->$property;

        return $this->addObjectToStack($obj, $property, $contextMessage);
    }

    /**
     * Analyze an index from an object on the top of the stack by adding it to the stack
     * Also checks if the object is an array and that the index exists on the array
     * @param int|string $arrayIndex the index to analyze
     * @param string $variableName the name of the property for the index to add to error reports (Could be derived if stored alongside in the Stack)
     * @param string $contextMessage a context to add to error reports
     * @return self (chainable)
     * <code> 
     *      $this->getPerson('Miguel')->getFriendsSortedByName() // see below __call and callObjectMethod
     *          ->checkThatIndex(0)
     *              ->returnsInstanceOf('Person')->withExistingProperty('name')->thatEquals('Adelino'); // try to make "has" an alias of "returns"
     * </code>
     */
    protected function checkThatIndex($arrayIndex, $variableName = null, $contextMessage = null)
    {
        $obj = $this->underAnalysis();
        $priorVariableName = $this->getPropertyNameUnderAnalysis();
        $propName = $priorVariableName && $priorVariableName != self::DEFAULT_NAME ? $priorVariableName : ($variableName ? $variableName : self::DEFAULT_NAME);

        if ($arrayIndex === null) $this->fail('checkThatIndex requires an index');
        
        $this->assertInternalType('array', $obj, $this->contextMessage . "{$propName} must be instance of array");
        $this->assertArrayHasKey($arrayIndex, $obj, $this->contextMessage . "{$propName} must have key {$arrayIndex}");
        $obj = $obj[$arrayIndex];

        return $this->addObjectToStack($obj, $propName, $contextMessage);
    }



    /** 
     * Convenient alias for internal functions: has* => returns*, ofNativeType => beingOfNativeType, greaterThan => thatIsGreaterThan, etc...
     */
    private $internalAlias = array(
        'has' => 'returns', // try hasNativeType => returnsNativeType
        'thatIs' => 'returns', // try thatIsString => returnsString
        'returns' => 'thatHas', // hasSomeValue => returnsSomeValue => thatHasSomeValue
        'isOneOf' => 'beingOneOf',
        'is' => 'thatIs', // isSimilarTo => thatIsSimilarTo
        'shouldBe' => 'thatIs', // shouldBeSimilarTo => thatIsSimilarTo
        'shouldHave' => 'thatHas',
        'shouldBeEqualTo' => 'thatEquals',
        'shouldStartWith' => 'thatStartsWith',
        'ofNativeType' => 'beingOfNativeType',

        // more synonym words
        'contains' => 'returns', // similar to has, try containsNativeType => returnsNativeType
        'lookInto' => 'with', // lookIntoExistingProperty => withExistingProperty

        //'' => 'with', // withExistingProperty
//    , 'greaterThan' => 'thatIsGreaterThan'
    );

    /**
     * A convenient way to call methods on result objects is to call methods on this object as if the method was defined here
     * Another convenience is to call methods on the service handler as they were defined here
     */
    public function __call($method, $arguments) 
    {
        $result = null;
        $methodNotFound = false;

        if ($this->underAnalysis() && method_exists($this->underAnalysis(), $method))
        {
            $result =  $this->callObjectMethod($method, $arguments);
        }
        else if (method_exists($this->api, $method))
        {
            $result = $this->callAPI($method, $arguments);
        }
        else 
        {
            $methodNotFound = true;

            if (0 === strpos($method, 'searchItemBy')) // starts with searchItemBy
                return $this->callSearchItemBy($method, $arguments[0]);
                
            if (0 === strpos($method, 'failToSearchItemBy')) // TODO alias shouldntExistItemBy?
                return $this->callFailToSearchItemBy($method, $arguments[0]);
    
            // remove "and" from method start (and lowercases the next letter)
            $method = preg_replace_callback('/^(?:and)(\w)/', function($match) { return strtolower($match[1]); }, $method);
            // remove Also in the middle
            $method = preg_replace_callback('/(\w)Also(\w)/', function($match) { return $match[1] . $match[2]; }, $method); 

            if (method_exists($this, $method)) 
                return call_user_func_array(array($this, $method), $arguments); // internal method gets called immediatelly

            // try method aliases
            foreach($this->internalAlias as $match => $replacement)
                if (0 === strpos($method, $match)) // just match at the beginning of string
                {
                    $method = str_replace($match, $replacement, $method);
                    if (method_exists($this, $method)) 
                        return call_user_func_array(array($this, $method), $arguments); // internal method gets called immediatelly
                }
        }

        if ($methodNotFound) throw new Exception($this->contextMessage . 'Invalid method call: ' . $method);

//      if (!$result)  // DO SOMETHING?
        if ($this->expectingException) throw new Exception($this->contextMessage . 'Was expecting an Exception to be thrown');

        $resultValue = $this->underAnalysis(); 

        if (in_array(gettype($resultValue), array('string', 'int', 'integer', 'array', 'double')))
        {
            $this->checkVariable('resultOf_' . $method, $resultValue);
        }
        return $result;
    }

    /** 
     * internal function to handle calling functions on the api
     * adds result to the analysis stack if successful.
     * Handles exception if expecting it, fails otherwise
     */
    protected function callAPI($function, $params)
    {
        try {
            $functionResult = call_user_func_array(array($this->api, $function), $params);
            return $this->addObjectToStack($functionResult, $objectName = $function.self::DEFAULT_NAME, $context = null);

        } catch(Exception $e) { 

            if (!$this->expectingException)// || $e->getCode() == 666) 
                return $this->fail($this->contextMessage . 'Call to operation ' . get_class($this->api) . '::' . $function . 
                    " failed with exception: {$e->getCode()}\n{$e->getMessage()}\nTrace: " . 
// allways present a trace?                    (($e->getCode() < 100) ? $e->getTraceAsString() : '')); 
                        $e->getTraceAsString());

            return $this->handleException($e);
        }
        return $this;
    }

    /** 
     * internal function to handle calling functions on objetcs under analysis
     * adds result to the analysis stack if successful.
     * Handles exception if expecting it, fails otherwise
     */
    protected function callObjectMethod($method, $arguments)
    {//@assertNotFalse
        $this->assertFalse(!method_exists($this->underAnalysis(), $method), $this->contextMessage . 'O método ' . $method . ' deve existir no Objecto ' . get_class($this->underAnalysis()));
        try {
            $result = call_user_func_array(array($this->underAnalysis(), $method), $arguments);
        } 
        catch(Exception $e)
        { 
            if (!$this->expectingException) throw $e;
            return $this->handleException($e);
        }
        return $this->addObjectToStack($result, $objectName = $method.self::DEFAULT_NAME, $context = null);
    }

#end navigating result, calling methods on service / result objects



#region reporting and debugging

    /**
     * set a context message for errors that will appear on error reports
     * @param string $contextMessage message that will be sent in case of error
     *      that helps to contextualize the test step that throwed the error.
     *      this message may be formatted according to the requested output format (@TODO)
     * @return self (chainable)
     * TESTED, but no tests for it now 
     */
    protected function provingThat($contextMessage) 
    {
        if (!$contextMessage) $this->contextMessage = '';
        else $this->contextMessage = ' * ' .$contextMessage . ' => '; 
        return $this; 
    }

    /**
     * For debugging tests outputs the top of the stack, then exits!
     */ 
    public function stopAndDebug()
    {
        echo "\n\nstopAndDebug called. Exiting...\nLast Result:\n" . print_r($this->underAnalysis(), 1); 
        exit(1); 
    }

#end region messaging and debugging

    // TODO define a method assertNotFalse to handle cases when phpunit doesn't provide it 
}

