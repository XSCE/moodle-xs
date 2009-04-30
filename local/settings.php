<?php
/*
 * OLPCXS config settings
 *
 */

if (has_capability('moodle/local:editaliases', $systemcontext)) {

// "presence" settingpage
    $temp = new admin_settingpage('presence', get_string('presence', 'olpcxs'));
    $temp->add(new admin_setting_configcheckbox('presencebycourse',
                                                get_string('presencebycourse', 'olpcxs'),
                                                get_string('configpresencebycourse', 'olpcxs'),
                                                0));
    $ADMIN->add('courses', $temp);
}
?>