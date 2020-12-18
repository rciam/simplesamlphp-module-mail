# simplesamlphp-module-mail
A SimpleSAMLphp module for handling email information received from the identity provider.

## AddVerifiedEmailAttribute

This filter adds a new attribute that contains the verified email address(es) of the user.

### SimpleSAMLphp configuration

The following authproc filter configuration options are supported:

* `emailAttribute`: Optional, a string to use as the name of the attribute that holds the user's email address. Defaults to 'mail'.
* `verifiedEmailAttribute`: Optional, a string to use as the name of the attribute that will hold the user's verified email address list. Defaults to 'voPersonVerifiedEmail'.
* `replace`: Optional, a boolean to use as flag, to replace `verifiedEmailAttribute` if exists. Defaults to `false`.
* `idpEntityIdIncludeList`: Optional, a array of strings that contains entityIDs for which the module will generate the `verifiedEmailAttribute` attribute. Defaults to empty array.

### Example authproc filter configuration

```php
    authproc = array(
        ...
        'XX' => array(
            'class' => 'mail:AddVerifiedEmailAttribute',
            'emailAttribute' => 'email',                 // Optional, defaults to 'mail'
            'verifiedEmailAttribute' => 'verifiedEmail', // Optional, defaults to 'voPersonVerifiedEmail'
            'replace' => true,                           // Optional, defaults to false
            'idpEntityIdIncludeList' => array(
                'https://idp.example1.org',
                'https://idp.example2.org',
            ),                                           // Optional, defaults to empty array
        )
```

## Compatibility matrix

This table matches the module version with the supported SimpleSAMLphp version.

| Module |  SimpleSAMLphp |
|:------:|:--------------:|
|        | v1.14          |

## License

Licensed under the Apache 2.0 license, for details see `LICENSE`.
