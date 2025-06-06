<?php

namespace Laminas\Ldap;

use Laminas\Ldap\Collection;
use Laminas\Ldap\Exception;
use LDAP\Connection;
use Traversable;

use function array_change_key_case;
use function array_key_exists;
use function array_merge;
use function array_reverse;
use function array_shift;
use function array_values;
use function class_exists;
use function count;
use function dechex;
use function extension_loaded;
use function function_exists;
use function implode;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use function is_subclass_of;
use function iterator_to_array;
use function key;
use function mb_strtolower;
use function min;
use function preg_match_all;
use function sprintf;
use function str_replace;
use function strcasecmp;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use function usleep;

use const CASE_LOWER;
use const E_WARNING;
use const PREG_SET_ORDER;

class Ldap
{
    public const SEARCH_SCOPE_SUB  = 1;
    public const SEARCH_SCOPE_ONE  = 2;
    public const SEARCH_SCOPE_BASE = 3;

    public const ACCTNAME_FORM_DN        = 1;
    public const ACCTNAME_FORM_USERNAME  = 2;
    public const ACCTNAME_FORM_BACKSLASH = 3;
    public const ACCTNAME_FORM_PRINCIPAL = 4;

    /**
     * String used with ldap_connect for error handling purposes.
     */
    private ?string $connectString = null;

    /**
     * The options used in connecting, binding, etc.
     *
     * @var array
     */
    protected $options;

    /**
     * The raw LDAP extension resource.
     *
     * @var Connection|null
     */
    protected $resource;

    /**
     * FALSE if no user is bound to the LDAP resource
     * NULL if there has been an anonymous bind
     * username of the currently bound user
     *
     * @var bool|null|string
     */
    protected $boundUser = false;

    /**
     * Caches the RootDse
     *
     * @var Node\RootDse
     */
    protected $rootDse;

    /**
     * Caches the schema
     *
     * @var Node\Schema
     */
    protected $schema;

    /**
     * Current connection retry attempt counter.
     *
     * @var int
     */
    protected $reconnectCount = 0;

    /**
     * Total number of times reconnections were attempted unsuccessfully.
     *
     * @var int
     */
    protected $reconnectsAttempted = 0;

    /** @var array */
    protected $lastConnectBindParams = [];

    /**
     * @param  array|Traversable $options Options used in connecting, binding, etc.
     * @throws Exception\LdapException
     */
    public function __construct($options = [])
    {
        if (! extension_loaded('ldap')) {
            throw new Exception\LdapException(
                null,
                'LDAP extension not loaded',
                Exception\LdapException::LDAP_X_EXTENSION_NOT_LOADED
            );
        }
        $this->setOptions($options);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @return Connection The raw LDAP extension resource.
     */
    public function getResource()
    {
        if (! Handler::isLdapHandle($this->resource) || $this->boundUser === false) {
            $this->bind();
        }

        return $this->resource;
    }

    /**
     * Return the LDAP error number of the last LDAP command
     *
     * @return int
     */
    public function getLastErrorCode()
    {
        ErrorHandler::start(E_WARNING);
        $ret = false;
        if (Handler::isLdapHandle($this->resource)) {
            $ret = ldap_get_option($this->resource, LDAP_OPT_ERROR_NUMBER, $err);
        }
        ErrorHandler::stop();
        if ($ret === true) {
            if ($err <= -1 && $err >= -17) {
                /* For some reason draft-ietf-ldapext-ldap-c-api-xx.txt error
                 * codes in OpenLDAP are negative values from -1 to -17.
                 */
                $err = Exception\LdapException::LDAP_SERVER_DOWN + (-$err - 1);
            }
            return $err;
        }

        return 0;
    }

    /**
     * Return the LDAP error message of the last LDAP command
     *
     * @param  int   $errorCode
     * @param  array $errorMessages
     * @return string
     */
    public function getLastError(&$errorCode = null, ?array &$errorMessages = null)
    {
        $errorCode     = $this->getLastErrorCode();
        $errorMessages = [];

        /* The various error retrieval functions can return
         * different things so we just try to collect what we
         * can and eliminate dupes.
         */
        ErrorHandler::start(E_WARNING);
        $estr1 = "getLastError: could not call ldap_error because LDAP resource was not of type resource";
        if (Handler::isLdapHandle($this->resource)) {
            $estr1 = ldap_error($this->resource);
        }
        ErrorHandler::stop();
        if ($errorCode !== 0 && $estr1 === 'Success') {
            ErrorHandler::start(E_WARNING);
            $estr1 = ldap_err2str($errorCode);
            ErrorHandler::stop();
        }
        if (! empty($estr1)) {
            $errorMessages[] = $estr1;
        }

        ErrorHandler::start(E_WARNING);
        $estr2 = "getLastError: could not call ldap_get_option because LDAP resource was not of type resource";
        if (Handler::isLdapHandle($this->resource)) {
            ldap_get_option($this->resource, LDAP_OPT_ERROR_STRING, $estr2);
            /** @psalm-var string $estr2 */
        }
        ErrorHandler::stop();
        if (! empty($estr2) && ! in_array($estr2, $errorMessages)) {
            $errorMessages[] = $estr2;
        }

        $message = '';
        if ($errorCode > 0) {
            $message = '0x' . dechex($errorCode) . ' ';
        }

        if (count($errorMessages) > 0) {
            $message .= '(' . implode('; ', $errorMessages) . ')';
        } else {
            $message .= '(no error message from LDAP)';
        }

        return $message;
    }

    /**
     * Get the currently bound user
     *
     * FALSE if no user is bound to the LDAP resource
     * NULL if there has been an anonymous bind
     * username of the currently bound user
     *
     * @return bool|null|string
     */
    public function getBoundUser()
    {
        return $this->boundUser;
    }

    /**
     * Sets the options used in connecting, binding, etc.
     *
     * Valid option keys:
     *  host
     *  port
     *  useSsl
     *  username
     *  password
     *  bindRequiresDn
     *  baseDn
     *  accountCanonicalForm
     *  accountDomainName
     *  accountDomainNameShort
     *  accountFilterFormat
     *  allowEmptyPassword
     *  useStartTls
     *  optReferrals
     *  tryUsernameSplit
     *  reconnectAttempts
     *  networkTimeout
     *  saslOpts
     *
     * @param  array|Traversable $options Options used in connecting, binding, etc.
     * @return Ldap Provides a fluent interface
     * @throws Exception\LdapException
     */
    public function setOptions($options)
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        $permittedOptions = [
            'host'                   => null,
            'port'                   => 0,
            'useSsl'                 => false,
            'username'               => null,
            'password'               => null,
            'bindRequiresDn'         => false,
            'baseDn'                 => null,
            'accountCanonicalForm'   => null,
            'accountDomainName'      => null,
            'accountDomainNameShort' => null,
            'accountFilterFormat'    => null,
            'allowEmptyPassword'     => false,
            'useStartTls'            => false,
            'optReferrals'           => false,
            'tryUsernameSplit'       => true,
            'reconnectAttempts'      => 0,
            'networkTimeout'         => null,
            'saslOpts'               => null,
        ];

        foreach ($permittedOptions as $key => $val) {
            if (array_key_exists($key, $options)) {
                $val = $options[$key];
                unset($options[$key]);
                /* Enforce typing. This eliminates issues like Laminas\Config\Reader\Ini
                 * returning '1' as a string (Laminas-3163).
                 */
                switch ($key) {
                    case 'port':
                    case 'accountCanonicalForm':
                    case 'reconnectAttempts':
                    case 'networkTimeout':
                        $permittedOptions[$key] = (int) $val;
                        break;
                    case 'useSsl':
                    case 'bindRequiresDn':
                    case 'allowEmptyPassword':
                    case 'useStartTls':
                    case 'optReferrals':
                    case 'tryUsernameSplit':
                        $permittedOptions[$key] = $val === true
                            || $val === '1'
                            || strcasecmp($val, 'true') === 0;
                        break;
                    case 'saslOpts':
                        $permittedOptions[$key] = $val;
                        break;
                    default:
                        $permittedOptions[$key] = trim((string) $val);
                        break;
                }
            }
        }
        if (count($options) > 0) {
            $key = key($options);
            throw new Exception\LdapException(null, "Unknown Laminas\\Ldap\\Ldap option: $key");
        }
        $this->options = $permittedOptions;

        return $this;
    }

