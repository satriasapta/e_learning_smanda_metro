<?php
defined('MOODLE_INTERNAL') || die();

function local_onerole_before_role_assign(\core\event\role_assigned $event) {
    global $DB;

    $userid = $event->relateduserid;
    $roleid = $event->objectid;

    // Periksa apakah pengguna sudah memiliki peran sebagai siswa di kursus lain.
    $studentRole = $DB->get_record('role', array('shortname' => 'student'));
    $isStudentElsewhere = $DB->record_exists('role_assignments', array('userid' => $userid, 'roleid' => $studentRole->id));

    // Periksa apakah pengguna sudah memiliki peran sebagai guru di kursus lain.
    $teacherRole = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $isTeacherElsewhere = $DB->record_exists('role_assignments', array('userid' => $userid, 'roleid' => $teacherRole->id));

    // Jika pengguna sudah menjadi siswa di tempat lain, mereka tidak dapat menjadi guru.
    // Begitu juga sebaliknya.
    if (($isStudentElsewhere && $roleid == $teacherRole->id) || ($isTeacherElsewhere && $roleid == $studentRole->id)) {
        throw new moodle_exception('erroruseronerole', 'local_onerole');
    }
}