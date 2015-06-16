<?php

require_once __DIR__ . '/../src/ResultChecker.php';
require_once __DIR__ . '/helpers/ExampleApi.php';

class ApiCallTestCase extends Sapo\TestAbstraction\ResultChecker
{
//before
    protected static $api;

    public static function setUpBeforeClass()
    {
        self::$api = new ExampleApi();
    }

    // runs for every test before setUpBeforeClass
    public function __construct() { } 

    public function testIntegerResult()
    {
        $this->checkAPI(self::$api)
            ->provingThat("Sum actually works")
            ->initSum(3)
                ->returnsInteger()
                ->returnsNativeType('integer')->thatEquals(3)
            ->sum(4)->sum(2)->sum(1)
                ->provingThat("return is an integer")->returnsNativeType('integer')
                ->provingThat("beingOneOf Works 1")->beingOneOf(1,10)
                ->provingThat("beingOneOf Works 2")->beingOneOf(10)
                ->provingThat("beingOneOf Works 3")->beingOneOf(10, 9)
                ->provingThat("Equals Works")->thatEquals(10)
                ->provingThat("IsGreaterThan Works")->thatIsGreaterThan(9)
//needs array                ->provingThat("thatIsContainedIn Works")->thatIsContainedIn(3, 10)
                ->provingThat("thatIsSimilarTo Works")->thatIsSimilarTo("10");
//May only call on strings                ->provingThat("thatStartWith Works")->thatStartsWith("1");
    
        $caught = false;
        try {
            $this->checkAPI(self::$api)
                ->provingThat("returnsNativeType actually works")
            ->initSum(3)->returnsNativeType('intege');
        } catch(Exception $e)
        {
            $caught = true;
        }

        if (!$caught) $this->fail('returnsNativeType called with "intege" should fail');
    }

    public function testBooleanResult()
    {
        $this->checkAPI(self::$api)
            ->provingThat("Boolean tests works actually works")
                ->getTrue()->returnsBoolean()
                ->getFalse()->returnsBoolean()

        $this->getZero()->mustFail('returnsBoolean', [], 'int 0 is not boolean');
    }


    public function testDouble() {
        $this->checkAPI(self::$api)
            ->getDouble()
                ->returnsDouble()
                ->returnsFloat();
    }

    public function testObjectResult()
    {
        $this->checkAPI(self::$api)
            ->getTree()->returnsInstanceOf('ExampleTree');
    }

    /**
     * @TODO how to turn a failure into a pass in the report? 
     * this is suposed to fail!
     */
    public function testObjectResultNegative()
    {
        try {
            $this->provingThat('passing the wrong object type should fail')->checkAPI(self::$api)
                ->getTree()->returnsInstanceOf('ExampleNode');
        }
        catch(Exception $e) // PHPUnit_Framework_ExpectationFailedException
        {
            return; // OK! it's supposed to fail
            //echo "\nClass = " . get_class($e) . ", message: " . $e->getMessage();
        }
        
        $this->fail("passing the wrong object type should fail" . 
            " => Must return instance of ExampleNode\n" . 
            "Must Fail asserting that ExampleTree Object is an instance of class ExampleNode"
        );
    }

    public function testStackingAnalysis()
    {
        $this->provingThat('stacking will work')
            ->checkAPI(self::$api)
            ->getTree()->returnsInstanceOf('ExampleTree');

        $this->assertContains('stacking', $this->getContextMessage());

        $this->getNode()->returnsInstanceOf('ExampleNode');
        $this->assertContains('stacking', $this->getContextMessage());

        try {
            $caught = false;
            $this->getNode();
        } catch(Exception $e) { $caught = true; } // OK! it's supposed to fail
        if (!$caught) $this->fail('Node should not have a method called getNode');

        $this->endObjectAnalysis();

        $this->provingThat("we have two contexts")->getNode();
        $this->assertContains('two contexts', $this->getContextMessage());

        $this->endObjectAnalysis()->returnsInstanceOf('ExampleTree');
        $this->assertContains('stacking', $this->getContextMessage());
    }

