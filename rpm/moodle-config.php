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
$CFG->dirroot   = '/var/www/moodle/web';
$CFG->dataroot  = '/var/lib/moodle';

$CFG->directorypermissions = 02777;

$CFG->admin = 'admin';

////
//// Feature settings
////
// Ensure we don't send out emails - ever
$CFG->noemailever = true;

// This gives us better logging in apache
$CFG->apacheloguser = 3; // Log username.

// Disable old, slow, buggy HTMLArea until we have tinymce sorted out
$CFG->htmleditor=0;

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
