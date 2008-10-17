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

      $first = get_field_sql("SELECT COUNT(id)
                              FROM {$CFG->prefix}user
                              WHERE auth='olpcxs'");
      if ((int)$first > 0) {
	$first = true;
      } else {
	$first = false;
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

      if ($first) {
	$ccrole  = get_record('role', 'shortname', 'coursecreator');
	$etrole  = get_record('role', 'shortname', 'editingteacher');
	$sitectx = get_context_instance(CONTEXT_SYSTEM);
	$sitecoursectx = get_record('context',
				    'contextlevel', CONTEXT_COURSE,
				    'instanceid', SITEID);

	role_assign($ccrole->id, $localuser->id, 0, $sitectx->id);
	role_assign($ccrole->id, $localuser->id, 0, $sitecoursectx->id);
	role_assign($etrole->id, $localuser->id, 0, $sitecoursectx->id);

	// tweak coursecreator to be able to assign course-creator roles systemwide...
	assign_capability('moodle/role:assign', CAP_ALLOW, $ccrole->id, $sitectx->id, false);
	if (!get_record('role_allow_assign', 'roleid', $ccrole->id, 'allowassign', $ccrole->id)) {
	  allow_assign($ccrole->id,$ccrole->id);
	}
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
