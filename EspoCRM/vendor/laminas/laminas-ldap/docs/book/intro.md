# Introduction

laminas-ldap lets you perform LDAP operations, including, but not limited to,
binding, searching and modifying entries in an LDAP directory.

## Theory of operation

This component currently consists of the main `Laminas\Ldap\Ldap` class, which
conceptually represents a binding to a single LDAP server and allows for
executing operations against a LDAP server such as OpenLDAP or ActiveDirectory
(AD) servers. The parameters for binding may be provided explicitly or in the
form of an options array. `Laminas\Ldap\Node` provides an object-oriented interface
for single LDAP nodes and can be used to form a basis for an active-record-like
interface for a LDAP-based domain model.

The component provides several helper classes to perform operations on LDAP
entries (`Laminas\Ldap\Attribute`) such as setting and retrieving attributes (date
values, passwords, boolean values, ...), to create and modify LDAP filter
strings (`Laminas\Ldap\Filter`) and to manipulate LDAP distinguished names (DN)
(`Laminas\Ldap\Dn`).

Additionally the component abstracts LDAP schema browsing for OpenLDAP and
ActiveDirectory servers `Laminas\Ldap\Node\Schema` and server information retrieval
for OpenLDAP-, ActiveDirectory- and Novell eDirectory servers
(`Laminas\Ldap\Node\RootDse`).

Usage of laminas-ldap depends on the type of LDAP server, and is best summarized with
some examples.

If you are using OpenLDAP, consider the following example (note that the
`bindRequiresDn` option is important if you are **not** using AD):

```php
use Laminas\Ldap\Ldap;

$options = [
    'host'              => 's0.foo.net',
    'username'          => 'CN=user1,DC=foo,DC=net',
    'password'          => 'pass1',
    'bindRequiresDn'    => true,
    'accountDomainName' => 'foo.net',
    'baseDn'            => 'OU=Sales,DC=foo,DC=net',
];

$ldap = new Ldap($options);
$acctname = $ldap->getCanonicalAccountName('abaker', Ldap::ACCTNAME_FORM_DN);
echo "$acctname\n";
```

If you are using Microsoft AD:

```php
use Laminas\Ldap\Ldap;

$options = [
    'host'                   => 'dc1.w.net',
    'useStartTls'            => true,
    'username'               => 'user1@w.net',
    'password'               => 'pass1',
    'accountDomainName'      => 'w.net',
    'accountDomainNameShort' => 'W',
    'baseDn'                 => 'CN=Users,DC=w,DC=net',
];

$ldap = new Ldap($options);
$acctname = $ldap->getCanonicalAccountName('bcarter', Ldap::ACCTNAME_FORM_DN);
echo "$acctname\n";
```

Note that we use the `getCanonicalAccountName()` method to retrieve the account
DN here only because that is what exercises the most of what little code is
currently present in this class.

### Automatic Username Canonicalization When Binding

If `bind()` is called with a non-DN username but `bindRequiresDN` is `true`
and no username in DN form was supplied as an option, the bind will fail.
However, if a username in DN form is supplied in the options array,
`Laminas\Ldap\Ldap` will first bind with that username, retrieve the account DN for
the username supplied to `bind()` and then re-bind with that DN.

