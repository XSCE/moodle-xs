<?php
/*
 * PHP port of Ruby on Rails famous distance_of_time_in_words method.
 *  See http://api.rubyonrails.com/classes/ActionView/Helpers/DateHelper.html for more details.
 *
 * Reports the approximate distance in time between two timestamps. Set include_seconds
 * to true if you want more detailed approximations.
 *
 * Imported from a comment on PHP.net and edited lightly by martin@laptop.org
 * 
 */
function timestamp_to_elapsed_string($from_time, $to_time = 0, $include_seconds = false) {
  $distance_in_minutes = round(abs($to_time - $from_time) / 60);
  $distance_in_seconds = round(abs($to_time - $from_time));

  if ($distance_in_minutes >= 0 and $distance_in_minutes <= 1) {
    if (!$include_seconds) {
      return ($distance_in_minutes == 0) ? 'less than a minute' : '1 minute';
    } else {
      if ($distance_in_seconds >= 0 and $distance_in_seconds <= 4) {
	return get_string('lessthan5s', 'timedistances');
      } elseif ($distance_in_seconds >= 5 and $distance_in_seconds <= 9) {
	return get_string('lessthan10s', 'timedistances');
      } elseif ($distance_in_seconds >= 10 and $distance_in_seconds <= 19) {
	return get_string('lessthan20s', 'timedistances');
      } elseif ($distance_in_seconds >= 20 and $distance_in_seconds <= 39) {
	return  get_string('halfminute', 'timedistances');
      } elseif ($distance_in_seconds >= 40 and $distance_in_seconds <= 59) {
	return get_string('lessthan1m', 'timedistances');
      } else {
	return '1 ' . get_string('minute', 'timedistances');
      }
    }
  } elseif ($distance_in_minutes >= 2 and $distance_in_minutes <= 44) {
    return $distance_in_minutes . ' ' . get_string('minutes', 'timedistances');
  } elseif ($distance_in_minutes >= 45 and $distance_in_minutes <= 89) {
    return get_string('aprox', 'timedistances') . ' 1 ' . get_string('hour', 'timedistances');
  } elseif ($distance_in_minutes >= 90 and $distance_in_minutes <= 1439) {
    return get_string('aprox', 'timedistances') . ' ' . round(floatval($distance_in_minutes) / 60.0)
      . ' ' . get_string('hours', 'timedistances');
  } elseif ($distance_in_minutes >= 1440 and $distance_in_minutes <= 2879) {
    return '1 ' . get_string('day', 'timedistances');
  } elseif ($distance_in_minutes >= 2880 and $distance_in_minutes <= 43199) {
    return get_string('aprox', 'timedistances') . ' ' . round(floatval($distance_in_minutes) / 1440)
      . ' ' . get_string('days', 'timedistances');
  } elseif ($distance_in_minutes >= 43200 and $distance_in_minutes <= 86399) {
    return get_string('aprox', 'timedistances') . ' 1 ' . get_string('month', 'timedistances');
  } elseif ($distance_in_minutes >= 86400 and $distance_in_minutes <= 525599) {
    return round(floatval($distance_in_minutes) / 43200) . ' ' . get_string('months', 'timedistances');
  } elseif ($distance_in_minutes >= 525600 and $distance_in_minutes <= 1051199) {
    return get_string('aprox', 'timedistances') . ' 1 ' . get_string('year', 'timedistances');
  } else {
    return get_string('morethan', 'timedistances'). ' ' . round(floatval($distance_in_minutes) / 525600)
      . ' '. get_string('years', 'timedistances');
  }
  }

/*
 * make_journal_entry_bundle()
 *
 * Will read a ds entry from the given
 * ds path, and return a filepath to a
 * properly formed JEB tempfile.
 *
 * The caller is responsible for
 * the tempfile (caching, removal, etc).
 *
 */
function make_journal_entry_bundle($dspath, $uid) {

  // We use /var/tmp as we will store larger
  // files than what /tmp may be prepared to
  // hold (/tmp may be a ramdisk)
  $filepath = tempnam('/var/tmp', 'ds-restore-');

  $zip = new ZipArchive();

  if ($zip->open($filepath, ZIPARCHIVE::OVERWRITE)!==TRUE) {
    mdie("cannot open <$filepath>\n");
  }
  // Main file
  $zip->addFile("$dspath/$uid", "$uid/$uid")
    || mdie("Error adding file $dspath/$uid");
  $zip->addFile("$dspath/$uid.metadata", "$uid/_metadata.json")
    || mdie("Error adding metadata");
  if (file_exists("$dspath/preview/$uid")) {
    $zip->addFile("$dspath/preview/$uid", "$uid/preview/$uid")
      || mdie("Error adding preview");
  }
  $zip->close()
    || mdie("Error zipping");
  return $filepath;
}

?>