    public function testWithExistingPublicProperty()
    {
        $this->provingThat('acessing public members will work')
            ->checkAPI(self::$api)
            ->getTree()->returnsInstanceOf('ExampleTree')
            ->withExistingProperty('public_member')->thatEquals('public');
    }

    public function testWithExistingProtectedProperty()
    {
//$this->markTestIncomplete('must implement some way to test if a property is public or else a fatal error follows');
        $this->provingThat('acessing protected members will not work')
            ->checkAPI(self::$api)
            ->getTree()->returnsInstanceOf('ExampleTree');

        try {
            $this->withExistingProperty('protected_member')->thatEquals('protected');
        } catch(Exception $e) { return; }
        $this->fail('acessing protected member should not work');
    }

    public function testWithExistingPrivateProperty()
    {
//$this->markTestIncomplete('must implement some way to test if a property is public or else a fatal error follows');
        $this->provingThat('acessing private members will not work')
            ->checkAPI(self::$api)
            ->getTree()->returnsInstanceOf('ExampleTree');

        try {
            $this->withExistingProperty('private_member')->thatEquals('private');
        } catch(Exception $e) { return; }
        $this->fail('acessing private member should not work');
    }

    public function testAcessorGet()
    {
        $this->provingThat('acessing by getter method will work')
            ->checkAPI(self::$api)
            ->getTree()->returnsInstanceOf('ExampleTree')
            ->getPrivateMember()
                ->returnsNativeType('string')->thatEquals('private')
                ->returnsString()->thatEquals('private');
    }

    public function testWithExistingPrivatePropertyByMagicGet()
    {
        $this->provingThat('acessing by magic method will work')
            ->checkAPI(self::$api)
            ->getMagic()->returnsInstanceOf('ExampleMagic')
            ->withExistingProperty('protected_member')->thatEquals('protected');
    }

