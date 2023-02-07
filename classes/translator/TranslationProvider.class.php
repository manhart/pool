<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    function getFactory(): TranslationProviderFactory;

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
    function getAllTranslations():array;

}