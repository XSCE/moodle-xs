<?php  //$Id$

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/form/selectgroups.php');

class question_import_form extends moodleform {

    function definition() {
        global $COURSE;
        $mform    =& $this->_form;

        $defaultcategory   = $this->_customdata['defaultcategory'];
        $contexts   = $this->_customdata['contexts'];
//--------------------------------------------------------------------------------
        $mform->addElement('header','general', get_string('general', 'form'));

        $mform->addElement('questioncategory', 'category', get_string('category','quiz'), compact('contexts'));
        $mform->setDefault('category', $defaultcategory);
        $mform->setHelpButton('category', array('importcategory', get_string('importcategory','quiz'), 'quiz'));

        $categorygroup = array();
        $categorygroup[] =& $mform->createElement('checkbox', 'catfromfile', '', get_string('getcategoryfromfile', 'question'));
        $categorygroup[] =& $mform->createElement('checkbox', 'contextfromfile', '', get_string('getcontextfromfile', 'question'));
        $mform->addGroup($categorygroup, 'categorygroup', '', '', false);
        $mform->disabledIf('categorygroup', 'catfromfile', 'notchecked');
        $mform->setDefault('catfromfile', 1);
        $mform->setDefault('contextfromfile', 1);

        $fileformatnames = get_import_export_formats('import');
        $mform->addElement('select', 'format', get_string('fileformat','quiz'), $fileformatnames);
        $mform->setDefault('format', 'gift');

        $matchgrades = array();
        $matchgrades['error'] = get_string('matchgradeserror','quiz');
        $matchgrades['nearest'] = get_string('matchgradesnearest','quiz');
        $mform->addElement('select', 'matchgrades', get_string('matchgrades','quiz'), $matchgrades);
        $mform->setHelpButton('matchgrades', array('matchgrades', get_string('matchgrades','quiz'), 'quiz'));
        $mform->setDefault('matchgrades', 'error');

        $mform->addElement('selectyesno', 'stoponerror', get_string('stoponerror', 'quiz'));
        $mform->setDefault('stoponerror', 1);
        $mform->setHelpButton('stoponerror', array('stoponerror', get_string('stoponerror', 'quiz'), 'quiz'));
        
//--------------------------------------------------------------------------------
        $mform->addElement('header', 'importfileupload', get_string('importfileupload','quiz'));

        $this->set_upload_manager(new upload_manager('newfile', true, false, $COURSE, false, 0, false, true, false));
        $mform->addElement('file', 'newfile', get_string('upload'));
//--------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('uploadthisfile'));

//--------------------------------------------------------------------------------
        $mform->addElement('header', 'importfilearea', get_string('importfilearea','quiz'));

        $mform->addElement('choosecoursefile', 'choosefile', get_string('choosefile','quiz'));
//--------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('importfromthisfile','quiz'));
//--------------------------------------------------------------------------------
    }
    function get_importfile_name(){
        if ($this->is_submitted() and $this->is_validated()) {
            // return the temporary filename to process
            return $this->_upload_manager->files['newfile']['tmp_name'];
        }else{
            return  NULL;
        }
    }
}
?>