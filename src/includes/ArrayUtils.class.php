<?php
/*
 * g7system.local
 *
 * ArrayUtils.class.php created at 26.09.22, 12:36
 *
 * @author A.Manhart <A.Manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */


final class ArrayUtils
{
    /**
     * @param array $array
     * @return bool
     */
    static function isAssoc(array $array): boolean
    {
        $keys = array_keys($array);
        return $keys !== array_keys($keys);
    }
}