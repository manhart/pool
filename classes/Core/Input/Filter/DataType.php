<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core\Input\Filter;

use Closure;
use InvalidArgumentException;

use function chr;
use function filter_var;
use function htmlentities;
use function is_array;
use function is_numeric;
use function is_string;
use function strcspn;
use function strlen;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_URL;

/**
 * Class DataType
 *
 * @package pool\classes\Core\Input
 * @since 2023-07-26
 */
enum DataType
{
    case INT; // value is an integer and can be converted to an integer
    case FLOAT; // value is a float and can be converted to a float
    case ANY; // everything is allowed
    case TEXT; // Default. Disallow ASCII <32 except 09, 10, 13 and is converted to string
    // case BIDI_TEXT; // bidirectional text
    case BOOL; // = 0, 1, '0', '1', 'true', 'false', 'on', 'off', 'yes', 'no'
    case ARRAY; // = is_array
    case JSON; // = json_validate
    case EMAIL; // = FILTER_VALIDATE_EMAIL
    case URL; // = FILTER_VALIDATE_URL
    case NO_HTML; // encode html with html entities
    case ALPHANUMERIC;// only a-z, A-Z, 0-9, _
    case ALPHANUMERIC_SPACE; // only a-z, A-Z, 0-9, _, space
    case ALPHANUMERIC_SPACE_PUNCTUATION; // only a-z, A-Z, 0-9, _, space, ., ,;:!?()-+/&
    case HTML_SAFE; // = FILTER_SANITIZE_FULL_SPECIAL_CHARS

    public static function getFilter(DataType $dataType): Closure
    {
        $alphaNum = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_';
        $filter = match ($dataType) {
            self::INT => static fn($value) => is_numeric($value) && (!is_string($value) || !str_contains($value, '.')) ? (int)$value :
                throw new InvalidArgumentException('Value is not an integer'),
            self::FLOAT => static fn($value) => is_numeric($value) ? (float)$value : throw new InvalidArgumentException('Value is not numeric.'),
            self::ANY => static fn($value) => $value,
            self::TEXT => static function ($value) {
                if ($value !== '' && strcspn($value, "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0E\x0F\x10\x11\x12\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F") !==
                    strlen($value)) {
                    throw new InvalidArgumentException('Value is not text.');
                }
                return (string)$value;
            },
            self::BOOL => static fn($value) => match ($value) {
                true, 1, '1', 'true', 'on', 'yes' => true,
                false, 0, '0', 'false', 'off', 'no' => false,
                default => throw new InvalidArgumentException('Value is not boolean.')
            },
            self::ARRAY => static fn($value) => is_array($value) ? $value :
                throw new InvalidArgumentException('Value is not an array.'),
            self::JSON => static fn($value) => json_validate($value) ? $value :
                throw new InvalidArgumentException('Value is not valid JSON.'),
            self::EMAIL => static fn($value) => is_string(filter_var($value, FILTER_VALIDATE_EMAIL)) ? $value :
                throw new InvalidArgumentException('Value is not a valid email address.'),
            self::URL => static fn($value) => is_string(filter_var($value, FILTER_VALIDATE_URL)) ? $value :
                throw new InvalidArgumentException('Value is not a valid URL.'),
            self::NO_HTML => static fn($value) => htmlentities($value, ENT_QUOTES | ENT_SUBSTITUTE),
            self::ALPHANUMERIC => static fn($value) => strcspn($value, $alphaNum) === 0 ? $value :
                throw new InvalidArgumentException('Value is not alphanumeric.'),
            self::ALPHANUMERIC_SPACE => static fn($value) => strcspn($value, "$alphaNum ") === 0 ? $value :
                throw new InvalidArgumentException('Value is not alphanumeric with spaces.'),
            self::ALPHANUMERIC_SPACE_PUNCTUATION => static fn($value) => strcspn($value, "$alphaNum .,;:!?()-+/&") === 0 ? $value :
                throw new InvalidArgumentException('Value is not alphanumeric with spaces and punctuation.'),
            self::HTML_SAFE => static fn($value) => is_string(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS)) ? $value :
                throw new InvalidArgumentException('Value is not a valid string or could not be sanitized for HTML safety.'),
        };
        return match ($dataType) {
            self::ANY => $filter,
            default => static function ($value) use ($filter) {
                return $value === chr(0) ? null : $filter($value);
            }
        };
    }
}