    /**
     * @return array The current options.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /** @return int */
    public function getReconnectsAttempted()
    {
        return $this->reconnectsAttempted;
    }

    /** @return void */
    public function resetReconnectsAttempted()
    {
        $this->reconnectsAttempted = 0;
    }

    /**
     * @return string The hostname of the LDAP server being used to
     *  authenticate accounts
     */
    protected function getHost()
    {
        return $this->options['host'];
    }

    /**
     * @return int The port of the LDAP server or 0 to indicate that no port
     *  value is set
     */
    protected function getPort()
    {
        return $this->options['port'];
    }

    /**
     * @return bool The default SSL / TLS encrypted transport control
     */
    protected function getUseSsl()
    {
        return $this->options['useSsl'];
    }

    /**
     * @return string The default acctname for binding
     */
    protected function getUsername()
    {
        return $this->options['username'];
    }

    /**
     * @return string The default password for binding
     */
    protected function getPassword()
    {
        return $this->options['password'];
    }

    /**
     * @return bool Bind requires DN
     */
    protected function getBindRequiresDn()
    {
        return $this->options['bindRequiresDn'];
    }

    /**
     * Gets the base DN under which objects of interest are located
     *
     * @return string
     */
    public function getBaseDn()
    {
        return $this->options['baseDn'];
    }

    /**
     * Gets any options that have been set for sasl binds.
     *
     * @return string[]|null
     */
    public function getSaslOpts()
    {
        return $this->options['saslOpts'];
    }

    /**
     * @return int Either ACCTNAME_FORM_BACKSLASH, ACCTNAME_FORM_PRINCIPAL or
     * ACCTNAME_FORM_USERNAME indicating the form usernames should be canonicalized to.
     */
    protected function getAccountCanonicalForm()
    {
        /* Account names should always be qualified with a domain. In some scenarios
         * using non-qualified account names can lead to security vulnerabilities. If
         * no account canonical form is specified, we guess based in what domain
         * names have been supplied.
         */
        $accountCanonicalForm = $this->options['accountCanonicalForm'];
        if (! $accountCanonicalForm) {
            $accountDomainName      = $this->getAccountDomainName();
            $accountDomainNameShort = $this->getAccountDomainNameShort();
            if ($accountDomainNameShort) {
                $accountCanonicalForm = self::ACCTNAME_FORM_BACKSLASH;
            } else {
                if ($accountDomainName) {
                    $accountCanonicalForm = self::ACCTNAME_FORM_PRINCIPAL;
                } else {
                    $accountCanonicalForm = self::ACCTNAME_FORM_USERNAME;
                }
            }
        }

        return $accountCanonicalForm;
    }

    /**
     * @return null|string The account domain name
     */
    protected function getAccountDomainName()
    {
        return $this->options['accountDomainName'];
    }

    /**
     * @return null|string The short account domain name
     */
    protected function getAccountDomainNameShort()
    {
        return $this->options['accountDomainNameShort'];
    }

    /**
     * @return string A format string for building an LDAP search filter to match
     * an account
     */
    protected function getAccountFilterFormat()
    {
        return $this->options['accountFilterFormat'];
    }

    /**
     * @return bool Allow empty passwords
     */
    protected function getAllowEmptyPassword()
    {
        return $this->options['allowEmptyPassword'];
    }

    /**
     * @return bool The default SSL / TLS encrypted transport control
     */
    protected function getUseStartTls()
    {
        return $this->options['useStartTls'];
    }

    /**
     * @return bool Opt. Referrals
     */
    protected function getOptReferrals()
    {
        return $this->options['optReferrals'];
    }

    /**
     * @return bool Try splitting the username into username and domain
     */
    protected function getTryUsernameSplit()
    {
        return $this->options['tryUsernameSplit'];
    }

    /**
     * @return int The number of times reconnect to server should be attempted.
     */
    protected function getReconnectsToAttempt()
    {
        return $this->options['reconnectAttempts'];
    }

    /**
     * @return int The value for network timeout when connect to the LDAP server.
     */
    protected function getNetworkTimeout()
    {
        return $this->options['networkTimeout'];
    }

    /**
     * @param  string $acctname
     * @return string The LDAP search filter for matching directory accounts
     */
    protected function getAccountFilter($acctname)
    {
        $dname = '';
        $aname = '';
        $this->splitName($acctname, $dname, $aname);
        $accountFilterFormat = $this->getAccountFilterFormat();
        $aname               = Filter\AbstractFilter::escapeValue($aname);
        if ($accountFilterFormat) {
            return sprintf($accountFilterFormat, $aname);
        }
        if (! $this->getBindRequiresDn()) {
            // is there a better way to detect this?
            return sprintf("(&(objectClass=user)(sAMAccountName=%s))", $aname);
        }

        return sprintf("(&(objectClass=posixAccount)(uid=%s))", $aname);
    }

