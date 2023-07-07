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
	 * @param string $dirPath  Directory of the test class.
	 * @param string $fileName Test data filename without the `.php` extension.
	 * @param string $dataSet  Optional. Name of a subset in the data.
	 * @return mixed[] Array of test data.
	 */
	public static function getTestData( $dirPath, $fileName, $dataSet = null ) {
		$error_msg = 'Cannot get data with provider: ';

		if ( empty( $dirPath ) || empty( $fileName ) ) {
			self::fail( $error_msg . '$dirPath and/or $fileName not provided.' );
		}

		$dirPath  = str_replace( \WPSYNTEX_TESTS_PATH, \WPSYNTEX_FIXTURES_PATH, $dirPath . DIRECTORY_SEPARATOR );
		$dirPath  = rtrim( $dirPath, '\\/' );
		$dataPath = "$dirPath/{$fileName}.php";

		if ( ! is_readable( $dataPath ) ) {
			$dataPath = self::makePathRelative( $dataPath );
			self::fail( $error_msg . "the data file '$dataPath' is not readable." );
		}

		$data = require $dataPath;

		if ( ! is_array( $data ) ) {
			$dataPath = self::makePathRelative( $dataPath );
			self::fail( $error_msg . "the data file '$dataPath' does not return an array as it should." );
		}

		if ( empty( $data ) ) {
			$dataPath = self::makePathRelative( $dataPath );
			self::fail( $error_msg . "the data file '$dataPath' returns empty data." );
		}

		// Return the full data.
		if ( ! isset( $dataSet ) ) {
			return $data;
		}

		// Return only a subset of the data.
		if ( ! isset( $data[ $dataSet ] ) ) {
			$dataPath = self::makePathRelative( $dataPath );
			self::fail( $error_msg . "the data file '$dataPath' does not contain a '$dataSet' subset." );
		}

		if ( ! is_array( $data[ $dataSet ] ) ) {
			$dataPath = self::makePathRelative( $dataPath );
			self::fail( $error_msg . "the '$dataSet' data subset in file '$dataPath' does not return an array as it should." );
		}

		if ( empty( $data[ $dataSet ] ) ) {
			$dataPath = self::makePathRelative( $dataPath );
			self::fail( $error_msg . "the '$dataSet' data subset in file '$dataPath' returns empty data." );
		}

		return $data[ $dataSet ];
	}

	/**
	 * Makes a path relative to the project.
	 *
	 * @param string $path A path.
	 * @return string
	 */
	public static function makePathRelative( $path ) {
		$rootPath   = preg_quote( WPSYNTEX_PROJECT_PATH, '@' );
		$resultPath = preg_replace( "@^$rootPath@", '', $path );
		return is_string( $resultPath ) ? $resultPath : $path;
	}

	/**
	 * Prepares data for log.
	 *
	 * @param mixed $data Data to log.
	 * @return string
	 */
	public static function varExport( $data ) {
		if ( null === $data ) {
			return 'null';
		}

		if ( is_bool( $data ) ) {
			return $data ? 'true' : 'false';
		}

		if ( is_int( $data ) || is_float( $data ) ) {
			return "$data";
		}

		$data = is_string( $data ) ? $data : (string) var_export( $data, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$data = (string) preg_replace( '@=>\s+([^\s])@', '=> $1', $data );
		$data = (string) preg_replace( '/array\s*\(/', 'array(', $data );
		$data = (string) preg_replace( '/array\(\s+\)/', 'array()', $data );

		return trim( $data );
	}

	/**
	 * Returns the errors from a `WP_Error` object.
	 *
	 * @param WP_Error|mixed $wpError A `WP_Error` object.
	 * @return mixed[]
	 */
	public static function getErrors( $wpError ) {
		if ( ! $wpError instanceof WP_Error ) {
			return [];
		}

		return $wpError->errors;
	}

	/**
	 * Resets the value of a private/protected property to null.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param string        $propertyName Property name for which to gain access.
	 * @return mixed The previous value of the property.
	 */
	public static function resetPropertyValue( $objInstance, $propertyName ) {
		return self::setPropertyValue( $objInstance, $propertyName, null );
	}

	/**
	 * Sets the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param string        $propertyName Property name for which to gain access.
	 * @param mixed         $value        The value to set to the property.
	 * @return mixed The previous value of the property.
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
	 * Returns the value of a private/protected property.
	 * Note: overrides `Yoast\PHPUnitPolyfills\TestCases\TestCase::getPropertyValue()`.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param string        $propertyName Property name for which to gain access.
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
	 * Invokes a private/protected method.
	 *
	 * @throws ReflectionException Throws an exception upon failure.
	 *
	 * @param object|string $objInstance Class name for a static method, or instance for an instance method.
	 * @param string        $methodName  Method name for which to gain access.
	 * @param mixed[]       $args        List of args to pass to the method.
	 * @return mixed The method result.
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
	 * Gives reflective access to a private/protected method.
	 *
	 * @throws ReflectionException Throws an exception if method does not exist.
	 *
	 * @param object|string $objInstance Class name for a static method, or instance for an instance method.
	 * @param string        $methodName  Method name for which to gain access.
	 * @return ReflectionMethod
	 */
	public static function getReflectiveMethod( $objInstance, $methodName ) {
		$ref = new ReflectionMethod( $objInstance, $methodName );
		$ref->setAccessible( true );

		return $ref;
	}

	/**
	 * Gives reflective access to a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param string        $propertyName Property name for which to gain access.
	 * @return ReflectionProperty
	 */
	public static function getReflectiveProperty( $objInstance, $propertyName ) {
		$ref = new ReflectionProperty( $objInstance, $propertyName );
		$ref->setAccessible( true );

		return $ref;
	}

	/**
	 * Sets the value of a private/protected property.
	 *
	 * @throws ReflectionException Throws an exception if property does not exist.
	 *
	 * @param object|string $objInstance  Class name for a static property, or instance for an instance property.
	 * @param string        $propertyName Property name for which to gain access.
	 * @param mixed         $value        The value to set for the property.
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
