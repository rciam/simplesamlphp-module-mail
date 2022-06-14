# simplesamlphp-module-mail

A SimpleSAMLphp module for handling email information received from the identity provider.

## AddVerifiedEmailAttribute

This filter adds a new attribute that contains the verified email address(es) of the user.

If scopeChecking is enabled, then filter will check if the domain part of the email is (sub) domain
 - of any of the scopes (regular expressions are not supported)
 - of the IdP endpoint
 - of home organization

If any of the above is met then a new attribute will be added that will contain the verified email address(es) of the user.

### SimpleSAMLphp configuration

The following authproc filter configuration options are supported:

* `emailAttribute`: Optional, a string to use as the name of the attribute that holds the user's email address. Defaults to 'mail'.
* `verifiedEmailAttribute`: Optional, a string to use as the name of the attribute that will hold the user's verified email address list. Defaults to 'voPersonVerifiedEmail'.
* `replace`: Optional, a boolean to use as flag, to replace `verifiedEmailAttribute` if exists. Defaults to `false`.
* `idpEntityIdIncludeList`: Optional, an array of strings that contains entityIDs for which the module will generate the `verifiedEmailAttribute` attribute. Defaults to empty array.
* `scopeChecking`: Optional, a boolean to use as flag, that indicates if scopes will be checked, in order to verify an email. Defaults to `false`.
* `homeOrganizationAttribute`: Optional, a string to use as the name of the attribute that
holds home organisation information. Defaults to 'schacHomeOrganization'.

### Example authproc filter configuration

```php
    authproc = [
        ...
        'XX' => [
            'class' => 'mail:AddVerifiedEmailAttribute',
            'emailAttribute' => 'email',                 // Optional, defaults to 'mail'
            'verifiedEmailAttribute' => 'verifiedEmail', // Optional, defaults to 'voPersonVerifiedEmail'
            'replace' => true,                           // Optional, defaults to false
            'idpEntityIdIncludeList' => [
                'https://idp.example1.org',
                'https://idp.example2.org',
            ],                                           // Optional, defaults to empty array
            'scopeChecking' => true,                     // Optional, defaults to false
            'homeOrganizationAttribute' => 'urn:oid:1.3.6.1.4.1.25178.1.2.9', // Optional, defaults to 'schacHomeOrganization'
        ]
```

## Compatibility matrix

This table matches the module version with the supported SimpleSAMLphp version.

| Module |  SimpleSAMLphp |
|:------:|:--------------:|
| v1.x   | v1.14          |
| v2.x   | v1.17+         |

## License

Licensed under the Apache 2.0 license, for details see `LICENSE`.