    public function testWithInexistingPropertyByMagicGet()
    {
        $caught = false;
        try {
            $this->provingThat('acessing by magic method will work')
                ->checkAPI(self::$api)
                ->getMagic()->returnsInstanceOf('ExampleMagic')
                ->withExistingProperty('inexisting_member');
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('withExistingProperty(inexisting_member) should fail');
    }

    public function testWithVirtualPropertyByMagicGet()
    {
        $this->provingThat('acessing virtual property by magic method will work')
            ->checkAPI(self::$api)
            ->getEvolvedMagic()->returnsInstanceOf('EvolvedMagic');

        try {
            $this->withVirtualProperty('inexisting_member');
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('withExistingProperty(inexisting_member) should fail');

        $this->withVirtualProperty('protected')->thatEquals('protected');

    }


    public function testCheckThatIndex()
    {
        $this->provingThat('Check a response index works')
            ->checkAPI(self::$api)
            ->getList()->returnsArray()
                ->checkThatIndex(2)->returnsString()->thatEquals('c')
            ->provingThat('check that check arrayOfSize works and getList with negative index will return all the list except the ending nodes')
                ->getList(-1)->returnsArrayOfSize(2) // 3 -1
            ->provingThat('getList with positive index will return as many items as the passed integer, provided they exist')
                ->getList(0)->returnsArrayOfSize(0)
                ->getList(1)->returnsArrayOfSize(1)
                ->getList(2)->returnsArrayOfSize(2)
                ->getList(PHP_INT_MAX)->returnsArrayOfSize(3);
    }

    public function testPopPopPop()
    {
        $this->provingThat('Check a response index works')
            ->checkAPI(self::$api)
            ->endObjectAnalysis()->endObjectAnalysis()->endObjectAnalysis();
    }

    public function testReturnsNull()
    {
        $this->checkAPI(self::$api)
            ->provingThat("returnsNull actually works")
            ->getNull()->returnsNull();

        $caught = false;
        try {
            $this->provingThat("returnsNull works with int 0")->getZero()->returnsNull();
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('returnsNativeType returnsNull with int 0 should fail');

        $caught = false;
        try {
            $this->provingThat("returnsNull works with int 0")->getZero()->returnsNull();
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('returnsNativeType returnsNull with int 0 should fail');

        $caught = false;
        try {
            $this->provingThat("returnsNull works with int 0")->getZero()->returnsNull();
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('returnsNativeType returnsNull with int 0 should fail');

    }

    public function testThatEqualsFails()
    {
        $this->checkAPI(self::$api)
            ->provingThat("ThatEquals Fails when not equal")
            ->getZero()->returnsInteger()->thatEquals(0);
        $caught = false;
        try {
            $this->shouldBeEqualTo('0');
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('shouldBe is an alias of thatEquals that should perform strict equallity "0" != 0');
    }

    public function testThatIsSimilarToFails()
    {
        $this->checkAPI(self::$api)
            ->provingThat("ThatIsSimilarTo Fails when not similar")
            ->getZero()->returnsInteger()
                ->thatIsSimilarTo(0)
                ->andThatIsSimilarTo('0')
                ->andThatIsSimilarTo(false)
                ->andThatIsSimilarTo(null);
        $caught = false;
        try {
            $this->shouldBeSimilarTo(1);
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('shouldBe is an alias of thatIs (SimilarTo) that should perform strict equallity "0" != 0');
    }

    public function testThatIsGreaterThanFails()
    {
        $this->checkAPI(self::$api)
            ->provingThat("ThatIsGreaterThan Fails when not Greater")
            ->getZero()->returnsInteger()
                ->thatIsGreaterThan(-1);

        $caught = false;
        try {
            $this->shouldBeGreaterThan(0);
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('shouldBe is an alias of thatIs (Greaterthan) that should perform > comparision (0 > 0)');

        $caught = false;
        try {
            $this->shouldBeGreaterThan(1);
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('shouldBe is an alias of thatIs (Greaterthan) that should perform > comparision (0 > 1)');
    }

    public function testThatHasSomeValueFails()
    {
        $this->checkAPI(self::$api)
            ->provingThat("ThatHasSomeValue Fails when value is null")
            ->getZero()->returnsInteger()->thatHasSomeValue()
            ->getFilledString()->returnsString()->thatHasSomeValue()
            ->getFilledArray()->returnsArray()->thatHasSomeValue();

        $caught = false;
        try {
            $this->getNull()->returnsNull()->hasSomeValue();
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('has is an alias of thatHas (SomeValue) that should perform check if not empty (Null should be empty)');

        $caught = false;
        try {
            $this->getEmptyArray()->hasSomeValue();
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('has is an alias of thatHas (SomeValue) that should perform check if not empty (Empty Array should be empty)');

        $caught = false;
        try {
            $this->getEmptyString()->hasSomeValue();
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('has is an alias of thatHas (SomeValue) that should perform check if not empty (Empty String shoud be empty)');
    }

    public function testBeingOneOfFails()
    {
        $this->checkAPI(self::$api)
            ->provingThat("beingOneOf Fails when value is not contained in list")
            ->getFilledArray();

        $targetList = $this->underAnalysis();

        $this->getRandomElement($targetList)->returnsString(); // focus analysis on property otherwise it would test the return value
        $randomElement = $this->underAnalysis();

        $this->isOneOf('a', 'b', 'c'); // it's in the group
        $this->isOneOf($randomElement); // exact match

        $caught = false;
        try {
            $this->isOneOf('d');
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('isOneOf is an alias of beingOneOf that should perform check if result is in a list of values');

        $caught = false;
        try {
            $this->isOneOf();
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('isOneOf is an alias of beingOneOf that should perform check if result is in a list of values');
    }

    public function testBeingInstanceOf()
    {
        $this->checkAPI(self::$api)
            ->getTree()->returnsInstanceOf('ExampleTree')
                ->withExistingProperty('public_magic')->beingInstanceOf("ExampleMagic")
                ->withExistingProperty('public_member')
                    ->beingOfNativeType('string')
                    ->beingAString()
                ->withExistingProperty('public_array')
                    ->beingOfNativeType('array')
                    ->beingAnArray()
                ->withExistingProperty('public_integer')
                    ->beingOfNativeType('int')
                    ->beingAnInteger();

        $caught = false;
        try {
            $this->getTree()->returnsInstanceOf('ExampleTree')
                ->withExistingProperty('public_magic')->beingInstanceOf("ExampleNode");
        } catch(Exception $e) { $caught = true; }
        if (!$caught) $this->fail('isOneOf is an alias of beingOneOf that should perform check if result is in a list of values');
    }

    public function testThatIsSubHashTableOfFails()
    {
        $this->checkAPI(self::$api)
            ->provingThat("thatIsSubHashTableOf Fails when result is not contained in list")
            ->getFilledArray()->returnsArray()
                ->thatIsSubHashTableOf(['a','b','c'])
                ->isSubHashTableOf(['a','b','c','d']);


        $this->mustFail('thatIsSubHashTableOf', ['d','c','a','b'], 'position 0 should have a, so d is not a match');
        $this->mustFail('thatIsSubHashTableOf', ['b','c'], 'position 0 should have a');
        $this->mustFail('thatIsSubHashTableOf', ['a','c'], 'position 1 should have b');
        $this->mustFail('isSubHashTableOf', ['a','b'], 'missing c at position 2');

        $this->getDictionary()->returnsArray()
            ->isSubHashTableOf(['a' => 'A', 'b' => 'B', 'c' => 'C'])
            ->isSubHashTableOf(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D']);

        $this->mustFail('isSubHashTableOf', ['a' => 'A', 'b' => 'B'], 'missing key c with value C');
    }

    public function testThatStartsWithFails()
    {
        $this->checkAPI(self::$api)
            ->provingThat("thatStartsWith Fails when result doesnt start with expected string")
            ->getNumericString()//->returnsString()
            ->thatStartsWith('1')
            ->andThatStartsWith('12')
            ->andThatStartsWith('123');

        $this->mustFail('thatStartsWith', '', 'string must be non-empty');//fails - strpos will complain
        $this->mustFail('thatStartsWith', '2', 'must start with 1');
        $this->mustFail('thatStartsWith', '132', 'must start with 1 then 2 ');
        $this->mustFail('thatStartsWith', '1234', 'this string is bigger than the original');
        $this->mustFail('thatStartsWith', 1, "this isn't even a string");
    }

    public function testExceptionExpectations()
    {
        $code = 879;
        $message = 'um-do-li-ta';

        $this->checkAPI(self::$api)
            ->provingThat('matching exception codes works')
                ->expectException($code, null, null)
                    ->raiseException($code)

            ->provingThat('matching Exception type works')
                ->expectException(null, 'Exception', null)
                    ->raiseException(null, null)

            ->provingThat('matching ExampleException type works')
                ->expectException(null, 'ExampleException', null)
                    ->raiseException(null, 'example')

            ->provingThat('matching RuntimeException type works')
                ->expectException(null, 'RuntimeException', null)
                    ->raiseException(null, 'inexisting')

            ->provingThat('matching Exception message works')
                ->expectException(null, null, $message)
                    ->raiseException(null, null, $message);
    }

    public function testExceptionExpectationsFail()
    {
        $code = mt_rand(1, 1000);
        $anotherCode = $code + 1;
        $message = 'um-do-li-ta';
        $anotherMessage = md5($message);

        $this->checkAPI(self::$api);

        $this->provingThat('Different Exception Codes will flag an error')
            ->expectException($code, null, null);
        $this->mustFail('raiseException', [$anotherCode], 'exception Code is different, should fail');

        $this->provingThat('Different Exception Codes will flag an error')
            ->expectException(null, 'Exception', null);
        $this->mustFail('raiseException', [null, 'blah'], 'exception class is different, should fail');

        $this->provingThat('Different Exception Messages will flag an error')
            ->expectException(null, null, 'mesg 1');
        $this->mustFail('raiseException', [null, null, 'mesg 2'], 'exception class is different, should fail');

        $this->provingThat('Not throwing an Exception will flag an error')
            ->expectException(null, null, null);
        $this->mustFail('sum', [3], 'no exception was raised when one was expected, should fail');

    }

    private function mustFail($methodName, $args, $message)
    {
        $caught = false;
        try {
            call_user_method_array($methodName, $this, $args);
        } catch(Exception $e) { 
            $caught = true; 
//echo "\n" . $e->getMessage(); 
        }
        if (!$caught) $this->fail($message);
    }
}
