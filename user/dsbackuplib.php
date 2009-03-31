<?php

function ds_print_dir($user, $dsdir, $course) {
  global $CFG;

  $dspath = implode('/', array($CFG->dsbackupdir, $user->username, $dsdir, '/store'));

  $latest = false;
  $dsbasepath = dirname($dspath);
  if (is_link($dsbasepath)) {
    $latest = true;
    $dsbasepath = readlink($dsbasepath);
  }

  // Extract UTC datestamp
  if (!preg_match('/^datastore-(\d{4})-(\d{2})-(\d{2})_(\d{2}):(\d{2})$/',
		  basename($dsbasepath), $match)) {
    error("Malformed datastore directory - " . $dsbasepath);
  }

  $epoch = mktime($match[4], $match[5], 0, $match[2], $match[3], $match[1]);
  echo '<p>';
  echo get_string('backuptakenat', 'olpcxs') . ' ';
  echo timestamp_to_elapsed_string($epoch, time()) . ' ' . get_string('ago','timedistances').'. ';
  if ($latest) {
    echo get_string('thisislatestbackup', 'olpcxs') . ' ';
  }
  echo "<a href=\"{$CFG->wwwroot}/user/dsbackup.php?id={$user->id}&amp;courseid={$course->id}&amp;snapshotlist=1\">";
  echo get_string('showallbackups', 'olpcxs') . '</a></p>';

  // First, we read all the entries
  // into an array - this is a waste of mem
  // but php's opendir/readdir knows notink
  // about sorting.
  // OMG `ls -t1`
  if (! $dsdh = opendir($dspath)) {
    error("Problem opening $dspath");
  }
  $dirents = array();
  while ($direntry = readdir($dsdh)) {
    // we will only look at metadata files,
    // capturing the "root" filename match
    // in the process
    if (!preg_match('/^(.*)\.metadata$/',$direntry, $match)) {
      continue;
    }
    $filename = $match[1];
    $filepath = $dspath . '/' . $filename;
    $mdpath = $dspath . '/' . $direntry;
    if (!is_file($filepath) || !is_file($mdpath)) {
      continue;
    }

    // Read the file lazily. Memory bound.
    // (but the json parser isn't streaming, so...)
    // note that we get it as an array, as some properties
    // have funny names...
    $md = json_decode(file_get_contents($mdpath), true);

    if (!is_array($md)) {
      continue;
    }
    if (isset($md['title'])) {
      $md['title'] = trim($md['title']);
    }
    if (empty($md['title'])) {
      if (!empty($title['title:text'])) {
	$md['title'] = $md['title:text'];
      } else {
	$md['title'] = get_string('activitybackup_notitle', 'olpcxs');
      }
    }
    $md['fname'] = $filename;
    $dirents[] = $md;
  }
  closedir($dsdh);

  // my kingdom for a lambda
  function ds_print_dir_sorter($a, $b) {
    $av = array_key_exists('timestamp', $a) ? (int)$a['timestamp'] : 0;
    $bv = array_key_exists('timestamp', $b) ? (int)$b['timestamp'] : 0;

    if ($av == $bv) {
        return 0;
    }
    return ($av < $bv) ? 1 : -1;
  }
  usort($dirents, 'ds_print_dir_sorter');

  echo '<ul>';
  foreach ($dirents AS $md) {

    // Here we add the urlencoded title, which
    // feeds nicely into the "Download completed"
    // dialog in Browse.xo
    // ACTUALLY, non-ASII titles break Browse.xo
    //           so very nice, but no :-(
    echo '<li>'
      . "<a href=\"{$CFG->wwwroot}/user/dsbackup.php/"
      // . urlencode($md['title'])
      . 'Activity+Backup'  // don't localise!
      . "?id={$user->id}&amp;courseid={$course->id}&amp;snapshot="
      . urlencode($dsdir) . '&amp;restorefile=' .urlencode($md['fname']) . '">'      
      . s($md['title'])
      . '</a> &#8212; ' // emdash
      . s(timestamp_to_elapsed_string(strtotime($md['mtime']), time()) 
	  . ' ' . get_string('ago', 'timedistances'));

    /* TODO: something nice with the 'buddies'
       info we have 
    if (!empty($md['buddies'])) { // May be ''
      // Forced to array
      $buddies = json_decode($md['buddies'], true);
      $buddynames = array();
      foreach ($buddies as $hashid => $values) {
	// TODO: Something nice with the colours
	$name    = $values[0];
	$colours = $values[1];
	$buddynames[] = s($name);
      }
      echo '<br />With: ' . implode(', ', $buddynames);
    }
    */
    echo "</li>\n";
  }
  echo '</ul>';
}

