<?php
/*
 * OLPCXS config settings
 *
 */

if (has_capability('moodle/local:xsconfig', $systemcontext)) {
// "presence" settingpage
    $temp = new admin_settingpage('presence', get_string('presence', 'olpcxs'),
                                  'moodle/local:xsconfig');
    $temp->add(new admin_setting_configcheckbox('presencebycourse',
                                                get_string('presencebycourse', 'olpcxs'),
                                                get_string('configpresencebycourse', 'olpcxs'),
                                                0));
    $ADMIN->add('courses', $temp);

    $temp = new admin_externalpage('antitheft', get_string('antitheft', 'olpcxs'),
                                   "{$CFG->wwwroot}/{$CFG->admin}/antitheft.php",
                                  'moodle/local:xsconfig');
    $ADMIN->add('users', $temp);
}

?>