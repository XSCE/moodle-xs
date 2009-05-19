<?PHP // $Id$

//  Display available backup for a particular user


    require_once(dirname(dirname(__FILE__)) . '/config.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    require_once(dirname(__FILE__) . '/dsbackuplib.php');
    $id        = optional_param('id',     0,      PARAM_INT);   // user id
    $course    = optional_param('course', SITEID, PARAM_INT);   // course id (defaults to Site)
    $snapshot    = optional_param('snapshot', '', PARAM_RAW);     // manual cleanup later
    $restorefile = optional_param('restorefile', '', PARAM_FILE); // filename
    $snapshotlist=optional_param('snapshotlist', 0, PARAM_BOOL);
    $aliasuserid = optional_param('aliasuserid', 0, PARAM_INT);


    if (empty($id)) {         // See your own profile by default
        require_login();
        $id = $USER->id;
    }

    if (! $user = get_record("user", "id", $id) ) {
        error("No such user in this course");
    }

    if (! $course = get_record("course", "id", $course) ) {
        error("No such course id");
    }

/// Make sure the current user is allowed to see this user

    if (empty($USER->id)) {
        $currentuser = false;
    } else {
        $currentuser = ($user->id == $USER->id);
    }

    if ($course->id == SITEID) {
        $coursecontext = get_context_instance(CONTEXT_SYSTEM);   // SYSTEM context
    } else {
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);   // Course context
    }
    $usercontext   = get_context_instance(CONTEXT_USER, $user->id);       // User context
    $systemcontext = get_context_instance(CONTEXT_SYSTEM);   // SYSTEM context

    require_login();

    $strpersonalprofile = get_string('personalprofile');

    $fullname = fullname($user, has_capability('moodle/site:viewfullnames', $coursecontext));

/// If the user being shown is not ourselves, then make sure we are allowed to see them!

    if (!$currentuser) {
        // check capabilities
        if (!has_capability('moodle/local:viewbackup', $systemcontext) && 
            !has_capability('moodle/local:viewbackup', $usercontext)) {
            print_error('cannotviewbackup');
        }
    }

// if the user has an olpcxs_alias preference
// we use it to validate the $aliasuserid param --
    $useralias_pref = (int)get_user_preferences('olpcxs_alias', 0, $user->id);
    if ($useralias_pref > 0) {
        $aliasuser = get_record('user', 'id', $useralias_pref);
        if ( $useralias_pref !== (int)$aliasuserid) {
            $aliasuserid = null;
      }
    } else {
        $aliasuser = null;
        $aliasuserid = null;
    }

/// Serve the file to restore if we have it
    if (!empty($restorefile) && !empty($snapshot)) {
        if ($aliasuserid) {
            ds_serve_file($aliasuser, $snapshot, $restorefile);
        } else {
            ds_serve_file($user, $snapshot, $restorefile);
        }
        exit;
    }

/// We've established they can see the user's name at least, so what about the rest?

    $navlinks[] = array('name' => $fullname, 'link' => null, 'type' => 'misc');

    $navigation = build_navigation($navlinks);

    print_header("$course->fullname: $strpersonalprofile: $fullname", $course->fullname,
                 $navigation, "", "", true, "&nbsp;", navmenu($course));

    if ($user->deleted) {
        print_error('userdeleted');
    }
    if (is_mnet_remote_user($user)) {
        print_error('error_usertypenobackup', 'olpcxs');
    }

/// OK, security out the way, now we are showing the user

    add_to_log($course->id, "user", "viewbackup",
               "dsbackup.php?id=$user->id&course=$course->id", "$user->id");

/// Base dir for this user
    if ($user->auth != 'olpcxs') {
        print_error('error_usertypenobackup', 'olpcxs');
    }
    $basedir = $CFG->dsbackupdir . '/' . $user->username;
    if (!file_exists($basedir) || !file_exists($basedir.'/datastore-latest')) {
        print_error('error_nobackupsforuser', 'olpcxs');
    }


/// What snapshot are we showing?
    $latest = false;
    if (empty($snapshot)) {
        $latest = true;
    }


/// Print tabs at top

    $currenttab = 'backup';
    $showroles = 1;
    include('tabs.php');

    echo '<table width="90%" class="userinfobox" summary="">';
    echo '<tr>';
    echo '<td class="content">';
    if ($snapshotlist) {
        if ($aliasuser) {
            ds_print_snapshotlist($user, $aliasuser, $course);
            echo '</td><td>';
        }
        ds_print_snapshotlist($user, null, $course);
    } elseif ($latest) {
        if ($aliasuser) {
            ds_print_dir($user, $aliasuser, 'datastore-latest', $course);
            echo '<hr />';
        }
        ds_print_dir($user, null,       'datastore-latest', $course);
    } elseif($snapshot) {
        if ($aliasuserid) { // requested a snapshot from the alias...
            ds_print_dir($user, $aliasuser, $snapshot, $course);
        } else {
            ds_print_dir($user, null, $snapshot, $course);
        }
    }

    echo '</td></tr></table>';

    print_footer($course);


?>
