<?php
    // Allows the admin to configure services for remote hosts

    require_once(dirname(dirname(__FILE__)) . '/config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->dirroot.'/local/lib.php');

    require_login();
    admin_externalpage_setup('antitheft');

    $context = get_context_instance(CONTEXT_SYSTEM);

    require_capability('moodle/local:xsconfig', $context, $USER->id, true, "nopermissions");

    if (!$site = get_site()) {
        print_error('nosite', '', '', NULL, true);
    }

    if (($data = data_submitted()) && confirm_sesskey()) {
        if (!empty($data->serialnum) && preg_match('/^[A-Z0-9]+$/', $data->serialnum)) {
            $now = time();
            $sql = "UPDATE {$CFG->prefix}oat_laptops
                             SET    timestolen=$now
                             WHERE serialnum='{$data->serialnum}'";
            execute_sql($sql, false);
            add_to_log(SITEID, 'antitheft', 'markstolen', 'admin/antitheft.php', $data->serialnum, '', $USER->id);
            redirect('antitheft.php');
        }
    }
    if (optional_param('rescueleases', false, PARAM_RAW) && confirm_sesskey()) {
        // never returns
        add_to_log(SITEID, 'antitheft', 'rescueleases', 'admin/antitheft.php', $data->serialnum, '', $USER->id);
        serve_rescue_leases();
    }

    $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.imagealt, u.lastlogin, u.picture,
                   pa.value as alias,
                   o.serialnum, o.timelastreq, o.timekilled, o.timeleasegiven, o.timestolen
            FROM {$CFG->prefix}oat_laptops o
            LEFT OUTER JOIN {$CFG->prefix}user u
                 ON (u.username=o.serialnum AND u.auth='olpcxs')
            LEFT OUTER JOIN {$CFG->prefix}user_preferences pa
                 ON (u.id = pa.userid AND pa.name='olpcxs_alias')
            ";

    $users = get_recordset_sql($sql);

    include('./antitheft.html');
?>