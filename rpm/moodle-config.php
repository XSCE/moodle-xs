<?PHP // $Id$

unset($CFG);  // Security - 

$CFG->dbtype    = 'postgres7';      // mysql or postgres7 (for now)
$CFG->dbhost    = '';               // empty so as to use sockets
$CFG->dbname    = 'moodle-xs';      // database name, eg moodle
$CFG->dbuser    = 'apache';         // your database username
$CFG->dbpass    = '';               // we use ident
$CFG->prefix    = 'mdl_';        // Prefix to use for all table names

$CFG->dbpersist = true;         // Should database connections be reused?

$CFG->wwwroot   = 'http://schoolserver/moodle';
if (file_exists('/etc/sysconfig/xs_domain_name')) {
    $XS_FQDN=trim(file_get_contents('/etc/sysconfig/xs_domain_name'));
    $CFG->wwwroot = "http://schoolserver.$XS_FQDN/moodle";
}

$CFG->dirroot   = '/var/www/moodle/web';
$CFG->dataroot  = '/var/lib/moodle';

$CFG->dsbackupdir = '/library/users/';

$CFG->directorypermissions = 02777;

$CFG->admin = 'admin';

// Logging
$CFG->debug=6143;
$CFG->debugdisplay=0;

////
//// Feature settings
////
// Ensure we don't send out emails - ever
$CFG->noemailever = true;

// This gives us better logging in apache
$CFG->apacheloguser = 3; // Log username.

// Disable old, slow, buggy HTMLArea until we have tinymce sorted out
$CFG->htmleditor=0;

/// Authentication -
// Force logins
$CFG->forcelogin=1;
// Don't allow guests in...
$CFG->guestloginbutton=0;
// Don't allow registrations
$CFG->registerauth='';
// OLPCXS
$CFG->auth='olpcxs';
$CFG->olpcxsdb='/home/idmgr/identity.db';
$CFG->theme='xo';

// Frontpage
$CFG->frontpage='0,1';
$CFG->frontpageloggedin='0,1';

// Security
$CFG->cronclionly=1;

// Sysconfig
$CFG->zip='/usr/bin/zip';
$CFG->unzip='/usr/bin/unzip';
$CFG->pathtodu='/usr/bin/du';
$CFG->aspellpath='/usr/bin/aspell';

// Allow for local overrides
if (is_dir('/etc/moodle/conf.d')) {
    foreach (glob('/etc/moodle/conf.d/*.php') as $conffile) {
        include_once($conffile);
    }
}


// END OF CONFIG 

if ($CFG->wwwroot == 'http://example.com/moodle') {
    echo "<p>Error detected in configuration file</p>";
    echo "<p>Your server address can not be: \$CFG->wwwroot = 'http://example.com/moodle';</p>";
    die;
}

// If moodle is "disabled" prevent web access.
// We still allow cli access for install/upgrade purposes.
if (isset($_SERVER['REMOTE_ADDR']) && !file_exists('/var/lock/subsys/moodle')) {
    echo "<p>Moodle is disabled at the moment.</p>";
    exit;
}

if (file_exists("$CFG->dirroot/lib/setup.php"))  {       // Do not edit
    include_once("$CFG->dirroot/lib/setup.php");
} else {
    if ($CFG->dirroot == dirname(__FILE__)) {
        echo "<p>Could not find this file: $CFG->dirroot/lib/setup.php</p>";
        echo "<p>Are you sure all your files have been uploaded?</p>";
    } else {
        echo "<p>Error detected in config.php</p>";
        echo "<p>Error in: \$CFG->dirroot = '$CFG->dirroot';</p>";
        echo "<p>Try this: \$CFG->dirroot = '".dirname(__FILE__)."';</p>";
    }
    die;
}
// MAKE SURE WHEN YOU EDIT THIS FILE THAT THERE ARE NO SPACES, BLANK LINES,
// RETURNS, OR ANYTHING ELSE AFTER THE TWO CHARACTERS ON THE NEXT LINE.
?>
