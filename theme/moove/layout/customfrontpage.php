<?php 
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/../../config.php');

function get_user_counts() {
    global $DB;

    // Definisikan ID role untuk guru dan siswa.
    // Catatan: Anda harus mengonfirmasi ID role yang benar dalam konteks situs Moodle Anda.
    $teacher_role_id = 3; // Contoh: ID role untuk guru.
    $student_role_id = 5; // Contoh: ID role untuk siswa.

    // Query untuk mendapatkan jumlah guru.
    $teacher_count = $DB->count_records_sql("
        SELECT COUNT(DISTINCT userid)
        FROM {role_assignments}
        WHERE roleid = ?", array($teacher_role_id));

    // Query untuk mendapatkan jumlah siswa.
    $student_count = $DB->count_records_sql("
        SELECT COUNT(DISTINCT userid)
        FROM {role_assignments}
        WHERE roleid = ?", array($student_role_id));

    return array($teacher_count, $student_count);
}

// Di dalam fungsi render Anda, tambahkan:
$data = theme_moove_get_extra_data();
echo $OUTPUT->render_from_template('theme_moove/frontpage', $data);

?>