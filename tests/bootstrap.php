<?php
declare(strict_types = 1);

require_once __DIR__.'/../pool.lib.php';

if (!defined('IS_DEVELOP')) {
    define('IS_DEVELOP', true);
}

if (!defined('IS_STAGING')) {
    define('IS_STAGING', false);
}

if (!defined('IS_PRODUCTION')) {
    define('IS_PRODUCTION', false);
}

if (!defined('IS_TESTSERVER')) {
    define('IS_TESTSERVER', true);
}

if (!defined('DIR_DAOS_ROOT')) {
    define('DIR_DAOS_ROOT', DIR_DOCUMENT_ROOT.'/daos');
}

if (!defined('DBACCESSFILE')) {
    define('DBACCESSFILE', DIR_DOCUMENT_ROOT.'/dbaccess.inc.php');
}
