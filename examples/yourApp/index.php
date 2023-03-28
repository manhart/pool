<?php
// configs.inc.php: kann irgendwo liegen / can be placed anywhere!
use pool\classes\Core\Weblication;

require_once '../../src/configs/config.inc.php'; // <-- innerhalb config.inc.php die Pfade anpassen!
require_once DIR_DAOS_ROOT.'/database.inc.php';

if(!ini_set('include_path', DIR_POOL_ROOT)) {
    die('Fatal Error. Cannot set include path in index.php');
}
require_once 'pool.lib.php';
ini_set('include_path', '.');


define('APP_CHARSET', 'utf-8');

$Weblication = &Singleton('Weblication');
if($Weblication instanceof Weblication) {
    $Weblication->setPathBaselib(DIR_POOL_ROOT);
    $Weblication->setRelativePathBaselib('../../src'); // js, images from pool
    $Weblication->setTitle('This is yourApp');
    $Weblication->setCharset(APP_CHARSET);

    $MySQL_Packet = array(
        'host' => MYSQL_HOST,
        'database' => constant('DB_TESTING')
        /* 'charset' => MYSQL_CHARSET */
    );
    $MySQLi_db = DataInterface::createDataInterface($MySQL_Packet, MySQLi_Interface::class);
    $Weblication->addDataInterface($MySQLi_db);

    $Session = $Weblication->startPHPSession();

    $lang = $Session->getVar('lang');
    if(!$lang) {
        $lang = 'de';
    }

    $Weblication->setSchema('index');
    $Weblication->setSkin('default');
    $Weblication->setLanguage($lang);

    $Weblication->setup([
        'application.launchModule' => 'GUI_Frame'
    ]);
    $Weblication->render();
}