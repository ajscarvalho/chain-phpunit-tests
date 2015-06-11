<?php

class ExampleApi
{
	protected $sum = 0;

	public function initSum($number)
	{
		$this->sum = $number;
		return $this->sum;
	}

	public function sum($number)
	{
		$this->sum += $number;
		return $this->sum;
	}

    public function getTree() { return new ExampleTree(); }
    public function getMagic() { return new ExampleMagic(); }
    public function getEvolvedMagic() { return new EvolvedMagic(); }
    
    public function getList($maxCount = PHP_INT_MAX) { return array_slice(['a','b','c'], 0, $maxCount); }
    public function getNull() { return null; }
    public function getEmptyString() { return ''; }
    public function getFilledString() { return 'abc'; }
    public function getNumericString() { return '123'; }
    public function getFilledArray() { return ['a', 'b', 'c']; }
    public function getDictionary() { return ['a' => 'A', 'b' => 'B', 'c' => 'C']; }
    public function getZero() { return 0; }
    public function getDouble() { return 4.3; }
    public function getStringZero() { return '0'; }
    
    public function raiseException($code = null, $type = null, $message = null) {
		$thrownCode = $code?: mt_rand(1, 1000);
		$thrownMessage = $message ?:uniqid();
		if (null == $type) throw new Exception($thrownMessage, $thrownCode);
		else if ('example' == $type) throw new ExampleException($thrownMessage, $thrownCode);
		else throw new RuntimeException($thrownMessage, $thrownCode);
	}

	/** selects a random element from the list */
    public function getRandomElement(array $itemList) {
		$index = mt_rand(0, count($itemList)-1);
		return $itemList[$index];
	}
}

class ExampleTree {
	// property and accessor tests
	public $public_member = 'public';
	public $public_magic = null;
	public $public_array = [];
	public $public_integer = 0;

	protected $protected_member = 'protected';
	private $private_member = 'private';

	public function __construct() {$this->public_magic = new ExampleMagic();}
	public function getPrivateMember() { return $this->private_member; }
/*	public function __get($name){
echo "\nCalled __get($name)\n";
		if (false !== strpos($name, '_member')) 
			$memberName = $name . '_member';
echo $memberName; var_dump(isset($this->$memberName));
		if (isset($this->$memberName)) return $this->$memberName;
		throw new ExampleException('No such Property', 500);
	}
*/
	// object returned tests
	public function getNode() { return new ExampleNode(); }
}

class ExampleNode {
	public function getLowerChild() { return new Node(); }
}

class ExampleMagic {
	protected $protected_member = 'protected';
	public function __get($name){
		if (isset($this->$name)) return $this->$name;
		throw new ExampleException('No such Property', 500);
	}
}

class EvolvedMagic {
	protected $protected_member = 'protected';
	public function __get($name){
		$memberName = $name . '_member';
		if (isset($this->$memberName)) return $this->$memberName;
		throw new ExampleException('No such Property', 500);
	}
}

class ExampleException extends Exception {}