    /**
     * @param string $name  The name to split
     * @param string $dname The resulting domain name (this is an out parameter)
     * @param string $aname The resulting account name (this is an out parameter)
     * @return void
     */
    protected function splitName($name, &$dname, &$aname)
    {
        $dname = null;
        $aname = $name;

        if (! $this->getTryUsernameSplit()) {
            return;
        }

        $pos = strpos($name, '@');
        if ($pos) {
            $dname = substr($name, $pos + 1);
            $aname = substr($name, 0, $pos);
        } else {
            $pos = strpos($name, '\\');
            if ($pos) {
                $dname = substr($name, 0, $pos);
                $aname = substr($name, $pos + 1);
            }
        }
    }

    /**
     * @param  string $acctname The name of the account
     * @return string The DN of the specified account
     * @throws Exception\LdapException
     */
    protected function getAccountDn($acctname)
    {
        if (Dn::checkDn($acctname)) {
            return $acctname;
        }
        $acctname = $this->getCanonicalAccountName($acctname, self::ACCTNAME_FORM_USERNAME);
        $acct     = $this->getAccount($acctname, ['dn']);

        return $acct['dn'];
    }

    /**
     * @param  string $dname The domain name to check
     * @return bool
     */
    protected function isPossibleAuthority($dname)
    {
        if ($dname === null) {
            return true;
        }
        $accountDomainName      = $this->getAccountDomainName();
        $accountDomainNameShort = $this->getAccountDomainNameShort();
        if ($accountDomainName === null && $accountDomainNameShort === null) {
            return true;
        }
        if ($accountDomainName !== null && strcasecmp($dname, $accountDomainName) === 0) {
            return true;
        }
        if ($accountDomainNameShort !== null && strcasecmp($dname, $accountDomainNameShort) === 0) {
            return true;
        }

        return false;
    }

    /**
     * @param  string $acctname The name to canonicalize
     * @param  int    $form     The desired form of canonicalization
     * @return string The canonicalized name in the desired form
     * @throws Exception\LdapException
     */
    public function getCanonicalAccountName($acctname, $form = 0)
    {
        $dname = '';
        $uname = '';

        $this->splitName($acctname, $dname, $uname);

        if (! $this->isPossibleAuthority($dname)) {
            throw new Exception\LdapException(
                null,
                "Binding domain is not an authority for user: $acctname",
                Exception\LdapException::LDAP_X_DOMAIN_MISMATCH
            );
        }

        if (! $uname) {
            throw new Exception\LdapException(null, "Invalid account name syntax: $acctname");
        }

        if (function_exists('mb_strtolower')) {
            $uname = mb_strtolower($uname, 'UTF-8');
        } else {
            $uname = strtolower($uname);
        }

        if ($form === 0) {
            $form = $this->getAccountCanonicalForm();
        }

        switch ($form) {
            case self::ACCTNAME_FORM_DN:
                return $this->getAccountDn($acctname);
            case self::ACCTNAME_FORM_USERNAME:
                return $uname;
            case self::ACCTNAME_FORM_BACKSLASH:
                $accountDomainNameShort = $this->getAccountDomainNameShort();
                if (! $accountDomainNameShort) {
                    throw new Exception\LdapException(null, 'Option required: accountDomainNameShort');
                }
                return "$accountDomainNameShort\\$uname";
            case self::ACCTNAME_FORM_PRINCIPAL:
                $accountDomainName = $this->getAccountDomainName();
                if (! $accountDomainName) {
                    throw new Exception\LdapException(null, 'Option required: accountDomainName');
                }
                return "$uname@$accountDomainName";
            default:
                throw new Exception\LdapException(null, "Unknown canonical name form: $form");
        }
    }

    /**
     * @param  string $acctname
     * @param  array  $attrs An array of names of desired attributes
     * @return array  An array of the attributes representing the account
     * @throws Exception\LdapException
     */
    protected function getAccount($acctname, ?array $attrs = null)
    {
        $baseDn = $this->getBaseDn();
        if (! $baseDn) {
            throw new Exception\LdapException(null, 'Base DN not set');
        }

        $accountFilter = $this->getAccountFilter($acctname);
        if (! $accountFilter) {
            throw new Exception\LdapException(null, 'Invalid account filter');
        }

        if (! Handler::isLdapHandle($this->getResource())) {
            $this->bind();
        }

        $accounts = $this->search($accountFilter, $baseDn, self::SEARCH_SCOPE_SUB, $attrs);
        $count    = $accounts->count();
        if ($count === 1) {
            $acct = $accounts->getFirst();
            $accounts->close();

            return $acct;
        } else {
            if ($count === 0) {
                $code = Exception\LdapException::LDAP_NO_SUCH_OBJECT;
                $str  = "No object found for: $accountFilter";
            } else {
                $code = Exception\LdapException::LDAP_OPERATIONS_ERROR;
                $str  = "Unexpected result count ($count) for: $accountFilter";
            }
        }
        $accounts->close();

        throw new Exception\LdapException($this, $str, $code);
    }

    /**
     * Selects current parameters on new connections, last when reconnecting.
     *
     * @param string $method
     *   Whether the connect or bind method is the caller.
     * @param string $parameter
     *   The parameter name.
     * @param mixed $property
     *   The value of the parameter as set in an instance property.
     * @return mixed
     *   If a reconnect attempt is being made, the value used for the parameter
     *   last time it was supplied by an external invocation. Otherwise, the
     *   value.
     */
    protected function selectParam($method, $parameter, $property)
    {
        if ($this->reconnectCount > 0) {
            return self::coalesce(
                isset($this->lastConnectBindParams[$method]) ? $this->lastConnectBindParams[$method][$parameter] : null,
                $property
            );
        } else {
            return $property;
        }
    }

    /**
     * @template TA of mixed
     * @template TB of mixed
     * @param TA $a
     * @param TB $b
     * @psalm-return (TA is null ? TB : TA|TB)
     */
    protected static function coalesce($a, $b)
    {
        if ($a !== null) {
            return $a;
        }
        return $b;
    }

    /**
     * @return Ldap Provides a fluent interface
     */
    public function disconnect()
    {
        $this->unbind();
        $this->resetReconnectsAttempted();
        return $this;
    }

    /** @return $this */
    protected function unbind()
    {
        if (Handler::isLdapHandle($this->resource) && is_string($this->boundUser)) {
            ErrorHandler::start(E_WARNING);
            ldap_unbind($this->resource);
            ErrorHandler::stop();
        }
        $this->resource  = null;
        $this->boundUser = false;

        return $this;
    }

