<?php

namespace Laminas\Ldap\Converter;

use DateTime;
use DateTimeZone;
use Exception as PHPException;
use Laminas\Ldap\ErrorHandler;

use function chr;
use function date_default_timezone_get;
use function dechex;
use function get_resource_type;
use function hexdec;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_object;
use function is_resource;
use function is_scalar;
use function is_string;
use function ord;
use function preg_match;
use function preg_replace_callback;
use function serialize;
use function str_pad;
use function str_replace;
use function stream_get_contents;
use function strlen;
use function strtolower;
use function substr;
use function unserialize;

use const E_NOTICE;
use const STR_PAD_LEFT;

/**
 * Laminas\Ldap\Converter is a collection of useful LDAP related conversion functions.
 */
class Converter
{
    public const STANDARD         = 0;
    public const BOOLEAN          = 1;
    public const GENERALIZED_TIME = 2;

    /**
     * Converts all ASCII chars < 32 to "\HEX"
     *
     * @link   http://pear.php.net/package/Net_LDAP2
     * @see    Net_LDAP2_Util::asc2hex32() from Benedikt Hallinger <beni@php.net>
     *
     * @param string $string String to convert
     * @return string
     */
    public static function ascToHex32($string)
    {
        for ($i = 0, $len = strlen($string); $i < $len; $i++) {
            $char = substr($string, $i, 1);
            if (ord($char) < 32) {
                $hex = dechex(ord($char));
                if (strlen($hex) === 1) {
                    $hex = '0' . $hex;
                }
                $string = str_replace($char, '\\' . $hex, $string);
            }
        }
        return $string;
    }

    /**
     * Converts all Hex expressions ("\HEX") to their original ASCII characters
     *
     * @link   http://pear.php.net/package/Net_LDAP2
     * @see    Net_LDAP2_Util::hex2asc() from Benedikt Hallinger <beni@php.net>,
     *         heavily based on work from DavidSmith@byu.net
     *
     * @param string $string String to convert
     * @return string
     */
    public static function hex32ToAsc($string)
    {
        $string = preg_replace_callback(
            '/\\\([0-9A-Fa-f]{2})/',
            static fn($matches): string => chr(hexdec($matches[1])),
            $string
        );
        return $string;
    }

