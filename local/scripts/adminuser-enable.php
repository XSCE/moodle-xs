<?php 
//Intruders OUT!!!
if (!empty($_SERVER['GATEWAY_INTERFACE'])){
    error_log(__FILE__ . ' should not be called from apache!');
    echo 'This script is not accessible from the webserver';
    exit;
}
define('FULLME', 'cron');
$nomoodlecookie = true;
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

if (set_field('user',
              'deleted', 0,
              'username', 'admin',
              'auth', 'manual')) {
    exit(0);
} else { 
    exit(1);
}
?>