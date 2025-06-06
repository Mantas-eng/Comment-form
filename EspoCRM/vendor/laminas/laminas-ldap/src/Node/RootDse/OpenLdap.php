<?php

namespace Laminas\Ldap\Node\RootDse;

use Laminas\Ldap\Node;

/**
 * Laminas\Ldap\Node\RootDse\OpenLdap provides a simple data-container for the
 * RootDse node of an OpenLDAP server.
 */
class OpenLdap extends Node\RootDse
{
    /**
     * Gets the configContext.
     *
     * @return string|null
     */
    public function getConfigContext()
    {
        return $this->getAttribute('configContext', 0);
    }

    /**
     * Gets the monitorContext.
     *
     * @return string|null
     */
    public function getMonitorContext()
    {
        return $this->getAttribute('monitorContext', 0);
    }

    /**
     * Determines if the control is supported
     *
     * @param  string|array $oids control oid(s) to check
     * @return bool
     */
    public function supportsControl($oids)
    {
        return $this->attributeHasValue('supportedControl', $oids);
    }

    /**
     * Determines if the extension is supported
     *
     * @param  string|array $oids oid(s) to check
     * @return bool
     */
    public function supportsExtension($oids)
    {
        return $this->attributeHasValue('supportedExtension', $oids);
    }

    /**
     * Determines if the feature is supported
     *
     * @param  string|array $oids feature oid(s) to check
     * @return bool
     */
    public function supportsFeature($oids)
    {
        return $this->attributeHasValue('supportedFeatures', $oids);
    }

    /**
     * Gets the server type
     *
     * @return int
     */
    public function getServerType()
    {
        return self::SERVER_TYPE_OPENLDAP;
    }
}
