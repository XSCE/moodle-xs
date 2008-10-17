<?php

/**
 * @author Martin Langhoff
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodle multiauth
 *
 * Authentication Plugin: OLPC XS Authentication
 *
 * Standard authentication function.
 *
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/authlib.php');

/**
 * Email authentication plugin.
 */
class auth_plugin_olpcxs extends auth_plugin_base {

    /**
     * Constructor.
     */
    function auth_plugin_olpcxs() {
        $this->authtype = 'olpcxs';
        $this->config = get_config('auth/olpcxs');
    }

    function loginpage_hook(){
      global $CFG;
      if (isguestuser() || !isloggedin()) {
	if (empty($_GET['showlogin'])) {
	  redirect("{$CFG->wwwroot}/auth/olpcxs/who.php");
	}
      }
    }

    function getorcreate_user($extuser) {
      global $CFG;
      $firsttime = false;

      // get the local record for the remote user
      $localuser = get_record('user', 'username', addslashes($extuser['serial']));

      if (!empty($localuser)) {
	return $localuser;
      }

      // add the user to the database if necessary
      $user = new StdClass;
      $user->username = addslashes($extuser['serial']);
      $user->firstname = addslashes($extuser['nickname']);
      $user->lastname = ' ';
      $user->email = addslashes($extuser['nickname']) . '@xs.laptop.org';
      $user->modified   = time();
      $user->confirmed  = 1;
      $user->auth       = 'olpcxs';
      $user->mnethostid = $CFG->mnet_localhost_id;
      $user->lang = $CFG->lang;
      $uid = insert_record('user', addslashes_object($user));

      if (! $localuser = get_record('user', 'id', $uid)) {
	error("Failed to get new user record");
      }

      return $localuser;
    }

    function user_login ($username, $password) {
        return false;
    }

    function is_internal() {
        return false;
    }

    function can_change_password() {
        return false;
    }

    function can_reset_password() {
        return false;
    }


}

?>
