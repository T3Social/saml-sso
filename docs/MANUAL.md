# Manual


## Tested SAML Identity Providers 

### SimpleSAML

No known limitations.

### Active Directory Federation Services (ADFS)

For ADFS as IdP, URL rewriting must be activated.

Make also sure the checkbox `SP: Use deprecated entity ID` in the SAML configuration is **not** checked!

### Keycloak

- Install client using Metadata File
- Disable: (Realm) -> Client Scopes -> role_list (saml) -> Mappers tab -> role list -> 'Single Role Attribute'.


### Okta

Currently, the SLO (Single Logout) does not work correctly.

## Advanced SAML settings

In addition to the settings possible in the SAML configuration dialog, further settings can also be made in the configuration file.

The used [OneLogin SAML library](https://github.com/onelogin/php-saml) offers a wide range of additional configuration options.

An example overview of possible settings can be found here:

- https://github.com/onelogin/php-saml/blob/master/advanced_settings_example.php
- https://github.com/onelogin/php-saml/blob/master/settings_example.php

HumHub Configuration file (`protected/config/common.php`):
```php
<?php
return [
    // ...
    'modules' => [
        'saml-sso' => [
            'advancedSettings' => [
                // Begin: Custom Settings ****************************************************************
                'compress' => [
                    // ...
                ],
                    
                'security' => [
                    // ...
                ]
                // End: Custom Settings *******************************************************************
            ],
        ]
    ],
    // ...
];
```

## Encrypted and Signed SP messages

Create a self-signed certificate.

``
openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out saml.crt -keyout saml.pem
``

Add the contents of the file 
- ``saml.pem`` in the input field ``SP: Private key``
- ``saml.crt`` in the input field ``SP: X.509 certificate``

of the SAML configuration.

## Cookie Configuration

We recommend that you disable the feature for SameSite cookies at this time, otherwise you may experience problems with some older Safari browsers.

Example of `@humhub/protected/config/web.php`:

```php
<?php
//...
$config => [
  // ...
  'components' => [  
      'session' => [
            'cookieParams' => [
                'sameSite' => null,
            ],
      ],
  ],
  //...
];
```

## Dependencies / Module Requirements

 * `php >= 5.3.3` and some core extensions like `php-xml`, `php-date`, `php-zlib`.
 * `openssl`. Install the openssl library. It handles x509 certificates.
 * `mcrypt`. Install that library and its php driver if you're going to handle
   encrypted data (`nameID`, `assertions`).
 * `gettext`. Install that library and its php driver. It handles translations.
 * `curl`. Install that library and its php driver if you plan to use the IdP Metadata parser.
 
Since [PHP 5.3 is officially unsupported](http://php.net/eol.php) we recommend you to use a newer PHP version.