    /**
     * To connect using SSL it seems the client tries to verify the server
     * certificate by default. One way to disable this behavior is to set
     * 'TLS_REQCERT never' in OpenLDAP's ldap.conf and restarting Apache. Or,
     * if you really care about the server's cert you can put a cert on the
     * web server.
     *
     * @param  string  $host           The hostname of the LDAP server to connect to
     * @param  int     $port           The port number of the LDAP server to connect to
     * @param  bool $useSsl         Use SSL
     * @param  bool $useStartTls    Use STARTTLS
     * @param  int     $networkTimeout The value for network timeout when connect to the LDAP server.
     * @return Ldap Provides a fluent interface
     * @throws Exception\LdapException
     */
    public function connect($host = null, $port = null, $useSsl = null, $useStartTls = null, $networkTimeout = null)
    {
        if ($this->reconnectCount === 0) {
            $this->lastConnectBindParams[__METHOD__] = [
                'host'           => $host,
                'port'           => $port,
                'useSsl'         => $useSsl,
                'useStartTls'    => $useStartTls,
                'networkTimeout' => $networkTimeout,
            ];
        }

        if ($host === null) {
            $host = $this->selectParam(__METHOD__, 'host', $this->getHost());
        }
        if ($port === null) {
            $port = $this->selectParam(__METHOD__, 'port', $this->getPort());
        } else {
            $port = (int) $port;
        }

        if ($useSsl === null) {
            $useSsl = $this->selectParam(__METHOD__, 'useSsl', $this->getUseSsl());
        } else {
            $useSsl = (bool) $useSsl;
        }

        if ($port === 0) {
            $port = $useSsl ? 636 : 389;
        }

        if ($useStartTls === null) {
            $useStartTls = $this->selectParam(__METHOD__, 'useStartTls', $this->getUseStartTls());
        } else {
            $useStartTls = (bool) $useStartTls;
        }
        if ($networkTimeout === null) {
            $networkTimeout = $this->selectParam(__METHOD__, 'networkTimeout', $this->getNetworkTimeout());
        } else {
            $networkTimeout = (int) $networkTimeout;
        }

        if (! $host) {
            throw new Exception\LdapException(null, 'A host parameter is required');
        }

        /* Because ldap_connect doesn't really try to connect, any connect error
         * will actually occur during the ldap_bind call. Therefore, we save the
         * connect string here for reporting it in error handling in bind().
         */
        $hosts = [];
        if (preg_match_all('~ldap(i|s)?://~', $host, $hosts, PREG_SET_ORDER) > 0) {
            $this->connectString = $host;
            // assign $useSsl to true in case of ldaps schema
            $useSsl = isset($hosts[0][1]) && $hosts[0][1] === 's' ? true : false;
        } else {
            if ($useSsl) {
                $this->connectString = 'ldaps://' . $host;
            } else {
                $this->connectString = 'ldap://' . $host;
            }
            if ($port) {
                $this->connectString .= ':' . $port;
            }
        }

        $this->disconnect();

        // We supporting here only OpenLDAP 2.2 + which uses URLs for connections
        // (drop support of old-style host/port connections).
        ErrorHandler::start();
        $resource = ldap_connect($this->connectString);
        ErrorHandler::stop();

        if (Handler::isLdapHandle($resource)) {
            $this->resource  = $resource;
            $this->boundUser = false;

            $optReferrals = $this->getOptReferrals() ? 1 : 0;
            ErrorHandler::start(E_WARNING);
            if (
                ldap_set_option($resource, LDAP_OPT_PROTOCOL_VERSION, 3)
                && ldap_set_option($resource, LDAP_OPT_REFERRALS, $optReferrals)
            ) {
                if ($networkTimeout) {
                    ldap_set_option($resource, LDAP_OPT_NETWORK_TIMEOUT, $networkTimeout);
                }
                if ($useSsl || ! $useStartTls || ldap_start_tls($resource)) {
                    ErrorHandler::stop();
                    return $this;
                }
            }
            ErrorHandler::stop();

            $zle = new Exception\LdapException($this, "$host:$port");
            $this->disconnect();
            throw $zle;
        }

        throw new Exception\LdapException(null, "Failed to connect to LDAP server: $host:$port", -1);
    }

    /**
     * @param  string $username The username for authenticating the bind
     * @param  string $password The password for authenticating the bind
     * @param  string[]|null $saslOpts Options when performing SASL binds.
     * @return Ldap Provides a fluent interface
     * @throws Exception\LdapException
     */
    public function bind($username = null, $password = null, $saslOpts = null)
    {
        $moreCreds = true;

        if (is_string($password)) {
            // Security check: remove null bytes in password
            // @see https://net.educause.edu/ir/library/pdf/csd4875.pdf
            $password = str_replace("\0", '', $password);
        }

        if ($this->reconnectCount === 0) {
            $this->lastConnectBindParams[__METHOD__] = [
                'username' => $username,
                'password' => $password,
            ];
        }

        if ($username === null) {
            $username  = $this->selectParam(__METHOD__, 'username', $this->getUsername());
            $password  = $this->selectParam(__METHOD__, 'password', $this->getPassword());
            $moreCreds = false;
        }

        if ($saslOpts === null) {
            $saslOpts = $this->getSaslOpts();
        }

        if (empty($username)) {
            /* Perform anonymous bind
             */
            $username = null;
            $password = null;
        } else {
            /* Check to make sure the username is in DN form.
             */
            if (! Dn::checkDn($username)) {
                if ($this->getBindRequiresDn()) {
                    /* moreCreds stops an infinite loop if getUsername does not
                     * return a DN and the bind requires it
                     */
                    if ($moreCreds) {
                        try {
                            $username = $this->getAccountDn($username);
                        } catch (Exception\LdapException $zle) {
                            switch ($zle->getCode()) {
                                case Exception\LdapException::LDAP_NO_SUCH_OBJECT:
                                case Exception\LdapException::LDAP_X_DOMAIN_MISMATCH:
                                case Exception\LdapException::LDAP_X_EXTENSION_NOT_LOADED:
                                    throw $zle;
                            }
                            throw new Exception\LdapException(
                                null,
                                'Failed to retrieve DN for account: ' . $username
                                    . ' [' . $zle->getMessage() . ']',
                                Exception\LdapException::LDAP_OPERATIONS_ERROR
                            );
                        }
                    } else {
                        throw new Exception\LdapException(null, 'Binding requires username in DN form');
                    }
                } else {
                    $username = $this->getCanonicalAccountName(
                        $username,
                        $this->getAccountCanonicalForm()
                    );
                }
            }
        }

        if (! Handler::isLdapHandle($this->resource)) {
            $this->connect();
        }

        if ($username !== null && $password === '' && $this->getAllowEmptyPassword() !== true) {
            $zle = new Exception\LdapException(
                null,
                'Empty password not allowed - see allowEmptyPassword option.'
            );
        } else {
            ErrorHandler::start(E_WARNING);
            if (is_array($saslOpts)) {
                $sasl_mech     = array_key_exists('sasl_mech', $saslOpts) ? $saslOpts['sasl_mech'] : null;
                $sasl_realm    = array_key_exists('sasl_realm', $saslOpts) ? $saslOpts['sasl_realm'] : null;
                $sasl_authc_id = array_key_exists('sasl_authc_id', $saslOpts) ? $saslOpts['sasl_authc_id'] : null;
                $sasl_authz_id = array_key_exists('sasl_authz_id', $saslOpts) ? $saslOpts['sasl_authz_id'] : null;
                $sasl_props    = array_key_exists('props', $saslOpts) ? $saslOpts['props'] : null;

                $bind = ldap_sasl_bind(
                    $this->resource,
                    $username,
                    $password,
                    $sasl_mech,
                    $sasl_realm,
                    $sasl_authc_id,
                    $sasl_authz_id,
                    $sasl_props
                );
            } else {
                $bind = ldap_bind($this->resource, $username, $password);
            }
            ErrorHandler::stop();

            if ($bind !== false) {
                $this->boundUser = $username;
                return $this;
            }

            $this->boundUser = false;

            if ($this->shouldReconnect($this->resource)) {
                return $this;
            }

            $message = $username ?? $this->connectString;
            switch ($this->getLastErrorCode()) {
                case Exception\LdapException::LDAP_SERVER_DOWN:
                    /* If the error is related to establishing a connection rather than binding,
                     * the connect string is more informative than the username.
                     */
                    $message = $this->connectString;
            }

            $zle = new Exception\LdapException($this, $message);
        }
        $this->unbind();

        throw $zle;
    }

