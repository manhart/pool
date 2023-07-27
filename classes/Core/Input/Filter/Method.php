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

enum Method
{
    case EACH; // applies to each value only applicable to arrays
    case ALT; // alternate data type filter
    case DEF; // default value
}
/**
 * Examples
 * protected array $inputFilter = [
 *  'hasTransportInsurance' => [[
 *      DataType::BOOL,
 *          [DataType::ARRAY,
 *          [DataType::INT, DataType::BOOL]]],
 *      false],
 *  'shipment' => [
 *      DataType::ARRAY,
 *          [Method::EACH, DataType::INT,
 *              [Method::ALT, DataType::ALPHANUMERIC],
 *              [Method::DEF, 'invalid']],
 *          [Method::ALT, DataType::BOOL]// each, follow, alt
 *      ],
 *  'caption' => [DataType::TEXT, ''],
 *  'beispiel1' => [DataType::JSON, '{}'],
 *  'beispiel2' => [DataType::ANY],
 * ];
 */