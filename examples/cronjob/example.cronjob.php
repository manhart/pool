#!/usr/bin/php
<?php
/* PHP gets an incredible amount of time */
set_time_limit(0);
/* the implicit flush is turned on, so you can see immediately the output */
ob_implicit_flush();
/* We want to see what the PHP interpreter has to say. */
error_reporting(E_ALL);
/* Timezone */
date_default_timezone_set('Europe/Berlin');

$isConsole = (php_sapi_name() == 'cli');
define('IS_CONSOLE', $isConsole);

if (IS_CONSOLE) {
    $_SERVER['DOCUMENT_ROOT'] = '/var/www/'; // set your document root here
    $_SERVER['SERVER_NAME'] = php_uname('n');;
    $new_line = chr(10);
    
    $options = getopt('v', array('hello:', 'debug'));
}
else {
    $new_line = '<br>';
    
    $options = $_REQUEST;
}
define('NEW_LINE', $new_line);

$pid = getmypid();

// evaluate params
define('VERBOSE', (array_key_exists('v', $options)));
define('DEBUG', (array_key_exists('debug', $options)));
$hello = (array_key_exists('hello', $options)) ? $options['hello'] : '';
if(empty($hello)) die('Required argument --hello=<your name>'.NEW_LINE);

include_once($_SERVER['DOCUMENT_ROOT'].'/config.inc.php');
include_once(DIR_DAOS_ROOT.'/database.inc.php');

/*************************************************************
 * - POOL - POOL - POOL - POOL - POOL - POOL - POOL - POOL - *
 *************************************************************/
# Include_path auf die Library setzen
$old_include_path = ini_get('include_path');

if (!ini_set('include_path', DIR_BASELIB_ROOT)) {
    die('Fatal Error. Cannot set include_path in php.ini!');
}

# load base library:
require_once('pool.lib.php');
// require_once(addEndingSlash(DIR_BASELIB_ROOT).addEndingSlash(PWD_TILL_INCLUDES).'FTP.inc.php');
// require_once('includes/Utils.inc.php');
ini_set('include_path', $old_include_path);

// maybe some other libs
// require_once(DIR_DOCUMENT_ROOT.'vendor/autoload.php');

// logs
$jobName = remove_extension(basename(__FILE__));
$logFolder = DIR_DATA_ROOT.'/logs'; // maybe add an app folder
$errorLogfile = $logFolder.'/'.$jobName.'-error.log';

/*************************************************************
 *                Helper Function SECTION                    *
 *************************************************************/
function stderr($text)
{
    if(IS_CONSOLE) {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, date('d.m.Y H:i:s').' '.$text.NEW_LINE);
        fclose($stderr);
    }
    else {
        echo '<span style="color:red; font-weight: bold">'.$text.NEW_LINE.'</span>';
    }
    
    // global $ErrorLog;
    // maybe a global $ErrorLog->addLine($text);
}

function stdout($text)
{
    if(IS_CONSOLE) {
        $stdout = fopen('php://stdout', 'w');
        fwrite($stdout, date('d.m.Y H:i:s').' '.$text.NEW_LINE);
        fclose($stdout);
    }
    else {
        echo '<span style="color:black">'.$text.NEW_LINE.'</span>';
    }
}

/*************************************************************
                        CODE SECTION                         *
 *************************************************************/
//$Packet = array(
//    'host' => MYSQL_HOST,
//    'database' => constant('DB_DATABSE'),
//    'charset' => 'utf8'
//);
//
//$MySQL_db = &DataInterface::createDataInterface(DATAINTERFACE_MYSQL, $Packet);
//
//$Weblication = new Weblication($jobName);
//$Weblication->addDataInterface($MySQL_db);


//mkdirs($logFolder);
//chown($logFolder, APACHE_USER);

//$ErrorLog = new Log();
//$ErrorLog->open($errorLogfile);


if(DEBUG) {
    stdout(str_repeat('*', 100));
    stdout('Settings');
    stdout('ErrorLogFile: '.$errorLogfile);
    stdout('');
    stdout('Start the program '.$pid);
    stdout('Got hello value: '.$hello);
}

echo 'Hello '.$hello.'!'.NEW_LINE;
echo 'Nice to meet you.'.NEW_LINE;
if(VERBOSE) {
    echo 'The weather is nice.'.NEW_LINE;
}

if(DEBUG) {
    stdout('Program '.$pid.' terminated');
}