# Polylang PHPUnit

A code library for WP Syntex projects, containing:

- scripts to install the WordPress test suite,
- scripts to install the required plugins and themes,
- scripts for building and distributing the project,
- bootstraps allowing to init unit and integration tests,
- helpers for the tests,
- rulesets enforcing our conventions (phpcs): custom rules (Generic, PSR2, Squiz), Suin, WordPress.

## How to

### Install the test suite and dependencies

The test suite is installed in this package's `tmp` folder. The plugins and themes are installed in your project's `tmp` folder.  
You can create composer scripts like these ones for example:

```json
{
    "scripts": {
        "install-suite": "vendor/wpsyntex/wp-phpunit/bin/install-wp-tests.sh wordpress_tests root root 127.0.0.1:3306 latest",
        "install-suite-nodb": "vendor/wpsyntex/wp-phpunit/bin/install-wp-tests.sh wordpress_tests root root 127.0.0.1:3306 latest true",
        "fix-suite": "php vendor/wpsyntex/wp-phpunit/bin/fix-tests-suite.php",
        "install-plugins": "Tests/bin/install-plugins.sh",
        "install-tests": [
            "@install-suite",
            "@fix-suite",
            "@install-plugins"
        ],
        "install-tests-nodb": [
            "@install-suite-nodb",
            "@fix-suite",
            "@install-plugins"
        ],
        "build": "vendor/wpsyntex/wp-phpunit/bin/build.sh -- 0 1",
        "build-update": "vendor/wpsyntex/wp-phpunit/bin/build.sh -- -u 1",
        "dist": "vendor/wpsyntex/wp-phpunit/bin/distribute.sh -- polylang-foobar 1"
    },
}
```

Example for your `install-plugins.sh` file, don't forget to include `wp-download-tools.sh` to get access to the download functions:

```bash
#!/usr/bin/env bash

. "$PWD/vendor/wpsyntex/wp-phpunit/bin/wp-download-tools.sh"

# Install WP All Import Pro.
downloadPluginFromEdd wp-all-import-pro 'WP All Import' https://www.wpallimport.com

# Install Polylang Pro.
downloadPolylangPro

# Install Polylang for WooCommerce.
downloadPolylangForWoocommerce

# Install WooCommerce.
downloadPluginFromRepository woocommerce

# Install TwentyFourteen.
downloadThemeFromRepository twentyfourteen
```

### Integration tests

#### Bootstrap for integration tests

Example for your `bootstrap.php` file:

```php
<?php
/**
 * Bootstraps the Polylang Foobar integration tests
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Foobar\Tests\Integration
 */

namespace WP_Syntex\Polylang_Foobar\Tests\Integration;

use function WP_Syntex\Polylang_Phpunit\Integration\bootstrapSuite;

require dirname( dirname( __DIR__ ) ) . '/vendor/wpsyntex/wp-phpunit/UnitTests/Integration/bootstrap.php';

bootstrapSuite(
    [
        'polylang-pro/polylang.php'                            => true,
        'woocommerce/woocommerce.php'                          => 'withWoo',
        dirname( dirname( __DIR__ ) ) . '/polylang-foobar.php' => true,
    ],
    dirname( __DIR__ ),
    '5.6.0'
);
```

#### Extend the abstract class in your integration tests

You can simply extend `WP_Syntex\Polylang_Phpunit\Integration\AbstractTestCase`.

If you need to list some plugins among the "active ones" (`get_option( 'active_plugins' )`) for your integration tests (this may be required for some plugins that test which plugins are active), you can create an abstract class like this one and extend it in your tests:

```php
<?php
/**
 * Test Case for all of the integration tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Foobar\Tests\Integration
 */

namespace WP_Syntex\Polylang_Foobar\Tests\Integration;

use WP_Syntex\Polylang_Phpunit\Integration\AbstractTestCase as PllPhpunitTestCase;

/**
 * Test Case for all of the integration tests.
 */
abstract class AbstractTestCase extends PllPhpunitTestCase {

    /**
     * List of active plugins.
     *
     * @var array<string>
     */
    protected $activePlugins = [
        'wp-all-import-pro/wp-all-import-pro.php',
    ];
}

```