    /**
     * @param Connection $resource
     * @return bool
     */
    protected function shouldReconnect($resource)
    {
        if (
            $this->reconnectCount >= $this->getReconnectsToAttempt()
            || ldap_errno($resource) !== -1
        ) {
            $this->reconnectsAttempted = $this->reconnectCount;
            $this->reconnectCount      = 0;
            return false;
        }

        $this->reconnectCount++;
        $this->reconnectSleep();

        try {
            $this->connect();
            $this->bind();
            $this->reconnectsAttempted = $this->reconnectCount;
            $this->reconnectCount      = 0;
            return true;
        } catch (Exception\LdapException $e) {
            if ($e->getCode() !== -1) {
                return false;
            }
        }
        return $this->shouldReconnect($this->getResource());
    }

    protected function reconnectSleep()
    {
        $duration = min((pow(2, min($this->reconnectCount - 1, 0)) - 1) / 4, 10);
        usleep($duration * 1000000);
    }

    /**
     * A global LDAP search routine for finding information.
     *
     * Options can be either passed as single parameters according to the
     * method signature or as an array with one or more of the following keys
     * - filter
     * - baseDn
     * - scope
     * - attributes
     * - sort
     * - collectionClass
     * - sizelimit
     * - timelimit
     *
     * @param  string|Filter\AbstractFilter|array $filter
     * @param  string|Dn|null                     $basedn
     * @param  int                            $scope
     * @param  array                              $attributes
     * @param  string|null                        $sort
     * @param  string|null                        $collectionClass
     * @param  int                            $sizelimit
     * @param  int                            $timelimit
     * @return Collection
     * @psalm-return Collection<array{dn: string, ...}>
     * @throws Exception\LdapException
     */
    public function search(
        $filter,
        $basedn = null,
        $scope = self::SEARCH_SCOPE_SUB,
        array $attributes = [],
        $sort = null,
        $collectionClass = null,
        $sizelimit = 0,
        $timelimit = 0
    ) {
        if (is_array($filter)) {
            $options = array_change_key_case($filter, CASE_LOWER);
            foreach ($options as $key => $value) {
                switch ($key) {
                    case 'filter':
                    case 'basedn':
                    case 'scope':
                    case 'sort':
                        $$key = $value;
                        break;
                    case 'attributes':
                        if (is_array($value)) {
                            $attributes = $value;
                        }
                        break;
                    case 'collectionclass':
                        $collectionClass = $value;
                        break;
                    case 'sizelimit':
                    case 'timelimit':
                        $$key = (int) $value;
                        break;
                }
            }
        }

        if ($basedn === null) {
            $basedn = $this->getBaseDn();
        } elseif ($basedn instanceof Dn) {
            $basedn = $basedn->toString();
        }

        if ($filter instanceof Filter\AbstractFilter) {
            $filter = $filter->toString();
        }

        do {
            $resource = $this->getResource();
            ErrorHandler::start(E_WARNING);

            switch ($scope) {
                case self::SEARCH_SCOPE_ONE:
                    $search = ldap_list($resource, $basedn, $filter, $attributes, 0, $sizelimit, $timelimit);
                    break;
                case self::SEARCH_SCOPE_BASE:
                    $search = ldap_read($resource, $basedn, $filter, $attributes, 0, $sizelimit, $timelimit);
                    break;
                case self::SEARCH_SCOPE_SUB:
                default:
                    $search = ldap_search($resource, $basedn, $filter, $attributes, 0, $sizelimit, $timelimit);
                    break;
            }
            ErrorHandler::stop();
        } while ($search === false && $this->shouldReconnect($resource));

        if ($search === false || is_array($search)) {
            // TODO: determine under which conditions array of results could be returned and if it is possible here
            throw new Exception\LdapException($this, 'searching: ' . $filter);
        }

        $iterator = new Collection\DefaultIterator($this, $search);

        if ($sort !== null && is_string($sort)) {
            $iterator->sort($sort);
        }

        return $this->createCollection($iterator, $collectionClass);
    }

    /**
     * Extension point for collection creation
     *
     * @param  class-string<Collection>|null $collectionClass
     * @return Collection
     * @psalm-return Collection<array{dn: string, ...}>
     * @throws Exception\LdapException
     */
    protected function createCollection(Collection\DefaultIterator $iterator, $collectionClass)
    {
        if ($collectionClass === null) {
            return new Collection($iterator);
        } else {
            $collectionClass = (string) $collectionClass;
            if (! class_exists($collectionClass)) {
                throw new Exception\LdapException(
                    null,
                    "Class '$collectionClass' can not be found"
                );
            }
            if (! is_subclass_of($collectionClass, Collection::class)) {
                throw new Exception\LdapException(
                    null,
                    "Class '$collectionClass' must subclass 'Laminas\\Ldap\\Collection'"
                );
            }

            return new $collectionClass($iterator);
        }
    }

