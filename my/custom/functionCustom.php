<?php 

/**
 * My Moodle -- a user's personal dashboard
 *
 * This file contains common functions for the dashboard and profile pages.
 *
 * @package    moodlecore
 * @subpackage my
 * @copyright  2010 Remote-Learner.net
 * @author     Hubert Chathi <hubert@remote-learner.net>
 * @author     Olav Jordan <olav.jordan@remote-learner.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define('MY_PAGE_PUBLIC', 0);
 define('MY_PAGE_PRIVATE', 1);
 define('MY_PAGE_DEFAULT', '__default');
 define('MY_PAGE_COURSES', '__courses');
 
 require_once("$CFG->libdir/blocklib.php");
 
 /**
  * For a given user, this returns the $page information for their My Moodle page
  *
  * @param int|null $userid the id of the user whose page should be retrieved
  * @param int|null $private either MY_PAGE_PRIVATE or MY_PAGE_PUBLIC
  * @param string|null $pagename Differentiate between standard /my or /courses pages.
  */


function get_taught_course_count($userid) {
    global $DB;
    $sql = "SELECT COUNT(*) FROM {course} c
            JOIN {context} ctx ON ctx.instanceid = c.id
            JOIN {role_assignments} ra ON ra.contextid = ctx.id
            WHERE ctx.contextlevel = 50 AND ra.roleid = 3 AND ra.userid = :userid";
    return $DB->count_records_sql($sql, ['userid' => $userid]);
}

