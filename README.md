# Polylang PHPUnit

A code library for WP Syntex projects, containing:

- scripts to install the WordPress test suite,
- scripts to install the required plugins and themes,
- scripts for building and distributing the project,
- bootstraps allowing to init unit and integration tests,
- helpers for the tests.

## How to

### Install the test suite

The test suite is installed in **this package**'s `tmp` folder.

To tell the installation script how to connect to your database, you can create a `DB-CONFIG` file at the root of your project and formatted like follow (the file is not versioned with git of course).  
Each line is optional, the default values are:

```txt
db_host: localhost
db_name: wordpress_tests
db_user: root
db_pass: '' (an empty string)
```

### Install the dependencies

The plugins and themes are installed in **your project**'s `tmp` folder.

Create a `install-plugins.sh` file that launches all the downloads. Example:

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
downloadWoocommerce

# Install TwentyFourteen.
downloadThemeFromRepository twentyfourteen
```

Premium plugins installed from EDD need a bit more attention because they need a license key. You can provide it by creating a `LICENSE-CODES` file at the root of your project and formatted like follow (the file is not versioned with git of course):

```txt
license {PLUGIN-SLUG}:{YOUR-LICENSE}
site {PLUGIN-SLUG}:{YOUR-SITE}
```

Depending on EDD config, the `site` line may not be required.  
Also, if your plugin from EDD doesn't require a license key, do the following:

```txt
license {PLUGIN-SLUG}:none
```

### Composer scripts

Then you can create composer scripts like these ones for example:

```json
{
    "scripts": {
        "install-suite": "vendor/wpsyntex/wp-phpunit/bin/install-wp-suite.sh",
        "install-suite-with-db": "vendor/wpsyntex/wp-phpunit/bin/install-wp-suite.sh latest true",
        "install-plugins": "Tests/bin/install-plugins.sh",
        "install-tests": [
            "@install-suite",
            "@install-plugins"
        ],
        "install-tests-with-db": [
            "@install-suite-with-db",
            "@install-plugins"
        ],
        "build": "vendor/wpsyntex/wp-phpunit/bin/build.sh",
        "build-update": "vendor/wpsyntex/wp-phpunit/bin/build.sh -- -u",
        "dist": "vendor/wpsyntex/wp-phpunit/bin/distribute.sh -- polylang-foobar"
    },
    "scripts-descriptions": {
        "install-suite": "Installs the WordPress tests suite (without installing the database).",
        "install-suite-with-db": "Installs the WordPress tests suite (with database creation).",
        "install-plugins": "Installs dependencies needed for integration tests.",
        "install-tests": "Installs both the WordPress tests suite (without installing the database) and the dependencies needed for integration tests.",
        "install-tests-with-db": "Installs both the WordPress tests suite (with database creation) and the dependencies needed for integration tests, without creating the database.",
        "build": "Builds the project.",
        "build-update": "Builds the project (runs `composer update` instead of `composer install`).",
        "dist": "Make the zip file to distibute the project release."
    }
}
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
    dirname( __DIR__ ), // Path to the directory containing all tests.
    '5.6.0', // The PHP version required to run this test suite.
    [
        'plugins' => [
            'polylang-pro/polylang.php'                            => true,
            'woocommerce/woocommerce.php'                          => [
                'group' => 'WithWoo',
                'init'  => '\WP_Syntex\Polylang_Phpunit\Integration\WooCommerce\Bootstrap::initWoocommerce',
            ],
            'polylang-wc/polylang-wc.php'                          => [
                'group' => 'WithWoo',
            ],
            dirname( dirname( __DIR__ ) ) . '/polylang-foobar.php' => true,
        ], // A list of plugins to include and activate.
    ]
);
```

The previous code will:

- Require `polylang.php` and `polylang-foobar.php`.
- Require `woocommerce.php` and `polylang-wc.php` if `--group=WithWoo` is used when invoking phpunit.
- Call `\WP_Syntex\Polylang_Phpunit\Integration\WooCommerce\Bootstrap::initWoocommerce()` after `woocommerce.php` is required.

#### Use the trait in your integration tests

You can use the trait `WP_Syntex\Polylang_Phpunit\Integration\TestCaseTrait` in your integration tests.

Hint: if you need to create your own methods `set_up()`, `wpSetUpBeforeClass`, etc, you can't simply call the ones from the trait with `parent::` like you would with parent/child classes. In this case you can do that:

```php
<?php
/**
 * Test Case for all of the integration tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Foobar\Tests\Integration
 */

namespace WP_Syntex\Polylang_Foobar\Tests\Integration;

use WP_Syntex\Polylang_Phpunit\Integration\TestCaseTrait;
use WP_UnitTest_Factory;
use WP_UnitTestCase;

abstract class AbstractTestCase extends WP_UnitTestCase {
    use TestCaseTrait {
        wpSetUpBeforeClass as private traitWpSetUpBeforeClass;
        set_up as private traitSetUp;
    }

    public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
        self::traitWpSetUpBeforeClass( $factory );
        // Do things.
    }

    public function set_up() {
        $this->traitSetUp();
        // Do more things.
    }
}
```

If you need to list some plugins among the "active ones" (`get_option( 'active_plugins' )`) for your integration tests (this may be required for some plugins that test which plugins are active), you can create a class like this one in your tests:

```php
<?php
/**
 * Test Case for all of the integration tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Foobar\Tests\Integration
 */

namespace WP_Syntex\Polylang_Foobar\Tests\Integration;

use WP_Syntex\Polylang_Phpunit\Integration\TestCaseTrait;
use WP_UnitTestCase;

/**
 * Test Case for all of the integration tests.
 */
abstract class AbstractTestCase extends WP_UnitTestCase {
    use TestCaseTrait;

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

Example for your `bootstrap.php` file:

```php
<?php
/**
 * Bootstraps the Polylang Foobar Unit Tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Foobar\Tests\Unit
 */

namespace WP_Syntex\Polylang_Foobar\Tests\Unit;

use function WP_Syntex\Polylang_Phpunit\Unit\bootstrapSuite;

require dirname( dirname( __DIR__ ) ) . '/vendor/wpsyntex/wp-phpunit/UnitTests/Unit/bootstrap.php';

bootstrapSuite( dirname( __DIR__ ), '5.6.0' );
```

#### Extend the abstract class in your unit tests

You can extend `WP_Syntex\Polylang_Phpunit\Unit\AbstractTestCase` in your unit tests.

[Brain\Monkey](https://brain-wp.github.io/BrainMonkey/) is available in your unit tests, since it is included in [Yoast\WPTestUtils](https://github.com/Yoast/wp-test-utils).

In your tests you can mock [WordPress' most common functions](https://github.com/Yoast/wp-test-utils/blob/develop/src/BrainMonkey/TestCase.php) by setting two custom properties. And like for integration tests, some helpers are available in your tests, from the `TestCaseTrait` trait.

```php
<?php
/**
 * Tests for `WP_Syntex\Polylang_Foobar\barbaz()`.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Foobar\Tests\Unit
 */

namespace WP_Syntex\Polylang_Foobar\Tests\Unit;

use WP_Syntex\Polylang_Phpunit\Unit\AbstractTestCase;

/**
 * Tests for `WP_Syntex\Polylang_Foobar\barbaz()`.
 */
class Barbaz extends AbstractTestCase {$$

    /**
     * Stubs the WP native translation functions in the set_up().
     *
     * @var bool
     */
    protected static $stubTranslationFunctions = true;

    /**
     * Stubs the WP native escaping functions in the set_up().
     *
     * @var bool
     */
    protected static $stubEscapeFunctions = true;
}
```