    /**
     * Count items found by given filter.
     *
     * @param  string|Filter\AbstractFilter $filter
     * @param  string|Dn|null               $basedn
     * @param  int                      $scope
     * @return int
     * @throws Exception\LdapException
     */
    public function count($filter, $basedn = null, $scope = self::SEARCH_SCOPE_SUB)
    {
        try {
            $result = $this->search($filter, $basedn, $scope, ['dn'], null);
        } catch (Exception\LdapException $e) {
            if ($e->getCode() === Exception\LdapException::LDAP_NO_SUCH_OBJECT) {
                return 0;
            }
            throw $e;
        }

        return $result->count();
    }

    /**
     * Count children for a given DN.
     *
     * @param  string|Dn $dn
     * @return int
     * @throws Exception\LdapException
     */
    public function countChildren($dn)
    {
        return $this->count('(objectClass=*)', $dn, self::SEARCH_SCOPE_ONE);
    }

    /**
     * Check if a given DN exists.
     *
     * @param  string|Dn $dn
     * @return bool
     * @throws Exception\LdapException
     */
    public function exists($dn)
    {
        return $this->count('(objectClass=*)', $dn, self::SEARCH_SCOPE_BASE) === 1;
    }

    /**
     * Search LDAP registry for entries matching filter and optional attributes
     *
     * Options can be either passed as single parameters according to the
     * method signature or as an array with one or more of the following keys
     * - filter
     * - baseDn
     * - scope
     * - attributes
     * - sort
     * - reverseSort
     * - sizelimit
     * - timelimit
     *
     * @param  string|Filter\AbstractFilter|array $filter
     * @param  string|Dn|null                     $basedn
     * @param  int                            $scope
     * @param  array                              $attributes
     * @param  string|null                        $sort
     * @param  bool                            $reverseSort
     * @param  int                            $sizelimit
     * @param  int                            $timelimit
     * @return array
     * @throws Exception\LdapException
     */
    public function searchEntries(
        $filter,
        $basedn = null,
        $scope = self::SEARCH_SCOPE_SUB,
        array $attributes = [],
        $sort = null,
        $reverseSort = false,
        $sizelimit = 0,
        $timelimit = 0
    ) {
        if (is_array($filter)) {
            $filter = array_change_key_case($filter, CASE_LOWER);
            if (isset($filter['collectionclass'])) {
                unset($filter['collectionclass']);
            }
            if (isset($filter['reversesort'])) {
                $reverseSort = $filter['reversesort'];
                unset($filter['reversesort']);
            }
        }
        $result = $this->search($filter, $basedn, $scope, $attributes, $sort, null, $sizelimit, $timelimit);
        $items  = $result->toArray();
        if ((bool) $reverseSort === true) {
            $items = array_reverse($items, false);
        }

        return $items;
    }

    /**
     * Get LDAP entry by DN
     *
     * @param  string|Dn $dn
     * @param  array     $attributes
     * @param  bool   $throwOnNotFound
     * @return array
     * @throws null|Exception\LdapException
     */
    public function getEntry($dn, array $attributes = [], $throwOnNotFound = false)
    {
        try {
            $result = $this->search(
                "(objectClass=*)",
                $dn,
                self::SEARCH_SCOPE_BASE,
                $attributes,
                null
            );

            return $result->getFirst();
        } catch (Exception\LdapException $e) {
            if ($throwOnNotFound !== false) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Prepares an ldap data entry array for insert/update operation
     *
     * @param  array $entry
     * @throws Exception\InvalidArgumentException
     * @return void
     */
    public static function prepareLdapEntryArray(array &$entry)
    {
        if (array_key_exists('dn', $entry)) {
            unset($entry['dn']);
        }
        foreach ($entry as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $i => $v) {
                    if ($v === null) {
                        unset($value[$i]);
                    } elseif (! is_scalar($v)) {
                        throw new Exception\InvalidArgumentException('Only scalar values allowed in LDAP data');
                    } else {
                        $v = (string) $v;
                        if (strlen($v) === 0) {
                            unset($value[$i]);
                        } else {
                            $value[$i] = $v;
                        }
                    }
                }
                $entry[$key] = array_values($value);
            } else {
                if ($value === null) {
                    $entry[$key] = [];
                } elseif (! is_scalar($value)) {
                    throw new Exception\InvalidArgumentException('Only scalar values allowed in LDAP data');
                } else {
                    $value = (string) $value;
                    if (strlen($value) === 0) {
                        $entry[$key] = [];
                    } else {
                        $entry[$key] = [$value];
                    }
                }
            }
        }
        $entry = array_change_key_case($entry, CASE_LOWER);
    }