//fungsi chart
function chartsiswa()
{
    global $USER, $DB, $OUTPUT;
    $userid = $USER->id;
    $selected_courseid = !empty($_POST['courseid']) ? $_POST['courseid'] : null;

    // Mengambil kursus yang diikuti pengguna.
    $sql = "SELECT c.id, c.fullname FROM {course} c
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE ue.userid = :userid";
    $params = ['userid' => $userid];
    $courses = $DB->get_records_sql($sql, $params);

    // Membuat dropdown.
    echo '<form method="post">';
    echo '<select name="courseid">';
    foreach ($courses as $course) {
        $isSelected = ($course->id == $selected_courseid) ? 'selected' : '';
        echo '<option value="' . $course->id . '" ' . $isSelected . '>' . $course->fullname . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="Tampilkan Chart"/>';
    echo '</form>';
    if ($selected_courseid) {
        // Query untuk mengambil nilai tugas dan grade to pass, diurutkan berdasarkan itemname.
        $sql = "SELECT gi.itemname, gg.finalgrade, gi.gradepass, gi.itemmodule
            FROM {grade_items} gi
            JOIN {grade_grades} gg ON gi.id = gg.itemid
            WHERE gi.courseid = :courseid AND gi.itemtype = 'mod' 
            AND (gi.itemmodule = 'assign' OR gi.itemmodule = 'quiz')
            AND gg.userid = :userid
            ORDER BY gi.timecreated";  // Urutkan berdasarkan itemname
        $params = ['courseid' => $selected_courseid, 'userid' => $userid];

        // Menjalankan query.
        $grades = $DB->get_records_sql($sql, $params);

        // Mengolah data untuk chart...
        $assignment_names = [];
        $assignment_grades = [];
        $grades_to_pass = [];

        foreach ($grades as $grade) {
            $assignment_names[] = $grade->itemname;
            $assignment_grades[] = (float) $grade->finalgrade;
            $grades_to_pass[] = (float) $grade->gradepass; // Menambahkan grade to pass
        }

        // Membuat chart bar.
        $grades_series = new \core\chart_series('Nilai Anda', $assignment_grades);
        $pass_series = new \core\chart_series('Nilai Kelulusan', $grades_to_pass); // Series baru untuk grade to pass
        $chart = new \core\chart_line();
        $chart->set_smooth(true);
        $chart->set_title('Grafik Nilai Mata Pelajaran');
        $chart->add_series($grades_series);
        $chart->add_series($pass_series); // Menambahkan series grade to pass ke chart
        $chart->set_labels($assignment_names);

        // Menampilkan chart.
        echo $OUTPUT->render($chart);
    }
}

function chartGuru()
{
    global $OUTPUT, $DB, $USER;

    $userid = $USER->id;
    // Cek kursus yang dipilih.
    $selected_courseid = !empty($_POST['courseid']) ? $_POST['courseid'] : null;

    // Mengambil kursus yang diampu oleh guru.
    $sql = "SELECT c.id, c.fullname FROM {course} c
            JOIN {context} ctx ON ctx.instanceid = c.id
            JOIN {role_assignments} ra ON ra.contextid = ctx.id
            WHERE ctx.contextlevel = 50 AND ra.roleid = 3 AND ra.userid = :userid";
    $params = ['userid' => $userid];
    $courses = $DB->get_records_sql($sql, $params);

    // Membuat dropdown.
    echo '<form method="post">';
    echo '<select name="courseid" style="margin-right: 25px;">';
    foreach ($courses as $course) {
        $isSelected = ($course->id == $selected_courseid) ? 'selected' : '';
        echo '<option value="' . $course->id . '" ' . $isSelected . '>' . $course->fullname . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="Tampilkan Chart" style="margin-bottom: 45px;"/>';
    echo '</form>';

    // Jika kursus telah dipilih, jalankan query untuk nilai dan buat chart.
    if ($selected_courseid) {
        // Query untuk mengambil nilai kuis, grade to pass, dan jumlah siswa.
        $sql = "SELECT gi.itemname, AVG(gg.finalgrade) AS averagegrade, gi.gradepass, COUNT(DISTINCT CASE WHEN gg.finalgrade IS NOT NULL THEN gg.userid END) AS studentcount
                FROM {grade_items} gi
                JOIN {grade_grades} gg ON gi.id = gg.itemid
                JOIN {course} c ON gi.courseid = c.id
                WHERE gi.courseid = :courseid AND gi.itemmodule = 'quiz' AND gi.itemtype = 'mod'
                GROUP BY gi.itemname, gi.gradepass
                ORDER BY gi.itemname";


        $params = ['courseid' => $selected_courseid];
        $grades_data = $DB->get_records_sql($sql, $params);

        if (empty($grades_data)) {
            echo '<div style="margin-bottom: 25px;">Tidak ada nilai kuis yang ditemukan untuk kursus ini.</div>';
        } else {
            // Mengolah data untuk chart.
            $quiz_names = [];
            $quiz_averages = [];
            $grades_to_pass = [];
            $student_counts = [];

            foreach ($grades_data as $data) {
                $quiz_name = $data->itemname . ' (' . $data->studentcount . ' siswa Mengerjakan)'; // Menambahkan jumlah siswa ke label
                $quiz_names[] = $quiz_name;
                $quiz_averages[] = (float) $data->averagegrade;
                $grades_to_pass[] = (float) $data->gradepass;
                $student_counts[] = $data->studentcount;
            }

            // Membuat series untuk chart.
            $grades_series = new \core\chart_series('Rata-rata Nilai Kuis', $quiz_averages);
            $pass_series = new \core\chart_series('Nilai Kelulusan', $grades_to_pass);

            // Membuat chart bar.
            $chart = new \core\chart_bar();
            $chart->set_title('Grafik Nilai Kuis');
            $chart->add_series($grades_series);
            $chart->add_series($pass_series);
            $chart->set_labels($quiz_names);

            // Menampilkan informasi jumlah siswa di setiap label
            foreach ($quiz_names as $index => $name) {
                $student_count = $student_counts[$index];
                $quiz_names[$index] = "{$name} ({$student_count} siswa Mengerjakan)";
            }

            // Menampilkan chart.
            echo $OUTPUT->render($chart);
        }
    }
}

?>