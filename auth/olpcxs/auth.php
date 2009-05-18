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
require_once($CFG->libdir.'/ejabberdctl.php');

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

            // Check if this account should be aliased to a different one...
            $user_alias = get_record('user_preferences', 'name', 'olpcxs_alias',
                                     'value', "$user->id");
            if ($user_alias) {
                $olduser = $user;
                $user = get_record('user', 'id', $user_alias->userid);
                add_to_log(SITEID, 'user', 'login_alias', "../user/view.php?user={$user->id}", "ids:{$olduser->id} -> {$user->id}");
            }

            //
            // we have the user acct, complete login dance now
            //

            // is this our first user to ever login?
            $first = get_field_sql("SELECT COUNT(id)
				    FROM {$CFG->prefix}user
			            WHERE auth='olpcxs' AND lastlogin > 0 AND id != {$user->id}");
            if ((int)$first > 0) {
                $first = false;
            } else {
                $first = true;
            }
            $sitectx   = get_context_instance(CONTEXT_SYSTEM);
            if ($first) {
                $ccrole	 = get_record('role', 'shortname', 'coursecreator');
                $etrole	 = get_record('role', 'shortname', 'editingteacher');
                $sitecoursectx = get_record('context',
                                            'contextlevel', CONTEXT_COURSE,
                                            'instanceid', SITEID);

                role_assign($ccrole->id, $user->id, 0, $sitectx->id);
                role_assign($ccrole->id, $user->id, 0, $sitecoursectx->id);
                role_assign($etrole->id, $user->id, 0, $sitecoursectx->id);
                $this->fixup_roles();
            } else {
                // not first, if its a coursecreator
                // ensure the role is healthy
                $roles = $this->get_user_roles_in_context($user->id, $sitectx);
                if (in_array('coursecreator', $roles)) {
                    $this->fixup_roles();
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

            add_to_log(SITEID, 'user', 'login', "../user/view.php?user={$user->id}", 'autologin');

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

    function fixup_roles() {
        
        $sitectx   = get_context_instance(CONTEXT_SYSTEM);

        // course creator
        $ccrole	 = get_record('role', 'shortname', 'coursecreator');

        $ccaps = array('moodle/role:assign',
                        'moodle/user:viewuseractivitiesreport',
                        'moodle/user:editprofile',
                        'moodle/user:update',
                        'moodle/user:delete',
                        'moodle/user:create' 
                        );
        foreach ($ccaps as $cap) {
            assign_capability($cap, CAP_ALLOW, $ccrole->id, $sitectx->id, false);
        }
        // tweak coursecreator to be able to assign course-creator roles systemwide...
        if (!get_record('role_allow_assign', 'roleid', $ccrole->id, 'allowassign', $ccrole->id)) {
            allow_assign($ccrole->id,$ccrole->id);
        }
    }

    // inspired by the function w same name in accesslib,
    // but returning a more useful array of role strings-
    function get_user_roles_in_context($userid, $context, $view=true){
        global $CFG, $USER;

        $rolestring = '';
        $SQL = 'select ra.id,r.shortname from '.$CFG->prefix.'role_assignments ra, '.$CFG->prefix.'role r where ra.userid='.$userid.' and ra.contextid='.$context->id.' and ra.roleid = r.id';
        $rolenames = array();
        if ($roles = get_records_sql($SQL)) {
            foreach ($roles as $userrole) {
                $rolenames[] = $userrole->shortname;
            }
        }
        return $rolenames;
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
            $user->username     = $extuser['serial'];
            $user->email        = $extuser['serial']. '@' . $XS_FQDN;

            // we'll accept changes in nickname, and the pkey_hash
            $user->firstname    = $extuser['nickname'];
            $user->idnumber     = $pkey_hash;
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

    function ejabberd_sync() {
        global $CFG;
        global $XS_FQDN;

        $tslastrun = get_config('enrol/olpcxs', 'ejabberd_sync_ts', 0);
        $tsnow   = time();
        
        // run first time, run roughly once a day
        if ($tslastrun !== 0 && ($tsnow - $tslastrun) < 1 * 24 * 3600) {
            // run everytime - until we make it event-driven
            // return true;
        }

        $ej = new ejabberdctl;
        if (!isset($XS_FQDN)) {
            mdie('$XS_FQDN is not set');
        }
        $ej->set_vhost('schoolserver.' . $XS_FQDN);
        $ejsrg = $ej->srg_list_groups();
        $mcourses = get_recordset('course', '','', '', 'id,shortname,fullname');

        //
        $aliasessql = "SELECT p.userid as id, p.value, u.idnumber
                       FROM {$CFG->prefix}user_preferences p
                       JOIN {$CFG->prefix}user u ON p.value::int = u.id
                       WHERE p.name='olpcxs_alias' ";
        $aliases = get_records_sql($aliasessql);

        while ($mc = rs_fetch_next_record($mcourses)) {

            // Skip sitecourse
            if ($mc->id === SITEID) {
                continue;
            }

            // array_search() returns int on stack-like arrays
            $pos = array_search($mc->shortname, $ejsrg);
            if (is_int($pos)) {
                array_splice($ejsrg, $pos, 1);
                $info = $ej->srg_get_info($mc->shortname);
                if ($info === null) {
                    mdie("srg_get_info failed");
                }
                if (empty($info['members'])) {
                    $ejparticipants = array();
                } else {
                    $ejparticipants = $info['members'];
                }
            } else {
                $ej->srg_create($mc->shortname, $mc->fullname);
                $ejparticipants = array();
            }

            // Add missing participants, remove old participants!
            $ctx = get_context_instance(CONTEXT_COURSE, $mc->id);
            $users = get_users_by_capability($ctx, 'moodle/local:jabberpresence',
                                             'u.id,u.username,u.idnumber,u.auth', 
                                             '', '', '', '', '', false);
            // Note: ejabberdctl reports course members as
            // user@domain -- but expects the 2 params separate
            // when called - 
            if (is_array($users)) {
                foreach ($users as $user) {

                    if ($user->auth !== 'olpcxs') {
                        continue;
                    }

                    // map to aliases...
                    $username = $user->idnumber;
                    if (isset($aliases[ $user->id ])) {
                        $alias = $aliases[ $user->id ];
                        $username = $alias->idnumber;
                    }

                    // array_search() returns int on stack-like arrays
                    $pos = array_search($username . '@' . 'schoolserver.' . $XS_FQDN, $ejparticipants, true);
                    if (is_int($pos)) {
                        array_splice($ejparticipants, $pos, 1);
                    } else {
                        // add to srg
                        $ej->srg_user_add($mc->shortname, $username);
                    }
                }
            }
            foreach ($ejparticipants as $ejp) {
                // as mentioned above, ejabberctl reports user@host but wants the
                // params separated.
                if (preg_match('/^(\w+)@/', $ejp, $match)) {
                    $ej->srg_user_del($mc->shortname, $match[1]);
                }
            }
        }
        foreach ($ejsrg as $ejg) {
            $ej->srg_delete($ejg);
        }
        set_config('ejabberd_sync_ts', $tsnow, 'enrol/olpcxs');
    }

    // Check and if necessary fix the Online group
    function ejabberd_checkfixonline() {
        global $XS_FQDN;

        $ej = new ejabberdctl;
        if (!isset($XS_FQDN)) {
            mdie('$XS_FQDN is not set');
        }
        $ej->set_vhost('schoolserver.' . $XS_FQDN);

        // In this mode, remove other SRGs
        $ejsrg = $ej->srg_list_groups();
        $seenonlinesrg = false;
        foreach ($ejsrg as $ejg) {
            if ($ejg === 'Online') {
                $seenonlinesrg = true;
                continue;
            }
            $ej->srg_delete($ejg);
        }

        // Check the Online SRG is set and configured
        // correctly
        if (!$seenonlinesrg) {
            $ej->srg_create('Online', 'Online Group - created from moodle');
        } else {
            $info = $ej->srg_get_info('Online');
            if ($info['online_users']==='true') {
                return true;
            }
        }
        $ej->srg_user_add('Online', '@online@');

    }

	function cron() {
        global $CFG;
        $this->idmgr_sync();

        if (!empty($CFG->presencebycourse)) {
            $this->ejabberd_sync();
        } else {
            $this->ejabberd_checkfixonline();
        }
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
