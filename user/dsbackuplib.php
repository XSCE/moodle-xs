<?php

function ds_version($dspath) {
    // determine versin
    if (file_exists($dspath . '/version')) {
        $v = (int)file_get_contents($dspath . '/version');
        if ($v < 2) {
            $version = $v;
        }
    } else if (file_exists($dspath . '/store')) { // file_exists() matches a dir
        $version = 0;
    }
    if (!isset($version)) {
        error("Datastore is corrupt, or has unknown format");
    }
    return $version;
};

function ds_print_dir($user, $aliasuser, $dsdir, $course) {
    global $CFG;
    
    if ($aliasuser) {
        $dspath = implode('/', array($CFG->dsbackupdir, $aliasuser->username, $dsdir));
        $aliasparam = '&amp;aliasuserid=' . $aliasuser->id;
    } else {
        $dspath = implode('/', array($CFG->dsbackupdir, $user->username, $dsdir));
        $aliasparam = '';
    }

    $version = ds_version($dspath);

    $latest = false;
    if (is_link($dspath)) {
        $latest = true;
        $dspath = readlink($dspath);
    }

    // Extract UTC datestamp
    if (!preg_match('/^datastore-(\d{4})-(\d{2})-(\d{2})_(\d{2}):(\d{2})$/',
                    basename($dspath), $match)) {
        error("Malformed datastore directory - " . $dspath);
    }

    $epoch = gmmktime($match[4], $match[5], 0, $match[2], $match[3], $match[1]);
    echo '<p>';
    echo get_string('backuptakenat', 'olpcxs') . ' ';
    echo timestamp_to_elapsed_string($epoch, time()) . ' ' . get_string('ago','timedistances').'. ';
    if ($latest) {
        echo get_string('thisislatestbackup', 'olpcxs') . ' ';
    }
    echo "<a href=\"{$CFG->wwwroot}/user/dsbackup.php?"
        . "id={$user->id}&amp;courseid={$course->id}"
        . "&amp;snapshotlist=1\">";
    echo get_string('showallbackups', 'olpcxs') . '</a></p>';

    // First, we read all the entries
    // into an array - this is a waste of mem
    // but php's opendir/readdir knows notink
    // about sorting.
    // OMG `ls -t1`
    switch ($version) {
    case 0:
        $dirents = ds_readdir_v0($dspath);
        break;
    case 1:
        $dirents = ds_readdir_v1($dspath);
        break;
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
            . "?id={$user->id}&amp;courseid={$course->id}"
            . $aliasparam
            . "&amp;snapshot=" . urlencode($dsdir)
            . '&amp;restorefile=' .urlencode($md['fname']) . '">'      
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
// my kingdom for a lambda
function ds_print_dir_sorter($a, $b) {
    $av = array_key_exists('timestamp', $a) ? (int)$a['timestamp'] : 0;
    $bv = array_key_exists('timestamp', $b) ? (int)$b['timestamp'] : 0;

    if ($av == $bv) {
        return 0;
    }
    return ($av < $bv) ? 1 : -1;
}

function ds_readdir_v0($dspath) {
    global $CFG;

    $dspath .= '/store';

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

    return $dirents;
}

function ds_readdir_v1($dspath) {
    global $CFG;

    if (! $dsdh = opendir($dspath)) {
        error("Problem opening $dspath");
    }
    $dirents = array();

    // v1 has a 1-level fan-out so walk it...
    while ($tdent = readdir($dsdh)) {
        $tdentpath = $dspath . '/' . $tdent;
        if (strlen($tdent) !== 2 || !is_dir($tdentpath)) {
            // not part of the fan-out
            continue;
        }
        if (! $tdh = opendir($tdentpath)) {
            error("Problem opening $tdentpath");
        }
        while ($dent = readdir($tdh)) {
            $dentpath = $tdentpath . '/' . $dent;
            if (strlen($dent) !== 36 || !is_dir($dentpath)) {
                // not a ds entry
                continue;
            }
            if (!file_exists($dentpath . '/' . 'data')) {
                // it's a pure metadata entry, safe to skip...
                continue;
            }
            if (!file_exists($dentpath . '/' . 'metadata')) {
                // shouldn't happen, ignore entry
                continue;
            }
            
            $filename = $dentpath;
            $mdpath = $dentpath . '/' . 'metadata';

            // note that we get it as an array, as some properties
            // have funny names...
            $md = ds_readmetadata_v1($mdpath);

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
            $md['fname'] = $dent;
            $dirents[] = $md;
        }
        closedir($tdh);
    }
    closedir($dsdh);    
    return $dirents;
}

function ds_readmetadata_v1($mdpath) {

    $md = array();
    if (! $dh = opendir($mdpath)) {
        error("Problem opening $mdpath");
    }
    while ($dent = readdir($dh)) {
        if ($value = file_get_contents($mdpath . '/' . $dent)) {
            $md[$dent] = $value;
        }
    }
    closedir($dh);
    return $md;
}

// convert ugly ds timestamps to
// elapsed-time strings
function dsts_to_elapsed_string($dsts) {
    if (preg_match('^(\d{4})-(\d{2})-(\d{2})_(\d{2}):(\d{2})$/', $dsts)) {
        $epoch = gmmktime($match[4], $match[5], $match[2], $match[3], $match[1]);
        return timestamp_to_elapsed_string($epoch, time());
    } else {
        mdie("Datastore timestamp string does not match dsts format");
    }
}

function ds_print_snapshotlist($user, $aliasuser, $course) {
    global $CFG;

    if ($aliasuser) {
        $dspath = implode('/', array($CFG->dsbackupdir, $aliasuser->username));
        $aliasparam = '&amp;aliasuserid=' . $aliasuser->id;
    } else {
        $dspath = implode('/', array($CFG->dsbackupdir, $user->username));
        $aliasparam = '';
        }

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
        $epoch = gmmktime($match[4], $match[5], 0, $match[2], $match[3], $match[1]);
        $strtime = timestamp_to_elapsed_string($epoch, time());
        $dirents[] = array($direntry, $strtime);
    }
    closedir($dsdh);

    usort($dirents, 'ds_print_snapshotlist_sorter');

    echo '<ul>';
    foreach ($dirents AS $d) {
        echo '<li>'
            . "<a href=\"{$CFG->wwwroot}/user/dsbackup.php?"
            . "id={$user->id}&amp;courseid={$course->id}"
            . $aliasparam
            . "&amp;snapshot={$d[0]}\" >"
            . s($d[1] . ' ' .get_string('ago', 'timedistances')) 
            . '</a></li>';
    }
    echo '</ul>';
}

// my kingdom for a lambda
function ds_print_snapshotlist_sorter($a, $b) {
    $av = $a[0];
    $bv = $b[0];
  
    if ($av == $bv) {
        return 0;
    }
    return ($av < $bv) ? 1 : -1;
}
function ds_serve_file($user, $snapshot, $restorefile) {
    global $CFG;

    $dspath = implode('/', array($CFG->dsbackupdir, $user->username, $snapshot));

    $version = ds_version($dspath);

    $tmpfiles = array();

    switch ($version) {
    case 0:
        $filepath = implode('/', array($CFG->dsbackupdir, $user->username,
                                       $snapshot, 'store', $restorefile));
        $mdpath   = implode('/', array($CFG->dsbackupdir, $user->username,
                                       $snapshot, 'store', $restorefile . '.metadata'));
        $prevpath = implode('/', array($CFG->dsbackupdir, $user->username,
                                       $snapshot, 'store', 'preview', $restorefile));
        if (!file_exists($filepath)) {
            error("The file $filepath does not exist");
        }
        if (!file_exists($prevpath)) {
            $prevpath = null;
        }
        break;
    case 1:
        error_log("r $restorefile");
        $filepath = implode('/', array($CFG->dsbackupdir, $user->username,
                                       $snapshot, substr($restorefile, 0, 2) ,
                                       $restorefile, 'data'));
        $mdpath   = implode('/', array($CFG->dsbackupdir, $user->username,
                                       $snapshot, substr($restorefile, 0, 2) ,
                                       $restorefile, 'metadata'));
        $prevpath = null;
        $md = ds_readmetadata_v1($mdpath);
        if (!$tmpmd = tempnam("/tmp", "dsmetadata")) {
            error("Error creating tmpfile");
        }
        if (!file_put_contents($tmpmd, json_encode($md))) {
            error("Error writing to metadata tmpfile");
        }
        $mdpath = $tmpmd;

        $tmpfiles[] = $mdpath;
        break;
    }

    $jeb = make_journal_entry_bundle($restorefile, $filepath, $mdpath, $prevpath);        
    header("Content-Type: application/vnd.olpc-journal-entry");
    header("Content-Length: " . filesize($jeb));
    $fp = fopen($jeb, 'rb');
    fpassthru($fp);
    fclose($fp);
    unlink($jeb);
    foreach ($tmpfiles as $tmpfile) {
        unlink($tmpfile);
    }
    exit;
}

?>