    /**
     * Add new information to the LDAP repository
     *
     * @param  string|Dn $dn
     * @param  array     $entry
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function add($dn, array $entry)
    {
        if (! $dn instanceof Dn) {
            $dn = Dn::factory($dn, null);
        }
        static::prepareLdapEntryArray($entry);
        foreach ($entry as $key => $value) {
            if (is_array($value) && count($value) === 0) {
                unset($entry[$key]);
            }
        }

        $rdnParts = $dn->getRdn(Dn::ATTR_CASEFOLD_LOWER);
        foreach ($rdnParts as $key => $value) {
            $value = Dn::unescapeValue($value);
            if (! array_key_exists($key, $entry)) {
                $entry[$key] = [$value];
            } elseif (! in_array($value, $entry[$key])) {
                $entry[$key] = array_merge([$value], $entry[$key]);
            }
        }
        $adAttributes = [
            'distinguishedname',
            'instancetype',
            'name',
            'objectcategory',
            'objectguid',
            'usnchanged',
            'usncreated',
            'whenchanged',
            'whencreated',
        ];
        foreach ($adAttributes as $attr) {
            if (array_key_exists($attr, $entry)) {
                unset($entry[$attr]);
            }
        }

        do {
            $resource = $this->getResource();
            ErrorHandler::start(E_WARNING);
            $isAdded = ldap_add($resource, $dn->toString(), $entry);
            ErrorHandler::stop();
        } while ($isAdded === false && $this->shouldReconnect($resource));

        if ($isAdded === false) {
            throw new Exception\LdapException($this, 'adding: ' . $dn->toString());
        }

        return $this;
    }

    /**
     * Update LDAP registry
     *
     * @param  string|Dn $dn
     * @param  array     $entry
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function update($dn, array $entry)
    {
        if (! $dn instanceof Dn) {
            $dn = Dn::factory($dn, null);
        }
        static::prepareLdapEntryArray($entry);

        $rdnParts = $dn->getRdn(Dn::ATTR_CASEFOLD_LOWER);
        foreach ($rdnParts as $key => $value) {
            $value = Dn::unescapeValue($value);
            if (array_key_exists($key, $entry) && ! in_array($value, $entry[$key])) {
                $entry[$key] = array_merge([$value], $entry[$key]);
            }
        }
        $adAttributes = [
            'distinguishedname',
            'instancetype',
            'name',
            'objectcategory',
            'objectguid',
            'usnchanged',
            'usncreated',
            'whenchanged',
            'whencreated',
        ];
        foreach ($adAttributes as $attr) {
            if (array_key_exists($attr, $entry)) {
                unset($entry[$attr]);
            }
        }

        if (count($entry) > 0) {
            do {
                $resource = $this->getResource();
                ErrorHandler::start(E_WARNING);
                $isModified = ldap_modify($resource, $dn->toString(), $entry);
                ErrorHandler::stop();
            } while ($isModified === false && $this->shouldReconnect($resource));

            if ($isModified === false) {
                throw new Exception\LdapException($this, 'updating: ' . $dn->toString());
            }
        }

        return $this;
    }

    /**
     * Save entry to LDAP registry.
     *
     * Internally decides if entry will be updated to added by calling
     * {@link exists()}.
     *
     * @param  string|Dn $dn
     * @param  array     $entry
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function save($dn, array $entry)
    {
        if ($dn instanceof Dn) {
            $dn = $dn->toString();
        }
        if ($this->exists($dn)) {
            $this->update($dn, $entry);
        } else {
            $this->add($dn, $entry);
        }

        return $this;
    }

    /**
     * Delete an LDAP entry
     *
     * @param  string|Dn $dn
     * @param  bool   $recursively
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function delete($dn, $recursively = false)
    {
        if ($dn instanceof Dn) {
            $dn = $dn->toString();
        }
        if ($recursively === true) {
            if ($this->countChildren($dn) > 0) {
                $children = $this->getChildrenDns($dn);
                foreach ($children as $c) {
                    $this->delete($c, true);
                }
            }
        }

        do {
            $resource = $this->getResource();
            ErrorHandler::start(E_WARNING);
            $isDeleted = ldap_delete($resource, $dn);
            ErrorHandler::stop();
        } while ($isDeleted === false && $this->shouldReconnect($resource));

        if ($isDeleted === false) {
            throw new Exception\LdapException($this, 'deleting: ' . $dn);
        }

        return $this;
    }

    /**
     * Add one or more attributes to the specified dn
     *
     * @param  string|Dn $dn
     * @param  array     $attributes
     * @param bool       $allowEmptyAttributes
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function addAttributes($dn, array $attributes, $allowEmptyAttributes = false)
    {
        // Safety-flap: Check whether there are empty arrays that would cause
        // complete removal of entries without the emptyAll flag.
        if ($allowEmptyAttributes !== true) {
            foreach ($attributes as $key => $value) {
                if (empty($value)) {
                    unset($attributes[$key]);
                }
            }
        }

        if ($dn instanceof Dn) {
            $dn = $dn->toString();
        }

        do {
            ErrorHandler::start(E_WARNING);
            $entryAdded = ldap_mod_add($this->resource, $dn, $attributes);
            ErrorHandler::stop();
        } while ($entryAdded === false && $this->shouldReconnect($this->resource));

        if ($entryAdded === false) {
            throw new Exception\LdapException($this, 'adding attribute: ' . $dn);
        }

        return $this;
    }

    /**
     * Update one or more attributes to the specified dn
     *
     * @param  string|Dn $dn
     * @param  array     $attributes
     * @param bool       $allowEmptyAttributes
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function updateAttributes($dn, array $attributes, $allowEmptyAttributes = false)
    {
        // Safety-flap: Check whether there are empty arrays that would cause
        // complete removal of entries without the emptyAll flag.
        if ($allowEmptyAttributes !== true) {
            foreach ($attributes as $key => $value) {
                if (empty($value)) {
                    unset($attributes[$key]);
                }
            }
        }

        if ($dn instanceof Dn) {
            $dn = $dn->toString();
        }

        do {
            ErrorHandler::start(E_WARNING);
            $entryUpdated = ldap_mod_replace($this->resource, $dn, $attributes);
            ErrorHandler::stop();
        } while ($entryUpdated === false && $this->shouldReconnect($this->resource));

        if ($entryUpdated === false) {
            throw new Exception\LdapException($this, 'updating attribute: ' . $dn);
        }

        return $this;
    }

    /**
     * Delete single attributes from a LDAP-Node
     *
     * This method removes single attributes from a node identified by
     * <var>$dn</var>. The attributes have to be given as array where the
     * array-key is the attribute-name and the array-value is the attribute-value
     * that is to be removed.
     *
     * To remove multiple entries of an attribute pass an array with the values
     * to be removed as value of the key. So if you want to remove more than
     * one <strong>memberUid</strong>-attribute you would pass
     * <code>array('memberUid' => ['uid1', 'uid2',...]);</code> as
     * <var>$attributes</var>
     *
     * Beware that passing an empty array will remove <strong>all</strong>
     * entries of the attribute. Therefore you will have to set the
     * <var>$emptyAll</var>-flag!
     *
     * @param Dn|string $dn                   The DN for which to remove attributes
     * @param array     $attributes           The attributes to be removed
     * @param bool      $allowEmptyAttributes Whether empty attribute-array
     *                                        should remove all attribute-
     *                                        values or not.
     * @throws Exception\LdapException Is thrown when the LDAP-server reported an error.
     * @return Ldap Provides a fluent interface
     */
    public function deleteAttributes($dn, array $attributes, $allowEmptyAttributes = false)
    {
        // Safety-flap: Check whether there are empty arrays that would cause
        // complete removal of entries without the emptyAll flag.
        if ($allowEmptyAttributes !== true) {
            foreach ($attributes as $key => $value) {
                if (empty($value)) {
                    unset($attributes[$key]);
                }
            }
        }

        if ($dn instanceof Dn) {
            $dn = $dn->toString();
        }

        do {
            ErrorHandler::start(E_WARNING);
            $isDeleted = ldap_mod_del($this->resource, $dn, $attributes);
            ErrorHandler::stop();
        } while ($isDeleted === false && $this->shouldReconnect($this->resource));

        if ($isDeleted === false) {
            throw new Exception\LdapException($this, 'deleting: ' . $dn);
        }

        return $this;
    }