Some helpers are available in your tests, like toying with reflections or getting test data. You can find them in the `TestCaseTrait` trait.

### Unit tests

#### Bootstrap for unit tests

Example for your `bootstrap.php` file (unit tests):

```php
<?php
/**
 * Bootstraps the Polylang Foobar Unit Tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Foobar\Tests\Unit
 */

namespace WP_Syntex\Polylang_Foobar\Tests\Unit;

use WP_Syntex\Polylang_Phpunit\Bootstrap;

( new Bootstrap( 'Unit', dirname( __DIR__ ), '5.6.0' ) )->initTestSuite();
```

#### Extend the abstract class in your unit tests

You can simply extend `WP_Syntex\Polylang_Phpunit\Unit\AbstractTestCase`.

[Brain\Monkey](https://brain-wp.github.io/BrainMonkey/) is available in your unit tests.

In your tests you can mock WordPress' most common functions by setting a custom property:

```php
/**
 * Mock the common WP Functions in the setUp().
 *
 * @var bool
 */
protected static $mockCommonWpFunctionsInSetUp = true;
```

Like for integration tests, some helpers are available in your tests, from the `TestCaseTrait` trait.

### PHPCS

Example for your `phpcs.xml.dist` file:

```xml
<?xml version="1.0"?>
<ruleset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" name="Polylang Foobar" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/squizlabs/PHP_CodeSniffer/master/phpcs.xsd">
    <description>Coding standards for Polylang Foobar.</description>

    <arg value="p"/><!-- Shows progress. -->
    <arg name="colors"/><!-- Shows results with colors. -->
    <arg name="extensions" value="php"/><!-- Limits to PHP files. -->

    <!-- https://github.com/squizlabs/PHP_CodeSniffer/wiki/Usage -->
    <file>.</file>
    <exclude-pattern>bin/*</exclude-pattern>
    <exclude-pattern>src/Dependencies/*</exclude-pattern>
    <exclude-pattern>tmp/*</exclude-pattern>
    <exclude-pattern>vendor/*</exclude-pattern>

    <!-- Run against the PHPCompatibility ruleset: PHP 5.6 and higher + WP 5.4 and higher. -->
    <!-- https://github.com/PHPCompatibility/PHPCompatibilityWP -->
    <rule ref="PHPCompatibilityWP"/>
    <config name="testVersion" value="5.6-"/>
    <config name="minimum_supported_wp_version" value="5.4"/>

    <!-- Our own ruleset. -->
    <rule ref="vendor/wpsyntex/wp-phpunit/phpcs/ruleset.xml">
        <exclude name="Squiz.PHP.CommentedOutCode.Found"/>
        <exclude name="WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize"/>
    </rule>

    <!-- Run against the PSR-4 ruleset. -->
    <!-- https://github.com/suin/phpcs-psr4-sniff -->
    <arg name="basepath" value="."/>
</ruleset>

```

### PHPStan

Example for your `phpstan.neon.dist` file:

```neon
includes:
    - phar://phpstan.phar/conf/bleedingEdge.neon
    - vendor/wpsyntex/polylang-phpstan/extension.neon
parameters:
    level: max
    paths:
        - %currentWorkingDirectory%/src
        - %currentWorkingDirectory%/Tests
        - %currentWorkingDirectory%/polylang-foobar.php
    excludes_analyse:
        - src/Dependencies/*
    bootstrapFiles:
        - vendor/wpsyntex/wp-phpunit/tmp/wordpress-tests-lib/includes/testcase.php
        - vendor/wpsyntex/polylang-stubs/polylang-stubs.php
    scanDirectories:
        - vendor/wpsyntex/wp-phpunit/tmp/wordpress-tests-lib/includes
        - vendor/wpsyntex/wp-phpunit/UnitTests
    ignoreErrors:
        - '#^Constant WPSYNTEX_PROJECT_PATH not found\.$#'
```
