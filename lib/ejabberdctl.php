<?php
/**
 ** Simple OO wrapper around ejabberdctl
 **
 ** It cannot handle groups with non alphanum names,
 ** mainly because ejabberdctl cannot handle them either.
 **
 ** Author: Martin Langhoff <martin@laptop.org>
 ** Copyright: One Laptop per Child
 ** Licence: GPLv2
 **
 ** 
 ** Usage notes -- the convention is to
 **
 ** $ej = new ejabberdctl();
 ** $ej->set_vhost('foo.bar.org'); # set the vhost
 ** $var $ej->srg_list_groups();
 ** if ($var !== NULL) { // null indicates error
 **     $var is an array containing
 *      the command output
 ** } else { errorhandling... }
 **
 **/ 

class ejabberdctl{

    function set_vhost($vhost) {
        $this->vhost=$vhost;
    }

    function srg_list_groups() {
        return $this->_exec(array('srg-list-groups', $this->vhost));
    }    

    // This function returns a named array
    // with the values. It follows the ejabberdctl
    // output format, which results as of ejabberd 2.0.3
    // in 
    //
    // Array
    // (
    //   [name] => Online
    //   [displayed_groups] => Array
    //     (
    //         [0] => Online
    //      )
    // 
    //   [description] => Created_by_ejabberd_init
    //   [online_users] => true
    //   [members] => Array()
    //   )
    //
    // 
    function srg_get_info($srg) {
        $ret = $this->_exec(array('srg-get-info',
                                  $srg, $this->vhost));
        if ($ret === null) {
            return null;
        } else {
            $info = array();
            foreach ($ret as $line) {
                if (preg_match('/^(\w+): (.+)$/', $line, $match)) {
                    $key = $match[1];
                    $value = $match[2];
                    if (preg_match('/^"(.*)"$/', $value, $valmatch)) {
                        $value = stripslashes($valmatch[1]); // unwrap from slashes
                    } elseif (preg_match('/^\[(.*)\]$/', $value, $valmatch)) {
                        // looks like an array - unpack it...
                        // what ejabberd prints is csv'ish as far as
                        // I have tested
                        $value = str_getcsv($valmatch[1]);
                    }
                    $info[$key]=$value;
                }
            }
        }
        return $info;
    }
            
    // Notes:
    // - srg-create allows for a different 'srg identifier' and name,
    //   we hardcode them to be the same.
    // - the displayed group can be different -- if missing, we default
    //   to the group itself -- this is the "natural", "mirror" setup.
    function srg_create($srg, $desc, $dispsrg=NULL) {
        if ($dispsrg===NULL) {
            $dispsrg = $srg;
        }
        return $this->_exec(array('srg-create',
                                  $srg, $this->vhost, $srg, $desc, $dispsrg));
    }

    function srg_delete($srg) {
        return $this->_exec(array('srg-delete',
                                  $srg, $this->vhost)); 
    }


    function srg_user_add($srg, $user) {
        return $this->_exec(array('srg-user-add',
                                  $user, $this->vhost, $srg));  
    }

    function srg_user_delete($srg, $user) {
        return $this->_exec(array('srg-user-delete',
                                  $user, $this->vhost, $srg));
    }

    function _exec($cmdarr) {

        $cmdarr = array_map('ejabberdctl_escapeshellarg', $cmdarr);
        $cmd = 'sudo -u ejabberd /usr/sbin/ejabberdctl ' . implode(' ', $cmdarr);
        
        $buf = '';
        error_log("about to exec $cmd");
        exec($cmd, $buf, $ret);
        if ($ret === 0) {
            return $buf;
        } else {
            return NULL;
        }
    }

}

//  = Special escapeshellargs for ejabberdctl =
// ejabberdctl's escaping is broken in an odd way. The workaround is to
// escape things with normal shell args, and then wrap it all in
// nested double-and-single quotes like "'this'".
// The singlequotes are provided by escapeshellarg() - we do the rest.
// As of ejabberdctl as present in 2.0.3 fedora rpms anyway :-/
function ejabberdctl_escapeshellarg($arg) {
    return '"'. escapeshellarg($arg) . '"';
}


// Utility function for PHP < 5.3.0
if (!function_exists('str_getcsv')) {
    function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
        $fiveMBs = 5 * 1024 * 1024;
        $fp = fopen("php://temp/maxmemory:$fiveMBs", 'r+');
        fputs($fp, $input);
        rewind($fp);

        $data = fgetcsv($fp, 1000, $delimiter, $enclosure); //  $escape only got added in 5.3.0

        fclose($fp);
        return $data;
    }

} 



?>