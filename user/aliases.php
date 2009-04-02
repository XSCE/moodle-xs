<?PHP // $Id$

//  Display available backup for a particular user


    require_once(dirname(dirname(__FILE__)) . '/config.php');
    require_once($CFG->dirroot.'/tag/lib.php');
    $id        = optional_param('id',     0,      PARAM_INT);   // user id
    $course    = optional_param('course', SITEID, PARAM_INT);   // course id (defaults to Site)
    $mkalias   = optional_param('mkalias', 0, PARAM_RAW);
    $aliasto     = optional_param('aliasto',     0,      PARAM_INT);
    $rmalias   = optional_param('rmalias',     0,      PARAM_RAW);
    $showall        = optional_param('showall', 0, PARAM_RAW);
    $searchtext     = optional_param('searchtext', '', PARAM_RAW); // search string

    // treat$showall and $mkalias as a bool - any string in it means true...
    $showall = !empty($showall);
    $mkalias = !empty($mkalias);
    $rmalias = !empty($rmalias);

    $previoussearch = ($searchtext != '') or ($previoussearch) ? 1:0;

    define("MAX_USERS_PER_PAGE", 500);

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
    $strparticipants = get_string("participants");
    $struser = get_string("user");
    $strpotentialusers = get_string('potentialusers', 'role');
    $strsearch = get_string('search');
    $strshowall = get_string('showall');
    $strsearchresults = get_string('searchresults');

    $fullname = fullname($user, has_capability('moodle/site:viewfullnames', $coursecontext));

/// Make sure we are allowed to do this

    require_capability('moodle/user:editaliases', $systemcontext); 

/// Has the user got a previous alias?
    if ($alias = get_user_preferences('olpcxs_alias', false, $user->id)) {
      $alias = get_record('user', 'id', $alias);
    }

/// Process incoming role assignment

    if ($frm = data_submitted()) {
      if ($mkalias && confirm_sesskey()) {
	if ($alias) {
	  print_error("error_aliasexists", 'olpcxs', '', $alias->username);
	}
	if (empty($aliasto)) {
	  print_error("error_aliasmissing", 'olpcxs');
	}
	if (! $aliasto = get_record('user', 'id', $aliasto, 'auth', 'olpcxs')) {
	  print_error("error_aliasmissing", 'olpcxs');
	}
	$count = count_records_select('user_preferences', "name='olpcxs_alias' AND value='{$aliasto->id}'");
	if ($count>0) {
	  print_error("error_aliasinuse", 'olpcxs', '', $aliasto->username);
	}
	if (set_user_preference('olpcxs_alias', $aliasto->id, $user->id)) {
	  redirect($CFG->wwwroot . "/user/aliases.php?id={$user->id}&amp;courseid={$course->id}", get_string("aliasmade", 'olpcxs'), 5);
	} else {
	  print_error("error_makingalias", 'olpcxs');
	}
      }
      if ($rmalias && confirm_sesskey()) {
	if (unset_user_preference('olpcxs_alias', $user->id)) {
	  redirect($CFG->wwwroot . "/user/aliases.php?id={$user->id}&amp;courseid={$course->id}", get_string("aliasremoved", 'olpcxs'), 5);
	} else {
	  print_error("error_removingalias", 'olpcxs');
	};
      }
    }

    $select  = "auth = 'olpcxs' AND deleted = 0 AND id<>$id";

    $searchtext = trim($searchtext);

    if ($searchtext !== '' && !$showall) {   // Search for a subset of remaining users
      $LIKE      = sql_ilike();
      $FULLNAME  = sql_fullname();

      $selectsql = " AND ($FULLNAME $LIKE '%$searchtext%' OR email $LIKE '%$searchtext%') ";
      $select  .= $selectsql;
    } else {
            $selectsql = "";
    }
    $availableusers = get_recordset_select('user', $select);
    $usercount = $availableusers->_numOfRows;

    $navlinks[] = array('name' => $fullname, 'link' => null, 'type' => 'misc');

    $navigation = build_navigation($navlinks);

    print_header("$course->fullname: $strpersonalprofile: $fullname", $course->fullname,
                 $navigation, "", "", true, "&nbsp;", navmenu($course));

    if ($user->deleted) {
      print_error('userdeleted');
    }
    if (is_mnet_remote_user($user)) {
      print_error('mnetusernobackup');
    }