    /**
     * Retrieve the immediate children DNs of the given $parentDn
     *
     * This method is used in recursive methods like {@see delete()}
     * or {@see copy()}
     *
     * @param  string|Dn $parentDn
     * @throws Exception\LdapException
     * @return array of DNs
     */
    protected function getChildrenDns($parentDn)
    {
        if ($parentDn instanceof Dn) {
            $parentDn = $parentDn->toString();
        }
        $children = [];

        do {
            $resource = $this->getResource();
            ErrorHandler::start(E_WARNING);
            $search = ldap_list($resource, $parentDn, '(objectClass=*)', ['dn']);
            ErrorHandler::stop();
        } while ($search === false && $this->shouldReconnect($resource));

        if ($search === false || is_array($search)) {
            // TODO: determine under which conditions array of results could be returned and if it is possible here
            throw new Exception\LdapException($this, 'listing: ' . $parentDn);
        }

        ErrorHandler::start(E_WARNING);
        for (
            $entry = ldap_first_entry($resource, $search);
            $entry !== false;
            $entry = ldap_next_entry($resource, $entry)
        ) {
            $childDn = ldap_get_dn($resource, $entry);
            if ($childDn === false) {
                ErrorHandler::stop();
                throw new Exception\LdapException($this, 'getting dn');
            }
            $children[] = $childDn;
        }
        ldap_free_result($search);
        ErrorHandler::stop();

        return $children;
    }

    /**
     * Moves a LDAP entry from one DN to another subtree.
     *
     * @param  string|Dn $from
     * @param  string|Dn $to
     * @param  bool   $recursively
     * @param  bool   $alwaysEmulate
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function moveToSubtree($from, $to, $recursively = false, $alwaysEmulate = false)
    {
        if ($from instanceof Dn) {
            $orgDnParts = $from->toArray();
        } else {
            $orgDnParts = Dn::explodeDn($from);
        }

        if ($to instanceof Dn) {
            $newParentDnParts = $to->toArray();
        } else {
            $newParentDnParts = Dn::explodeDn($to);
        }

        $newDnParts = array_merge([array_shift($orgDnParts)], $newParentDnParts);
        $newDn      = Dn::fromArray($newDnParts);

        return $this->rename($from, $newDn, $recursively, $alwaysEmulate);
    }

    /**
     * Moves a LDAP entry from one DN to another DN.
     *
     * This is an alias for {@link rename()}
     *
     * @param  string|Dn $from
     * @param  string|Dn $to
     * @param  bool   $recursively
     * @param  bool   $alwaysEmulate
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function move($from, $to, $recursively = false, $alwaysEmulate = false)
    {
        return $this->rename($from, $to, $recursively, $alwaysEmulate);
    }

    /**
     * Renames a LDAP entry from one DN to another DN.
     *
     * This method implicitly moves the entry to another location within the tree.
     *
     * @param  string|Dn $from
     * @param  string|Dn $to
     * @param  bool   $recursively
     * @param  bool   $alwaysEmulate
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function rename($from, $to, $recursively = false, $alwaysEmulate = false)
    {
        $emulate = (bool) $alwaysEmulate;
        if (! function_exists('ldap_rename')) {
            $emulate = true;
        } elseif ($recursively) {
            $emulate = true;
        }

        if ($emulate === false) {
            if ($from instanceof Dn) {
                $from = $from->toString();
            }

            if ($to instanceof Dn) {
                $newDnParts = $to->toArray();
            } else {
                $newDnParts = Dn::explodeDn($to);
            }

            $newRdn    = Dn::implodeRdn(array_shift($newDnParts));
            $newParent = Dn::implodeDn($newDnParts);

            do {
                $resource = $this->getResource();
                ErrorHandler::start(E_WARNING);
                $isOK = ldap_rename($resource, $from, $newRdn, $newParent, true);
                ErrorHandler::stop();
            } while ($isOK === false && $this->shouldReconnect($resource));

            if ($isOK === false) {
                throw new Exception\LdapException($this, 'renaming ' . $from . ' to ' . $to);
            } elseif (! $this->exists($to)) {
                $emulate = true;
            }
        }
        if ($emulate) {
            $this->copy($from, $to, $recursively);
            $this->delete($from, $recursively);
        }

        return $this;
    }

    /**
     * Copies a LDAP entry from one DN to another subtree.
     *
     * @param  string|Dn $from
     * @param  string|Dn $to
     * @param  bool   $recursively
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function copyToSubtree($from, $to, $recursively = false)
    {
        if ($from instanceof Dn) {
            $orgDnParts = $from->toArray();
        } else {
            $orgDnParts = Dn::explodeDn($from);
        }

        if ($to instanceof Dn) {
            $newParentDnParts = $to->toArray();
        } else {
            $newParentDnParts = Dn::explodeDn($to);
        }

        $newDnParts = array_merge([array_shift($orgDnParts)], $newParentDnParts);
        $newDn      = Dn::fromArray($newDnParts);

        return $this->copy($from, $newDn, $recursively);
    }

    /**
     * Copies a LDAP entry from one DN to another DN.
     *
     * @param  string|Dn $from
     * @param  string|Dn $to
     * @param  bool   $recursively
     * @return Ldap Provides a fluid interface
     * @throws Exception\LdapException
     */
    public function copy($from, $to, $recursively = false)
    {
        $entry = $this->getEntry($from, [], true);

        if ($to instanceof Dn) {
            $toDnParts = $to->toArray();
        } else {
            $toDnParts = Dn::explodeDn($to);
        }
        $this->add($to, $entry);

        if ($recursively === true && $this->countChildren($from) > 0) {
            $children = $this->getChildrenDns($from);
            foreach ($children as $c) {
                $cDnParts      = Dn::explodeDn($c);
                $newChildParts = array_merge([array_shift($cDnParts)], $toDnParts);
                $newChild      = Dn::implodeDn($newChildParts);
                $this->copy($c, $newChild, true);
            }
        }

        return $this;
    }

    /**
     * Returns the specified DN as a Laminas\Ldap\Node
     *
     * @param  string|Dn $dn
     * @return Node|null
     * @throws Exception\LdapException
     */
    public function getNode($dn)
    {
        return Node::fromLdap($dn, $this);
    }

    /**
     * Returns the base node as a Laminas\Ldap\Node
     *
     * @return Node
     * @throws Exception\LdapException
     */
    public function getBaseNode()
    {
        $baseNode = $this->getNode($this->getBaseDn());

        assert($baseNode instanceof Node);

        return $baseNode;
    }

    /**
     * Returns the RootDse
     *
     * @return Node\RootDse
     * @throws Exception\LdapException
     */
    public function getRootDse()
    {
        if ($this->rootDse === null) {
            $this->rootDse = Node\RootDse::create($this);
        }

        return $this->rootDse;
    }

    /**
     * Returns the schema
     *
     * @return Node\Schema
     * @throws Exception\LdapException
     */
    public function getSchema()
    {
        if ($this->schema === null) {
            $this->schema = Node\Schema::create($this);
        }

        return $this->schema;
    }
}
