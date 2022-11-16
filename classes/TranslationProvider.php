<?php
/*
 * g7system.local
 *
 * TranslationProvider.php created at 15.11.22, 11:44
 *
 * @author p.lehfeld <p.lehfeld@groupfunction 7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\classes;

Interface TranslationProvider
{


    function getLang():string;
    function getResult():?string;

    /**
     * @param string|null $key
     * @return int status [-1 = nicht implementiert, 0 = OK, 1 = Fehlschlag, 2 = key fehlt, 3 = Translation nicht vorhanden]
     */
    function increaseMissCounter(?string $key=null):int;

    /**
     * @param string $key
     * @return int status[0 = OK, 1 = Fehlt, 2 = Fehlerhaft, 3 = Ausgelassen]
     */
    function query(string $key):int;

    function addTranslation(int $status, ?string $value, ?string $key=null):int;
    function getErrorMessage():string;
    function clearError():void;

}