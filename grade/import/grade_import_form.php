<?php // $Id$
require_once $CFG->libdir.'/formslib.php';

class grade_import_form extends moodleform {
    function definition (){
        $mform =& $this->_form;

        // course id needs to be passed for auth purposes
        $mform->addElement('hidden', 'id', optional_param('id'));

        // file upload
        $mform->addElement('file', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $this->add_action_buttons(false, get_string('uploadgrades'));
    }

    function get_userfile_name(){
        if ($this->is_submitted() and $this->is_validated()) {
            // return the temporary filename to process
            return $this->_upload_manager->files['userfile']['tmp_name'];
        }else{
            return  NULL;
        }
    }
}


class grade_import_mapping_form extends moodleform {
    function definition () {
        $mform =& $this->_form;

        // course id needs to be passed for auth purposes
        $mform->addElement('hidden', 'id', optional_param('id'));

        $this->add_action_buttons(false, get_string('uploadgrades'));
    }
    
    function setup ($headers = '', $filename = '') {
        $mform =& $this->_form;
        if (is_array($headers)) {
            foreach ($headers as $header) {
                $mform->addElement('hidden', $header, $header); 
                $mform->addRule($header, null, 'required');
            }
        }    
        if ($filename) {
            $mform->addElement('hidden', 'filename', $filename);
            $mform->addRule('filename', null, 'required');
        }
        
        print_object($mform);
                  
    }
}
?>