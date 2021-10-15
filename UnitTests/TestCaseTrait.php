<?php
/**
 * Generic trait for all tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit
 */

namespace WP_Syntex\Polylang_Phpunit;

use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use WP_Error;

/**
 * Generic trait for all tests.
 */
trait TestCaseTrait {

	/**
	 * Replacement values for `getTestData()`.
	 *
	 * @var array<string|array<string>>
	 */
	protected static $testDataReplacements = [
		'tests'    => [ 'Integration', 'Unit' ],
		'fixtures' => 'Fixtures',
	];

	/**
	 * An instanciated `__return_true()`.
	 *
	 * @return bool
	 */
	public function returnTrue() {
		return true;
	}

	/**
	 * An instanciated `__return_false()`.
	 *
	 * @return bool
	 */
	public function returnFalse() {
		return false;
	}

	/**
	 * Returns the test data, if it exists, for this test class.
	 *
	 * @param  string $dirPath  Directory of the test class.
	 * @param  string $fileName Test data filename without the `.php` extension.
	 * @return array<mixed>     Array of test data.
	 */
	public static function getTestData( $dirPath, $fileName ) {
		if ( empty( $dirPath ) || empty( $fileName ) ) {
			return [];
		}

		$dirPath  = str_replace( static::$testDataReplacements['tests'], static::$testDataReplacements['fixtures'], $dirPath );
		$dirPath  = rtrim( $dirPath, '\\/' );
		$testdata = "$dirPath/{$fileName}.php";

		return is_readable( $testdata ) ? require $testdata : [];
	}

	/**
	 * Get the errors from a `WP_Error` object.
	 *
	 * @param  WP_Error|mixed $wpError A `WP_Error` object.
	 * @return array<mixed>
	 */
	public static function getErrors( $wpError ) {
		if ( ! $wpError instanceof WP_Error ) {
			return [];
		}

		return $wpError->errors;
	}

	/**
	 * Reset the value of a private/protected property to null.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param  string        $propertyName Property name for which to gain access.
	 * @return mixed                       The previous value of the property.
	 */
	public static function resetPropertyValue( $objInstance, $propertyName ) {
		return self::setPropertyValue( $objInstance, $propertyName, null );
	}

	/**
	 * Set the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param  string        $propertyName Property name for which to gain access.
	 * @param  mixed         $value        The value to set to the property.
	 * @return mixed                       The previous value of the property.
	 */
	public static function setPropertyValue( $objInstance, $propertyName, $value ) {
		$ref = self::getReflectiveProperty( $objInstance, $propertyName );

		if ( is_object( $objInstance ) ) {
			$previousValue = $ref->getValue( $objInstance );
			// Instance property.
			$ref->setValue( $objInstance, $value );
		} else {
			$previousValue = $ref->getValue();
			// Static property.
			$ref->setValue( $value );
		}

		return $previousValue;
	}

	/**
	 * Get the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param  string        $propertyName Property name for which to gain access.
	 * @return mixed
	 */
	public static function getPropertyValue( $objInstance, $propertyName ) {
		$ref = self::getReflectiveProperty( $objInstance, $propertyName );

		if ( is_string( $objInstance ) ) {
			return $ref->getValue();
		}

		return $ref->getValue( $objInstance );
	}

	/**
	 * Invoke a private/protected method.
	 *
	 * @throws ReflectionException Throws an exception upon failure.
	 *
	 * @param  object|string $objInstance Class name for a static method, or instance for an instance method.
	 * @param  string        $methodName  Method name for which to gain access.
	 * @param  array<mixed>  $args        List of args to pass to the method.
	 * @return mixed                      The method result.
	 */
	public static function invokeMethod( $objInstance, $methodName, $args = [] ) {
		if ( is_string( $objInstance ) ) {
			$className   = $objInstance;
			$objInstance = null;
		} else {
			$className = get_class( $objInstance );
		}

		$ref = self::getReflectiveMethod( $className, $methodName );

		return $ref->invokeArgs( $objInstance, $args );
	}

	/**
	 * Get reflective access to a private/protected method.
	 *
	 * @throws ReflectionException Throws an exception if method does not exist.
	 *
	 * @param  object|string $objInstance Class name for a static method, or instance for an instance method.
	 * @param  string        $methodName  Method name for which to gain access.
	 * @return ReflectionMethod
	 */
	public static function getReflectiveMethod( $objInstance, $methodName ) {
		$ref = new ReflectionMethod( $objInstance, $methodName );
		$ref->setAccessible( true );

		return $ref;
	}

	/**
	 * Get reflective access to a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param  string        $propertyName Property name for which to gain access.
	 * @return ReflectionProperty
	 */
	public static function getReflectiveProperty( $objInstance, $propertyName ) {
		$ref = new ReflectionProperty( $objInstance, $propertyName );
		$ref->setAccessible( true );

		return $ref;
	}

	/**
	 * Set the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param  string        $propertyName Property name for which to gain access.
	 * @param  mixed         $value        The value to set for the property.
	 * @return ReflectionProperty
	 */
	public static function setReflectiveProperty( $objInstance, $propertyName, $value ) {
		$ref = self::getReflectiveProperty( $objInstance, $propertyName );

		if ( is_object( $objInstance ) ) {
			// Instance property.
			$ref->setValue( $objInstance, $value );
		} else {
			// Static property.
			$ref->setValue( $value );
		}

		$ref->setAccessible( false );

		return $ref;
	}
}
