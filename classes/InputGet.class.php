<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class InputGet extends Input
{
    /**
     * initialize the InputGet object with the superglobal $_GET
     *
     * @param int $superglobals
     */
    function __construct(int $superglobals = Input::INPUT_GET)
    {
        parent::__construct($superglobals);
    }

    /**
     * Die Funktion liefert eine Url-konforme Parameter Liste (auch query genannt). In der Standardeinstellung werden Objekte und Arrays uebersprungen.
     *
     * @return string Query (Url-konforme Parameter Liste)
     */
    function getQuery($query = '', $ampersand = '&')
    {
        $session_name = session_name();
        foreach($this->vars as $key => $value) {
            if(isset($this->vars[$key])) {
                if(is_object($this->vars[$key])) {
                    continue;
                }
                if($key == $session_name) {
                    continue;
                }

                // Array als Value
                if(is_array($value)) {
                    foreach($value as $val) {
                        if(!empty($query)) {
                            $query .= $ampersand;
                        }
                        $query .= urlencode($key . '[]') . '=' . urlencode($val);
                    }
                    continue;
                }

                if(!empty($query)) {
                    $query .= $ampersand;
                }
                $query .= $key . '=' . urlencode($value);
            }
        }
        return $query;
    }
}