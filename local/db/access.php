<?php
//
// Capability definitions for the OLPC XS
//
// The capabilities are loaded into the database table when 'local' is
// installed or updated, so bump the revnumber in local/version.php
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
/* grabbed from lib/locallib: 
 * Local capabilities
 * ------------------
 *
 * If your local customisations require their own capabilities, use
 * 
 * local/db/access.php
 *
 * You should create an array called $local_capabilities, which looks like:
 * 
 * $local_capabilities = array(
 *         'moodle/local:capability' => array(
 *         'captype' => 'read',
 *         'contextlevel' => CONTEXT_SYSTEM,
 *      ),
 * );
 *
 * Note that for all local capabilities you add, you'll need to add language strings.
 * Moodle will expect to find them in local/lang/en_utf8/local.php (eg for English)
 * with a key (following the above example) of local:capability
 * See the next section for local language support.
 *
 */


// For the core capabilities, the variable is $moodle_capabilities.


$local_capabilities = array(
    'moodle/local:xsconfig'
    => array(	     
	     'riskbitmask' => RISK_CONFIG,
	     'captype' => 'write',
	     'contextlevel' => CONTEXT_SYSTEM,
	     'legacy'
	     => array(
		      'coursecreator' => CAP_ALLOW
		      )
		     ),
    'moodle/local:editaliases'
    => array(	     
	     'riskbitmask' => RISK_PERSONAL,
	     'captype' => 'write',
	     'contextlevel' => CONTEXT_SYSTEM,
	     'legacy'
	     => array(
		      'coursecreator' => CAP_ALLOW
		      )
		     ),
    'moodle/local:viewbackup'
    => array(
	     'riskbitmask' => RISK_PERSONAL,
	     'captype' => 'write',
	     'contextlevel' => CONTEXT_SYSTEM,
	     'legacy'
	     => array(
		      'coursecreator' => CAP_ALLOW
		      )
             ),
    'moodle/local:jabberpresence'
    => array(
             'riskbitmask' => RISK_PERSONAL,
             'captype' => 'write',
             'contextlevel' => CONTEXT_SYSTEM,
             'legacy'
             => array(
                      'student' => CAP_ALLOW,
                      'teacher' => CAP_ALLOW,
                      'editingteacher' => CAP_ALLOW,
                      )
             )
);

?>