This behavior is critical to [Laminas\\Authentication\\Adapter\\Ldap](http://docs.laminas.dev/laminas-authentication/adapter/ldap/),
which passes the username supplied by the user directly to `bind()`.

The following example illustrates how the non-DN username 'abaker' can be used
with `bind()`:

```php
use Laminas\Ldap\Ldap;

$options = [
    'host'              => 's0.foo.net',
    'username'          => 'CN=user1,DC=foo,DC=net',
    'password'          => 'pass1',
    'bindRequiresDn'    => true,
    'accountDomainName' => 'foo.net',
    'baseDn'            => 'OU=Sales,DC=foo,DC=net',
];

$ldap = new Ldap($options);
$ldap->bind('abaker', 'moonbike55');
$acctname = $ldap->getCanonicalAccountName('abaker', Ldap::ACCTNAME_FORM_DN);
echo "$acctname\n";
```

The `bind()` call in this example sees that the username 'abaker' is not in DN
form, finds `bindRequiresDn` is `TRUE`, uses `CN=user1,DC=foo,DC=net` and
`pass1` to bind, retrieves the DN for 'abaker', unbinds and then rebinds with
the newly discovered `CN=Alice Baker,OU=Sales,DC=foo,DC=net`.

### Account Name Canonicalization

The `accountDomainName` and `accountDomainNameShort` options are used for two
purposes: (1) they facilitate multi-domain authentication and failover
capability, and (2) they are also used to canonicalize usernames. Specifically,
names are canonicalized to the form specified by the `accountCanonicalForm`
option. This option may one of the following values:

The default canonicalization depends on what account domain name options were
supplied. If `accountDomainNameShort` was supplied, the default
`accountCanonicalForm` value is `ACCTNAME_FORM_BACKSLASH`. Otherwise, if
`accountDomainName` was supplied, the default is `ACCTNAME_FORM_PRINCIPAL`.

Account name canonicalization ensures that the string used to identify an
account is consistent regardless of what was supplied to `bind()`. For example,
if the user supplies an account name of `abaker@example.com` or just `abaker`
and the `accountCanonicalForm` is set to 3, the resulting canonicalized name
would be `EXAMPLE\\abaker`.

### Multi-domain Authentication and Failover

The `Laminas\Ldap\Ldap` component by itself makes no attempt to authenticate with
multiple servers.  However, `Laminas\Ldap\Ldap` is specifically designed to handle
this scenario gracefully. The required technique is to simply iterate over an
array of arrays of serve options and attempt to bind with each server. As
described above `bind()` will automatically canonicalize each name, so it does
not matter if the user passes `abaker@foo.net` or `Wbcarter` or `cdavis`; the
`bind()` method will only succeed if the credentials were successfully used in
the bind.

Consider the following example that illustrates the technique required to
implement multi-domain authentication and failover:

```php
use Laminas\Ldap\Exception\LdapException;
use Laminas\Ldap\Ldap;

$acctname = 'W\\user2';
$password = 'pass2';

$multiOptions = [
    'server1' => [
        'host'                   => 's0.foo.net',
        'username'               => 'CN=user1,DC=foo,DC=net',
        'password'               => 'pass1',
        'bindRequiresDn'         => true,
        'accountDomainName'      => 'foo.net',
        'accountDomainNameShort' => 'FOO',
        'accountCanonicalForm'   => 4, // ACCT_FORM_PRINCIPAL
        'baseDn'                 => 'OU=Sales,DC=foo,DC=net',
    ],
    'server2' => [
        'host'                   => 'dc1.w.net',
        'useSsl'                 => true,
        'username'               => 'user1@w.net',
        'password'               => 'pass1',
        'accountDomainName'      => 'w.net',
        'accountDomainNameShort' => 'W',
        'accountCanonicalForm'   => 4, // ACCT_FORM_PRINCIPAL
        'baseDn'                 => 'CN=Users,DC=w,DC=net',
    ],
];

$ldap = new Ldap();

foreach ($multiOptions as $name => $options) {
    echo "Trying to bind using server options for '$name'\n";

    $ldap->setOptions($options);
    try {
        $ldap->bind($acctname, $password);
        $acctname = $ldap->getCanonicalAccountName($acctname);
        echo "SUCCESS: authenticated $acctname\n";
        return;
    } catch (LdapException $zle) {
        echo '  ' . $zle->getMessage() . "\n";
        if ($zle->getCode() === LdapException::LDAP_X_DOMAIN_MISMATCH) {
            continue;
        }
    }
}
```

If the bind fails for any reason, the next set of server options is tried.

The `getCanonicalAccountName()` call gets the canonical account name that the
application would presumably use to associate data with such as preferences. The
`accountCanonicalForm = 4` in all server options ensures that the canonical form
is consistent regardless of which server was ultimately used.

The special `LDAP_X_DOMAIN_MISMATCH` exception occurs when an account name with
a domain component was supplied (e.g., `abaker@foo.net` or `FOO\\abaker` and not
just `abaker`) but the domain component did not match either domain in the
currently selected server options. This exception indicates that the server is
not an authority for the account. In this case, the bind will not be performed,
thereby eliminating unnecessary communication with the server. Note that the
`continue` instruction has no effect in this example, but in practice for error
handling and debugging purposes, you will probably want to check for
`LDAP_X_DOMAIN_MISMATCH` as well as `LDAP_NO_SUCH_OBJECT` and
`LDAP_INVALID_CREDENTIALS`.

The above code is very similar to code used within
[Laminas\\Authentication\\Adapter\\Ldap](http://docs.laminas.dev/laminas-authentication/adapter/ldap/).
In fact,we recommend that you use that authentication adapter for multi-domain +
failover LDAP based authentication (or copy the code).
