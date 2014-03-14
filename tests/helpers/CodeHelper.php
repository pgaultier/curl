<?php
namespace Codeception\Module;

// here you can define custom functions for CodeGuy

class CodeHelper extends \Codeception\Module
{
	public function seeObjectIsInstanceOf($object, $class) {
		\PHPUnit_Framework_Assert::assertInstanceOf($class, $object);
	}
}
