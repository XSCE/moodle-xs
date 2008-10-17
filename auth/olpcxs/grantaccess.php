<?php

/**
 * @author Martin Langhoff
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodle multiauth
 *
 * Authentication Plugin: OLPC XS Authentication
 *
 */

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';

if (!is_enabled_auth('olpcxs')) {
    error('olpcxs is disabled');
}

$dbh = new PDO('sqlite:' . $CFG->olpcxsdb);


// grab the GET params
$nickname      = required_param('nickname', PARAM_ALPHANUM);
$sth = $dbh->query('SELECT * from laptops WHERE nickname=?');
$sth->execute(array($nickname));
$extuser = $sth->fetch();

// confirm setup the session
$auth = get_auth_plugin('olpcxs');
$localuser = $auth->getorcreate_user($extuser);

// log in
$USER = get_complete_user_data('id', $localuser->id);
complete_user_login($USER);

// redirect
redirect($CFG->wwwroot);

?>
