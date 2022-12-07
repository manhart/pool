<?php
/*
 * g7system.local
 *
 * TranslationProvider.class.php created at 30.11.22, 13:21
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\classes\translator;

use Exception;

Interface TranslationProvider
{
    public const NotImplemented = -2;
    public const Error = -1;
    public const OK = 0;
    public const TranslationKnownMissing= 1;
    public const TranslationInadequate = 2;
    public const TranslationOmitted= 3;
    public const TranslationNotExistent= 4;


    function getLang():string;
    function getLocale():string;
    function getResult(): ?Translation;

    /**
     * @param string|null $key
     * @return int status [NotImplemented, Error, OK, TranslationNotExistent]
     */
    function increaseMissCounter(?string $key):int;

    /**
     * @param string $key
     * @return int status[Error, OK,TranslationKnownMissing, TranslationInadequate, TranslationOmitted, TranslationNotExistent]
     */
    function query(string $key):int;

    /**
     * @param int $status
     * @param string|null $value
     * @param string $key
     * @return int status[NotImplemented, Error, OK]
     */
    function alterTranslation(int $status, ?string $value, string $key):int;
    function getError(): ?Exception;
    function clearError():void;

}