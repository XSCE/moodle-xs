<?php

// token upgrade script -- just a noop for now
function xmldb_local_upgrade($version) {

    global $CFG;
    $result = true;

    if ($result && $oldversion < 2009052500) {

    /// Define table oat_laptops to be created
        $table = new XMLDBTable('oat_laptops');

    /// Adding fields to table oat_laptops
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('osversion', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('serialnum', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('timelastreq', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('timekilled', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('timeleasegiven', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('timestolen', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);

    /// Adding keys to table oat_laptops
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('serialnum_ux', XMLDB_KEY_UNIQUE, array('serialnum'));

    /// Launch create table for oat_laptops
        $result = $result && create_table($table);

        upgrade_main_savepoint($result, 2009052500);
    }
    return $result;
}


?>