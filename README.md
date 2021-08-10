# Polylang PHPUnit

A code library for WP Syntex projects, containing:

- scripts to install the WordPress test suite,
- scripts to install the required plugins and themes,
- scripts for building and distributing the project.

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