    /**
     * Convert any value to an LDAP-compatible value.
     *
     * By setting the <var>$type</var>-parameter the conversion of a certain
     * type can be forced
     *
     * @param mixed $value The value to convert
     * @param int   $type  The conversion type to use
     * @return string|null
     * @throws Exception\ConverterException
     */
    public static function toLdap($value, $type = self::STANDARD)
    {
        try {
            switch ($type) {
                case self::BOOLEAN:
                    return static::toLdapBoolean($value);
                case self::GENERALIZED_TIME:
                    return static::toLdapDatetime($value);
                default:
                    if (is_string($value)) {
                        return $value;
                    } elseif (is_int($value) || is_float($value)) {
                        return (string) $value;
                    } elseif (is_bool($value)) {
                        return static::toLdapBoolean($value);
                    } elseif (is_object($value)) {
                        if ($value instanceof DateTime) {
                            return static::toLdapDatetime($value);
                        } else {
                            return static::toLdapSerialize($value);
                        }
                    } elseif (is_array($value)) {
                        return static::toLdapSerialize($value);
                    } elseif (is_resource($value) && get_resource_type($value) === 'stream') {
                        return stream_get_contents($value);
                    }

                    return null;
            }
        } catch (PHPException $e) {
            throw new Exception\ConverterException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Converts a date-entity to an LDAP-compatible date-string
     *
     * The date-entity <var>$date</var> can be either a timestamp, a
     * DateTime Object, a string that is parseable by strtotime().
     *
     * @param int|string|DateTime $date  The date-entity
     * @param  bool                 $asUtc Whether to return the LDAP-compatible date-string as UTC or as local value
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    public static function toLdapDateTime($date, $asUtc = true)
    {
        if (! $date instanceof DateTime) {
            if (is_int($date)) {
                $date = new DateTime('@' . $date);
                $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
            } elseif (is_string($date)) {
                $date = new DateTime($date);
            } else {
                throw new Exception\InvalidArgumentException('Parameter $date is not of the expected type');
            }
        }
        $timezone = $date->format('O');
        if (true === $asUtc) {
            $date->setTimezone(new DateTimeZone('UTC'));
            $timezone = 'Z';
        }
        if ('+0000' === $timezone) {
            $timezone = 'Z';
        }
        return $date->format('YmdHis') . $timezone;
    }

    /**
     * Convert a boolean value to an LDAP-compatible string
     *
     * This converts a boolean value of TRUE, an integer-value of 1 and a
     * case-insensitive string 'true' to an LDAP-compatible 'TRUE'. All other
     * other values are converted to an LDAP-compatible 'FALSE'.
     *
     * @param  bool|int|string $value The boolean value to encode
     * @return string
     */
    public static function toLdapBoolean($value)
    {
        $return = 'FALSE';
        if (! is_scalar($value)) {
            return $return;
        }
        if (true === $value || (is_string($value) && 'true' === strtolower($value)) || 1 === $value) {
            $return = 'TRUE';
        }
        return $return;
    }

    /**
     * Serialize any value for storage in LDAP
     *
     * @param mixed $value The value to serialize
     * @return string
     */
    public static function toLdapSerialize($value)
    {
        return serialize($value);
    }

    /**
     * Convert an LDAP-compatible value to a corresponding PHP-value.
     *
     * By setting the <var>$type</var>-parameter the conversion of a certain
     * type can be forced.
     *
     * @see Converter::STANDARD
     * @see Converter::BOOLEAN
     * @see Converter::GENERALIZED_TIME
     *
     * @param string  $value         The value to convert
     * @param int     $type          The conversion type to use
     * @param  bool $dateTimeAsUtc Return DateTime values in UTC timezone
     * @return mixed
     */
    public static function fromLdap($value, $type = self::STANDARD, $dateTimeAsUtc = true)
    {
        switch ($type) {
            case self::BOOLEAN:
                return static::fromldapBoolean($value);
            case self::GENERALIZED_TIME:
                return static::fromLdapDateTime($value);
            default:
                if (is_numeric($value)) {
                    // prevent numeric values to be treated as date/time
                    return $value;
                } elseif ('TRUE' === $value || 'FALSE' === $value) {
                    return static::fromLdapBoolean($value);
                }
                if (preg_match('/^\d{4}[\d\+\-Z\.]*$/', $value)) {
                    return static::fromLdapDateTime($value, $dateTimeAsUtc);
                }
                try {
                    return static::fromLdapUnserialize($value);
                } catch (Exception\UnexpectedValueException $e) {
                    // Do nothing
                }
                break;
        }

        return $value;
    }

    /**
     * Convert an LDAP-Generalized-Time-entry into a DateTime-Object
     *
     * CAVEAT: The DateTime-Object returned will always be set to UTC-Timezone.
     *
     * @param string  $date  The generalized-Time
     * @param  bool $asUtc Return the DateTime with UTC timezone
     * @return DateTime
     * @throws Exception\InvalidArgumentException If a non-parseable-format is given.
     */
    public static function fromLdapDateTime($date, $asUtc = true)
    {
        $datepart = [];
        if (! preg_match('/^(\d{4})/', $date, $datepart)) {
            throw new Exception\InvalidArgumentException('Invalid date format found');
        }

        if ($datepart[1] < 4) {
            throw new Exception\InvalidArgumentException('Invalid date format found (too short)');
        }

        $time = [
            // The year is mandatory!
            'year'          => $datepart[1],
            'month'         => 1,
            'day'           => 1,
            'hour'          => 0,
            'minute'        => 0,
            'second'        => 0,
            'offdir'        => '+',
            'offsethours'   => 0,
            'offsetminutes' => 0,
        ];

        $length = strlen($date);

        // Check for month.
        if ($length >= 6) {
            $month = substr($date, 4, 2);
            if ($month < 1 || $month > 12) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid month)');
            }
            $time['month'] = $month;
        }

        // Check for day
        if ($length >= 8) {
            $day = substr($date, 6, 2);
            if ($day < 1 || $day > 31) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid day)');
            }
            $time['day'] = $day;
        }

        // Check for Hour
        if ($length >= 10) {
            $hour = substr($date, 8, 2);
            if ($hour < 0 || $hour > 23) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid hour)');
            }
            $time['hour'] = $hour;
        }

        // Check for minute
        if ($length >= 12) {
            $minute = substr($date, 10, 2);
            if ($minute < 0 || $minute > 59) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid minute)');
            }
            $time['minute'] = $minute;
        }

        // Check for seconds
        if ($length >= 14) {
            $second = substr($date, 12, 2);
            if ($second < 0 || $second > 59) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid second)');
            }
            $time['second'] = $second;
        }

        // Set Offset
        $offsetRegEx = '/([Z\-\+])(\d{2}\'?){0,1}(\d{2}\'?){0,1}$/';
        $off         = [];
        if (preg_match($offsetRegEx, $date, $off)) {
            $offset = $off[1];
            if ($offset === '+' || $offset === '-') {
                $time['offdir'] = $offset;
                // we have an offset, so lets calculate it.
                if (isset($off[2])) {
                    $offsetHours = substr($off[2], 0, 2);
                    if ($offsetHours < 0 || $offsetHours > 12) {
                        throw new Exception\InvalidArgumentException('Invalid date format found (invalid offset hour)');
                    }
                    $time['offsethours'] = $offsetHours;
                }
                if (isset($off[3])) {
                    $offsetMinutes = substr($off[3], 0, 2);
                    if ($offsetMinutes < 0 || $offsetMinutes > 59) {
                        throw new Exception\InvalidArgumentException(
                            'Invalid date format found (invalid offset minute)'
                        );
                    }
                    $time['offsetminutes'] = $offsetMinutes;
                }
            }
        }

        // Raw-Data is present, so lets create a DateTime-Object from it.
        $timestring = $time['year'] . '-'
                      . str_pad($time['month'], 2, '0', STR_PAD_LEFT) . '-'
                      . str_pad($time['day'], 2, '0', STR_PAD_LEFT) . ' '
                      . str_pad($time['hour'], 2, '0', STR_PAD_LEFT) . ':'
                      . str_pad($time['minute'], 2, '0', STR_PAD_LEFT) . ':'
                      . str_pad($time['second'], 2, '0', STR_PAD_LEFT)
                      . $time['offdir']
                      . str_pad($time['offsethours'], 2, '0', STR_PAD_LEFT)
                      . str_pad($time['offsetminutes'], 2, '0', STR_PAD_LEFT);
        try {
            $date = new DateTime($timestring);
        } catch (PHPException $e) {
            throw new Exception\InvalidArgumentException(
                'Invalid date format found',
                0,
                $e
            );
        }
        if ($asUtc) {
            $date->setTimezone(new DateTimeZone('UTC'));
        }
        return $date;
    }

    /**
     * Convert an LDAP-compatible boolean value into a PHP-compatible one
     *
     * @param string $value The value to convert
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public static function fromLdapBoolean($value)
    {
        if ('TRUE' === $value) {
            return true;
        } elseif ('FALSE' === $value) {
            return false;
        } else {
            throw new Exception\InvalidArgumentException('The given value is not a boolean value');
        }
    }

    /**
     * Unserialize a serialized value to return the corresponding object
     *
     * @param string $value The value to convert
     * @return mixed
     * @throws Exception\UnexpectedValueException
     */
    public static function fromLdapUnserialize($value)
    {
        ErrorHandler::start(E_NOTICE);
        $v = unserialize($value);
        ErrorHandler::stop();

        if (false === $v && $value !== 'b:0;') {
            throw new Exception\UnexpectedValueException('The given value could not be unserialized');
        }
        return $v;
    }
}
