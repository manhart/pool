<?php
/**
* Die Klasse vermeidet, dass bestimmte hosts auf eine Seite verlinken.
* Funktioniert nur, wenn die Variable $_SERVER['HTTP_REFERER'] gefüllt
* ist - nicht bei allen Browsern der Fall !!!
*
* @author Christian Schmidseder <c.schmidseder@gmx.de>
* @access public
* @package pool
*/
class LinkBlocker extends PoolObject {

    /**
     * Server
     *
     * @access private
     * @var string
     */
    var $host2Block;

    /**
     * Kontruktor: Der Host, dessen Links geblockt werden sollen, wird als
     * Parameter �bergeben (z.B. www.boeseSeite.de)
     *
     * @param string $host2Block
     * @return LinkBlocker
     */
    function __construct($host2Block)
    {
        $this -> host2Block = $host2Block;
    }

    /**
     * Bewirkt das Blocken eines Hosts, indem auf den Host selbst
     * zur�ckgeleitet wird.
     *
     */
    function block()
    {
        $http_referer = $_SERVER['HTTP_REFERER'];
        $pieces = split("/", $http_referer);
        $linkHost = $pieces[2];

        if($this -> host2Block == $linkHost) {
            header('location: http://' . $linkHost);
            exit;
        }
    }
}