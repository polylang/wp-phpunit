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
	 * @var string[]|array[]
	 */
	protected $testDataReplacements = [
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
	 * @param  string $dir      Directory of the test class.
	 * @param  string $filename Test data filename without the `.php` extension.
	 * @return array<mixed>     Array of test data.
	 */
	protected function getTestData( $dir, $filename ) {
		if ( empty( $dir ) || empty( $filename ) ) {
			return [];
		}

		$dir      = str_replace( $this->testDataReplacements['tests'], $this->testDataReplacements['fixtures'], $dir );
		$dir      = rtrim( $dir, '\\/' );
		$testdata = "$dir/{$filename}.php";

		return is_readable( $testdata ) ? require $testdata : [];
	}

	/**
	 * Get the errors from a `WP_Error` object.
	 *
	 * @param  WP_Error|mixed $wpError A `WP_Error` object.
	 * @return array<mixed>
	 */
	protected function getErrors( $wpError ) {
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
	 * @param string|object $class    Class name for a static property, or instance for an instance property.
	 * @param string        $property Property name for which to gain access.
	 * @return mixed                  The previous value of the property.
	 */
	protected function resetPropertyValue( $class, $property ) {
		return $this->setPropertyValue( $class, $property, null );
	}

	/**
	 * Set the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string|object $class    Class name for a static property, or instance for an instance property.
	 * @param  string        $property Property name for which to gain access.
	 * @param  mixed         $value    The value to set to the property.
	 * @return mixed                   The previous value of the property.
	 */
	protected function setPropertyValue( $class, $property, $value ) {
		$ref = $this->getReflectiveProperty( $class, $property );

		if ( is_object( $class ) ) {
			$previous = $ref->getValue( $class );
			// Instance property.
			$ref->setValue( $class, $value );
		} else {
			$previous = $ref->getValue();
			// Static property.
			$ref->setValue( $value );
		}

		return $previous;
	}

	/**
	 * Get the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string|object $class    Class name for a static property, or instance for an instance property.
	 * @param  string        $property Property name for which to gain access.
	 * @return mixed
	 */
	protected function getPropertyValue( $class, $property ) {
		$ref = $this->getReflectiveProperty( $class, $property );

		if ( is_string( $class ) ) {
			return $ref->getValue();
		}

		return $ref->getValue( $class );
	}

	/**
	 * Invoke a private/protected method.
	 *
	 * @throws ReflectionException  Throws an exception upon failure.
	 *
	 * @param  string|object $class  Class name for a static method, or instance for an instance method.
	 * @param  string        $method Method name for which to gain access.
	 * @param  array<mixed>  $args   List of args to pass to the method.
	 * @return mixed                 The method result.
	 */
	protected function invokeMethod( $class, $method, $args = [] ) {
		if ( is_string( $class ) ) {
			$className = $class;
			$class     = null;
		} else {
			$className = get_class( $class );
		}

		$method = $this->getReflectiveMethod( $className, $method );

		return $method->invokeArgs( $class, $args );
	}

	/**
	 * Get reflective access to a private/protected method.
	 *
	 * @throws ReflectionException Throws an exception if method does not exist.
	 *
	 * @param  string|object $class  Class name for a static method, or instance for an instance method.
	 * @param  string        $method Method name for which to gain access.
	 * @return ReflectionMethod
	 */
	protected function getReflectiveMethod( $class, $method ) {
		$method = new ReflectionMethod( $class, $method );
		$method->setAccessible( true );

		return $method;
	}

	/**
	 * Get reflective access to a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string|object $class    Class name for a static property, or instance for an instance property.
	 * @param  string        $property Property name for which to gain access.
	 * @return ReflectionProperty
	 */
	protected function getReflectiveProperty( $class, $property ) {
		$property = new ReflectionProperty( $class, $property );
		$property->setAccessible( true );

		return $property;
	}

	/**
	 * Set the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param  string|object $class    Class name for a static property, or instance for an instance property.
	 * @param  string        $property Property name for which to gain access.
	 * @param  mixed         $value    The value to set for the property.
	 * @return ReflectionProperty
	 */
	protected function setReflectiveProperty( $class, $property, $value ) {
		$ref = $this->getReflectiveProperty( $class, $property );

		if ( is_object( $class ) ) {
			// Instance property.
			$ref->setValue( $class, $value );
		} else {
			// Static property.
			$ref->setValue( $value );
		}

		$ref->setAccessible( false );

		return $ref;
	}
}
