# Polylang PHPUnit

A code library for WP Syntex projects, containing:

- scripts to install the WordPress test suite,
- scripts to install the required plugins and themes,
- scripts for building and distributing the project.

## How to

### Install the test suite

The test suite is installed in **this package**'s `tmp` folder.

To tell the installation script how to connect to your database, you can create a `DB-CONFIG` file at the root of your project and formatted like follow (the file is not versioned with git of course).  
Each line is optional, the default values are:

```txt
db_host: localhost
db_name: wordpress_tests
db_user: root
db_pass: root
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
downloadPluginFromRepository woocommerce

# Install TwentyFourteen.
downloadThemeFromRepository twentyfourteen
```

Premium plugins installed from EDD need a bit more attention because they need a license key. You can provide it by creating a `LICENSE-CODES` file at the root of your project and formatted like follow (the file is not versioned with git of course):

```txt
license {PLUGIN-SLUG}:{YOUR-LICENSE}
site {PLUGIN-SLUG}:{YOUR-SITE}
```

Depending on EDD config, the `site` line may not be required.

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
}
```