/// OK, security out the way, now we are showing the user

    add_to_log($course->id, "user", "viewalias", "alias.php?id=$user->id&course=$course->id", "$user->id");

/// Base dir for this user
    if ($user->auth != 'olpcxs') {
      print_error('noaliasesforusertype', 'olpcxs');
    }

/// Print tabs at top

    $currenttab = 'aliases';
    $showroles = 1;
    include('tabs.php');

    echo '<table width="90%" class="userinfobox" summary="">';
    echo '<tr>';
    echo '<td class="content">';

    if ($alias) {

      echo get_string('accountaliasedto', 'olpcxs', $alias->username);
      ?>
          <form id="aliaspickerform" method="post" action="<?php echo "{$CFG->wwwroot}/user/aliases.php"; ?>">
          <input type="hidden" name="id" value="<?php p($id) ?>" />
          <input type="hidden" name="course" value="<?php p($course->id) ?>" />
          <input type="hidden" name="sesskey" value="<?php p(sesskey()) ?>" />
          <div style="text-align:center;">
          <input name="rmalias" id="rmalias" type="submit"
	      style="font-weight:bold"
	      value="<?php p(get_string('removealias', 'olpcxs')); ?>" />
          </div>
          </form>
	<?php

    } else {

?>

<form id="aliaspickerform" method="post" action="<?php echo "{$CFG->wwwroot}/user/aliases.php"; ?>">
<input type="hidden" id="previoussearch" name="previoussearch" value="<?php p($previoussearch) ?>" />
<input type="hidden" name="id" value="<?php p($id) ?>" />
<input type="hidden" name="course" value="<?php p($course->id) ?>" />
<input type="hidden" name="sesskey" value="<?php p(sesskey()) ?>" />
<div style="text-align:center;">
      <label for="aliastoselect"><?php print_string('potentialusers', 'role', $usercount); ?></label>
          <br />
          <select name="aliasto" size="15" id="aliastoselect"
           onchange="if (self.selectedIndex == -1) { getElementById('mkalias').disabled=true; } else { getElementById('mkalias').disabled=false;} ">
 
          <?php
            $i=0;
            if (!empty($searchtext) && !$showall) {
                echo "<optgroup label=\"$strsearchresults (" . $usercount . ")\">\n";
                while ($user = rs_fetch_next_record($availableusers)) {
                    $fullname = fullname($user, true);
                    echo "<option value=\"$user->id\">".$fullname.", ".$user->email."</option>\n";
                    $i++;
                }
                echo "</optgroup>\n";

            } else {
                if ($usercount > MAX_USERS_PER_PAGE) {
                    echo '<optgroup label="'.get_string('toomanytoshow').'"><option></option></optgroup>'."\n"
                          .'<optgroup label="'.get_string('trysearching').'"><option></option></optgroup>'."\n";
                } else {
                    while ($user = rs_fetch_next_record($availableusers)) {
                        $fullname = fullname($user, true);
                        echo "<option value=\"$user->id\">".$fullname.", ".$user->email."</option>\n";
                        $i++;
                    }
                }
            }
            if ($i==0) {
                echo '<option/>'; // empty select breaks xhtml strict
            }
          ?>
         </select>
         <br />
         <label for="searchtext" class="accesshide"><?php p($strsearch) ?></label>
         <input type="text" name="searchtext" id="searchtext" size="30" value="<?php p($searchtext, true) ?>"
                  onfocus ="getElementById('aliaspickerform').aliastoselect.selectedIndex=-1;"
                  onkeydown = "var keyCode = event.which ? event.which : event.keyCode;
                               if (keyCode == 13) {
				 getElementById('previoussearch').value=1;
                                    getElementById('aliaspickerform').submit();
                               } " />
         <input name="search" id="search" type="submit" value="<?php p($strsearch) ?>" />
         <?php
              if (!empty($searchtext)) {
                  echo '<input name="showall" id="showall" type="submit" value="'.$strshowall.'" />'."\n";
              }
         ?><br /> <br />
         
         <input name="mkalias" id="mkalias" type="submit"
	      style="font-weight:bold"
	      value="<?php p(get_string('makealias', 'olpcxs')); ?>" disabled="disabled" />
</form>
</td><td><?php print_string('aliases_explanation', 'olpcxs'); ?></td>

<?php

     } // this ends the if (alias) } else {

    echo '</td></tr></table>';

    print_footer($course);


?>
