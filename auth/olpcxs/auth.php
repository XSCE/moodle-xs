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
	die('Direct access to this script is forbidden.');	  ///  It must be included from a Moodle page
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
	  global $USER;
	  global $SESSION;

	  if ( (isguestuser() || !isloggedin())
	       && !empty($_COOKIE['xoid'])) {
	    // on 1.9 and earlier, $_COOKIE is badly mangled
	    // by addslashes_deep() so...
	    $xoid = json_decode(stripslashes($_COOKIE['xoid']));

	    $user = get_record('user', 'idnumber', addslashes($xoid->pkey_hash));
	    if (empty($user)) { // maybe the account is new!
	      $this->idmgr_sync(true);
	      $user = get_record('user', 'idnumber', addslashes($xoid->pkey_hash));
	      if (empty($user)) {
		// We failed to login the user
		// even though we saw a cookie.
		// Probably the client side is confused.
		// Log the problem, let things continue...
		trigger_error('auth/olpcxs: user with pkey_hash ' . $xoid->pkey_hash . ' was not found after idmgr_sync()');
		return true;
	      }
	    }

	    //
	    // we have the user acct, complete login dance now
	    //

	    // is this our first user to ever login?
	    $first = get_field_sql("SELECT COUNT(id)
				    FROM {$CFG->prefix}user
			            WHERE auth='olpcxs' AND lastlogin > 0 ");
	    if ((int)$first > 0) {
	      $first = true;
	    } else {
	      $first = false;
	    }
	    if ($first) {
	      $ccrole	 = get_record('role', 'shortname', 'coursecreator');
	      $etrole	 = get_record('role', 'shortname', 'editingteacher');
	      $sitectx = get_context_instance(CONTEXT_SYSTEM);
	      $sitecoursectx = get_record('context',
					  'contextlevel', CONTEXT_COURSE,
					  'instanceid', SITEID);

	      role_assign($ccrole->id, $user->id, 0, $sitectx->id);
	      role_assign($ccrole->id, $user->id, 0, $sitecoursectx->id);
	      role_assign($etrole->id, $user->id, 0, $sitecoursectx->id);

	      // tweak coursecreator to be able to assign course-creator roles systemwide...
	      assign_capability('moodle/role:assign', CAP_ALLOW, $ccrole->id, $sitectx->id, false);
	      if (!get_record('role_allow_assign', 'roleid', $ccrole->id, 'allowassign', $ccrole->id)) {
		allow_assign($ccrole->id,$ccrole->id);
	      }
	    }

	    // icon for the user - we cannot do this in
	    // create_update_user() because it's only
	    // in the cookie, and not in idmgr. grumble...
	    // .. expecting something like "#FF8F00,#00A0FF"
	    // rough regex
	    if (!empty($xoid->color) && preg_match('/#[A-F0-9]{1,6},#[A-F0-9]{1,6}/', $xoid->color)) {
	      $iconpref = get_user_preferences('xoicon', $user->id);
	      if (empty($iconpref) || $iconpref !== $xoid->color) {
		set_user_preference('xoicon',$xoid->color, $user->id);
	      }
	    }

	    // complete login
	    $USER = get_complete_user_data('id', $user->id);
	    complete_user_login($USER);

	    // redirect
	    if (isset($SESSION->wantsurl) and (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
	      $urltogo = $SESSION->wantsurl;    /// Because it's an address in this site
	      unset($SESSION->wantsurl);
            } else {
                // no wantsurl stored or external - go to homepage
                $urltogo = $CFG->wwwroot.'/';
                unset($SESSION->wantsurl);
            }
	    redirect($urltogo);
	  }
	}

	function create_update_user($extuser) {
	  global $CFG;
	  global $XS_FQDN;

	  /// Note: updates are limited to 
	  /// firstname - nickname from XO
	  /// idnumber  - pkey_hash from XO

	  // get the local record for the remote user
	  $user = get_record('user', 'username', addslashes($extuser['serial']));

	  $pkey_hash = $this->compute_pkey_hash($extuser['pubkey']);

	  if (empty($user)) {
	    // add the user to the database if necessary
	    $user = new StdClass;

	    // Harcoded fields -
	    $user->lastname     = ' ';
	    $user->auth		= 'olpcxs';
	    $user->mnethostid   = $CFG->mnet_localhost_id;
	    $user->lang         = $CFG->lang;
	    $user->confirmed	= 1;
	    $user->picture      = 1;

	    // username & fqdn  won't change over the lifetime of the account
	    $user->username     = addslashes($extuser['serial']);
	    $user->email        = addslashes($extuser['serial']) . '@' . $XS_FQDN;

	    // we'll accept changes in nickname, and the pkey_hash
	    $user->firstname    = addslashes($extuser['nickname']);
	    $user->idnumber     = addslashes($pkey_hash);
	    $user->modified	= time();

	    $uid = insert_record('user', addslashes_object($user));

	  } else {

	    $uid = $user->id;

	    if ($user->firstname !== $extuser['nickname']) {
	      // use set_field to avoid having to re-addslashes on every field
	      // and re-update the whole record
	      set_field('user', 'firstname', addslashes($extuser['nickname']), 'id', $uid);
	    }
	    
	    if ($user->idnumber !== $pkey_hash) {
	      set_field('user', 'idnumber', addslashes($pkey_hash), 'id', $uid);
	    }
	  }
	  return $uid;
	}

	function compute_pkey_hash($pkey) {
	  return sha1($pkey);
	}

	// read new accounts in from idmgr
	// if $fast=true then use a simple strategy
	// that only creates new accounts
	function idmgr_sync($fast=false) {
	  global $CFG;

	  if (empty($CFG->olpcxsdb) || !file_exists($CFG->olpcxsdb)) {
	    return false;
	  }
	  $dbh = new PDO('sqlite:' . $CFG->olpcxsdb);

	  //
	  // new accounts to create...
	  //
	  $sql = 'SELECT *
                  FROM laptops';
	  $tslastrun = get_config('enrol/olpcxs', 'idmgr_sync_ts');
	  $tsnow   = time();
	  if ($fast && !empty($tslastrun)) {
	    $sql .= " WHERE lastmodified >= '"
	      . gmdate('Y-m-d H:i:s', $tslastrun) ."'";
	  }

	  $rs = $dbh->query($sql);
	  foreach ($rs as $idmgruser) {
	    $this->create_update_user($idmgruser);
	  }

	  set_config('idmgr_sync_ts', $tsnow, 'enrol/olpcxs');
	  unset($dbh); unset($rs);

	  //
	  // TODO - consider cleanup account scenario...?
	  //
	}


	function cron() {
	  $this->idmgr_sync();
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
