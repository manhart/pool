<?php
// private
$mysql_global_auth = array(
    'username' => 'myapp',
    'password' => 'myapp-secret'
);


$mysql_auth = array(
    'localhost' => array(
        DB_TESTING => $mysql_global_auth
    )
);