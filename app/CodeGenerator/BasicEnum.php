<?php

namespace App\CodeGenerator;

/**
 * @source https://stackoverflow.com/questions/254514/php-and-enumerations
 *  added type hinting, formatting
 */

abstract class BasicEnum
{
	private static $constants = null;

	private static function getConstants() : array
	{
		if (self::$constants == null) {
			self::$constants = [];
		}
		$calledClass = get_called_class();
		if (!array_key_exists($calledClass, self::$constants)) {
			$reflect = new \ReflectionClass($calledClass);
			self::$constants[$calledClass] = $reflect->getConstants();
		}

		return self::$constants[$calledClass];
	}

	public static function isValidName(string $name, bool $strict = false) : bool
	{
		$constants = self::getConstants();

		if ($strict) {
			return array_key_exists($name, $constants);
		}

		$keys = array_map('strtolower', array_keys($constants));

		return in_array(strtolower($name), $keys);
	}

	public static function isValidValue(string $value, $strict = true) : bool
	{
		$values = array_values(self::getConstants());

		return in_array($value, $values, $strict);
	}
}
