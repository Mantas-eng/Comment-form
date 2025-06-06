<?php

namespace Laminas\Ldap\Node\Schema\AttributeType;

/**
 * This class provides a contract for schema attribute-types.
 */
interface AttributeTypeInterface
{
    /**
     * Gets the attribute name
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the attribute OID
     *
     * @return string
     */
    public function getOid();

    /**
     * Gets the attribute syntax
     *
     * @return string
     */
    public function getSyntax();

    /**
     * Gets the attribute maximum length
     *
     * @return int|null
     */
    public function getMaxLength();

    /**
     * Returns if the attribute is single-valued.
     *
     * @return bool
     */
    public function isSingleValued();

    /**
     * Gets the attribute description
     *
     * @return string
     */
    public function getDescription();
}