// convert ugly ds timestamps to
// elapsed-time strings
function dsts_to_elapsed_string($dsts) {
  if (preg_match('^(\d{4})-(\d{2})-(\d{2})_(\d{2}):(\d{2})$/', $dsts)) {
    $epoch = mktime($match[4], $match[5], $match[2], $match[3], $match[1]);
    return timestamp_to_elapsed_string($epoch, time());
  } else {
    mdie("Datastore timestamp string does not match dsts format");
  }
}


function print_userhome($userhome, $path) {
  global $baseurl;

  $uid = basename($path);

  echo '<h1>Snapshot listing for user ' . $uid . '</h1>';
  echo '<p>Times are in UTC</p>';
  echo '<ul>';

  // Extract UTC datestamp



  while ($direntry = readdir($userhome)) {

    if (!is_dir($path.'/'.$direntry)) {
      continue;
    }

    if (!preg_match('/^datastore-(\d{4}-\d{2}-\d{2}_\d{2}:\d{2})$/',
		    $direntry, $match)) {
      continue;
    }
    echo "<li><a href=\"{$baseurl}/{$uid}/{$direntry}\">"
      . $direntry
      . "</a></li>\n";
  }
  echo '</ul>';
}

function ds_print_snapshotlist($user, $course) {
  global $CFG;

  $dspath = implode('/', array($CFG->dsbackupdir, $user->username));

  // First, we read all the entries
  // into an array - this is a waste of mem
  // but php's opendir/readdir knows notink
  // about sorting.
  // OMG `ls -t1`
  if (! $dsdh = opendir($dspath)) {
    error("Problem opening $dspath");
  }

  $dirents = array();
  while ($direntry = readdir($dsdh)) {
    if (!preg_match('/^datastore-(\d{4})-(\d{2})-(\d{2})_(\d{2}):(\d{2})$/',$direntry, $match)) {
      continue;
    }
    $epoch = mktime($match[4], $match[5], 0, $match[2], $match[3], $match[1]);
    $strtime = timestamp_to_elapsed_string($epoch, time());
    $dirents[] = array($direntry, $strtime);
  }
  closedir($dsdh);

  // my kingdom for a lambda
  function ds_print_snapshotlist_sorter($a, $b) {
    $av = $a[0];
    $bv = $b[0];

    if ($av == $bv) {
        return 0;
    }
    return ($av < $bv) ? 1 : -1;
  }
  usort($dirents, 'ds_print_snapshotlist_sorter');

  echo '<ul>';
  foreach ($dirents AS $d) {
    echo '<li>'
      . "<a href=\"{$CFG->wwwroot}/user/dsbackup.php?"
      . "id={$user->id}&amp;courseid={$course->id}&amp;"
      . "snapshot={$d[0]}\" >"
      . s($d[1] . ' ' .get_string('ago', 'timedistances')) 
      . '</a></li>';
  }
  echo '</ul>';
}

function ds_serve_file($user, $snapshot, $restorefile) {
  global $CFG;
  $filepath = implode('/', array($CFG->dsbackupdir, $user->username, $snapshot, 'store'));

  if (!file_exists($filepath)) {
    error("The file $filepath does not exist");
  }

  $jeb = make_journal_entry_bundle($filepath, $restorefile);
  header("Content-Type: application/vnd.olpc-journal-entry");
  header("Content-Length: " . filesize($jeb));
  $fp = fopen($jeb, 'rb');
  fpassthru($fp);
  exit;
}

?>