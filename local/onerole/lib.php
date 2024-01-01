<?php
defined('MOODLE_INTERNAL') || die();

function local_onerole_before_role_assign(\core\event\role_assigned $event) {
    global $DB;

    $userid = $event->relateduserid;
    $newRoleId = $event->objectid;

    // Dapatkan ID peran untuk siswa dan guru
    $studentRoleId = $DB->get_field('role', 'id', array('shortname' => 'student'));
    $teacherRoleId = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));

    // Jika peran baru bukan salah satu dari peran ini, abaikan pemeriksaan
    if ($newRoleId != $studentRoleId && $newRoleId != $teacherRoleId) {
        return;
    }

    // Periksa apakah pengguna sudah memiliki peran sebagai siswa atau guru di tempat lain
    $existingRoles = $DB->get_records_sql(
        'SELECT * FROM {role_assignments} WHERE userid = ? AND roleid IN (?, ?)',
        array($userid, $studentRoleId, $teacherRoleId)
    );

    if ($existingRoles) {
        throw new moodle_exception('erroruseronerole', 'local_onerole', '', '', "User cannot have both student and teacher roles.");
    }
}