<style>
    select[name="courseid"] {
        padding: 10px;
        margin-right: 25px;
        border: 2px solid #ccc;
        border-radius: 10px;
        background-color: white;
        box-shadow: #0056b3;
        transition: border-color 0.3s;
    }

    select[name="courseid"]:focus {
        border-color: #ccc;
    }

    input[type="submit"] {
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        background-color: #0056b3;
        color: white;
        font-size: 16px;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: background-color 0.3s;
    }

    input[type="submit"]:hover {
        background-color: #0056b3;
    }

    .custom-select {
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ddd;
        background-color: #f8f8f8;
        margin-right: 10px;
    }

    .custom-button {
        padding: 10px 20px;
        border-radius: 5px;
        border: 1px solid #007bff;
        background-color: #007bff;
        color: #fff;
        cursor: pointer;
    }

    .custom-button:hover {
        background-color: #0056b3;
    }

    .custom-form {
        margin-top: 30px;
    }
</style>

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


function get_taught_course_count($userid)
{
    global $DB;
    $sql = "SELECT COUNT(*) FROM {course} c
            JOIN {context} ctx ON ctx.instanceid = c.id
            JOIN {role_assignments} ra ON ra.contextid = ctx.id
            WHERE ctx.contextlevel = 50 AND ra.roleid = 3 AND ra.userid = :userid";
    return $DB->count_records_sql($sql, ['userid' => $userid]);
}

function get_total_role_count($roleid)
{
    global $DB;

    $sql = "SELECT COUNT(DISTINCT ra.userid)
            FROM {role_assignments} ra
            JOIN {context} ctx ON ra.contextid = ctx.id
            JOIN {role} r ON ra.roleid = r.id
            WHERE r.id = :roleid AND ctx.contextlevel = 50";

    return $DB->count_records_sql($sql, array('roleid' => $roleid));
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
    echo '<form method="post" style="margin-top: 30px;">';
    echo '<select name="courseid">';
    foreach ($courses as $course) {
        $isSelected = ($course->id == $selected_courseid) ? 'selected' : '';
        echo '<option value="' . $course->id . '" ' . $isSelected . '>' . $course->fullname . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="Tampilkan Chart" style="margin-bottom: 25px;"/>';
    echo '</form>';
    if ($selected_courseid) {
        // Query untuk mengambil nilai tugas dan grade to pass, diurutkan berdasarkan itemname.
        $sql = "SELECT gi.itemname, gg.finalgrade, gi.gradepass, gi.itemmodule
            FROM {grade_items} gi
            JOIN {grade_grades} gg ON gi.id = gg.itemid
            WHERE gi.courseid = :courseid AND gi.itemtype = 'mod' 
            AND (gi.itemmodule = 'assign' OR gi.itemmodule = 'quiz')
            AND gg.userid = :userid
            ORDER BY gi.timecreated";
        $params = ['courseid' => $selected_courseid, 'userid' => $userid];

        // Menjalankan query.
        $grades = $DB->get_records_sql($sql, $params);
        if (empty($grades)) {
            echo '<div style="margin-bottom: 25px; text-align:center;">Tidak ada nilai kuis dan tugas yang ditemukan untuk pelajaran ini.</div>';
        } else {
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

    echo '<h4>Grafik Nilai Kuis dan Ujian</h4>';

    // Membuat dropdown mata pelajaran.
    echo '<form method="post" style="margin-top: 30px;">';
    echo '<select name="courseid">';
    foreach ($courses as $course) {
        $isSelected = ($course->id == $selected_courseid) ? 'selected' : '';
        echo '<option value="' . $course->id . '" ' . $isSelected . '>' . $course->fullname . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" name="showChart" value="Tampilkan Chart" style="margin-left: 10px;"/>';
    echo '</form>';

    if ($selected_courseid) {
        // Query untuk mengambil nilai kuis, nilai kelulusan, dan jumlah siswa.
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
            echo '<div style="margin-bottom: 25px; text-align:center;">Tidak ada nilai kuis yang ditemukan untuk kursus ini.</div>';
        } else {
            $quiz_names = [];
            $quiz_averages = [];
            $grades_to_pass = [];
            $student_counts = [];

            foreach ($grades_data as $data) {
                $quiz_names[] = $data->itemname . ' (' . $data->studentcount . ' siswa)';
                $quiz_averages[] = (float) $data->averagegrade;
                $grades_to_pass[] = (float) $data->gradepass;
                $student_counts[] = $data->studentcount;
            }

            function predict($quiz_averages)
            {
                function linearRegression($x, $y)
                {
                    $n = count($x);
                    $meanX = array_sum($x) / $n;
                    $meanY = array_sum($y) / $n;

                    $numerator = 0;
                    $denominator = 0;
                    for ($i = 0; $i < $n; $i++) {
                        $numerator += ($x[$i] - $meanX) * ($y[$i] - $meanY);
                        $denominator += pow($x[$i] - $meanX, 2);
                    }
                    $m = $numerator / $denominator;
                    $c = $meanY - $m * $meanX;

                    return [$m, $c];
                }

                $nilai = array_filter($quiz_averages, function ($i) {
                    return $i != 0;
                });

                //scaling & data normalization
                // $kuis = range(0, count($nilai) * 10, 10);
                $kuis = range(0, count($nilai));
                $kuis = array_map(function ($value) use ($quiz_averages, $nilai) {
                    $oldMin = 0;
                    $oldMax = count($quiz_averages);

                    $newMin = 0;
                    $newMax = max($nilai);
                    // $newMax = 100;
                    $newValue = (($value - $oldMin) * ($newMax - $newMin) / ($oldMax - $oldMin)) + $newMin;

                    return $newValue;
                }, $kuis);

                // print_r($kuis);
                // prediksi
                $predicted_series = array_map(function ($value, $index) use ($nilai, $kuis) {
                    if ($value == 0) {
                        list($slope, $intercept) = linearRegression($nilai, $kuis);
                        $res = $slope * ($index * 10) + $intercept;
                        return $res;
                    }
                    return $value;
                }, $quiz_averages, array_keys($quiz_averages));
                return $predicted_series;
            }

            // $quiz_averages = [80, 70, 60, 0, 0, 0, 0];
            $predicted_series = [];
            $nilai = array_filter($quiz_averages, function ($i) {
                return $i != 0;
            });
            if (count($nilai) < 2) {
                $predicted_series = array_fill(0, count($quiz_averages), $quiz_averages[0]);
            } else {
                $predicted_series = predict($quiz_averages);
            }

            $grades_series = new \core\chart_series('Rata-rata Nilai Kuis', $quiz_averages);
            $pass_series = new \core\chart_series('Nilai Kelulusan', $grades_to_pass);
            $predicted_series = new \core\chart_series('Nilai prediksi', $predicted_series);
            $chart = new \core\chart_line();
            $chart->set_title('Grafik Nilai Kuis Siswa');
            $chart->add_series($grades_series);
            $chart->add_series($pass_series);
            $chart->add_series($predicted_series);
            $chart->set_labels($quiz_names);

            echo $OUTPUT->render($chart);
        }
    }

    if ($selected_courseid) {
        // Mengambil daftar kuis untuk pelajaran yang dipilih
        $quizzes_sql = "SELECT id, name FROM {quiz} WHERE course = :courseid ORDER BY name";

        echo '<h4>Data Nilai Siswa Tidak Lulus</h4>';
        $quizzes = $DB->get_records_sql($quizzes_sql, ['courseid' => $selected_courseid]);

        echo '<form method="post" class="custom-form" style="margin-top: 30px;">';
        echo '<input type="hidden" name="courseid" value="' . $selected_courseid . '">';

        $selected_quizid = isset($_POST['quizid']) ? $_POST['quizid'] : '';

        echo '<select name="quizid" class="custom-select">';
        echo '<option value="">Pilih Detail Kuis...</option>';
        foreach ($quizzes as $quiz) {
            $selected_attribute = ($quiz->id == $selected_quizid) ? ' selected' : '';
            echo '<option value="' . $quiz->id . '"' . $selected_attribute . '>' . $quiz->name . '</option>';
        }
        echo '</select>';
        echo '<input type="submit" name="showGrades" value="Tampilkan Nilai" style="margin-left: 10px;"/>';
        echo '</form>';
    }

    if (!empty($_POST['showGrades']) && !empty($_POST['quizid'])) {
        $selected_quizid = $_POST['quizid'];
        $gradepass_sql = "SELECT gradepass
            FROM {grade_items}
            WHERE itemmodule = 'quiz' AND iteminstance = :quizid";
        $gradepass = $DB->get_field_sql($gradepass_sql, ['quizid' => $selected_quizid]);
        $grades_sql = "SELECT gg.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname, gg.finalgrade
                    FROM {grade_grades} gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid AND gi.itemmodule = 'quiz'
                    JOIN {user} u ON u.id = gg.userid
                    WHERE gi.iteminstance = :quizid AND gg.finalgrade IS NOT NULL AND gg.finalgrade < :gradepass
                    ORDER BY u.lastname, u.firstname";
        $params = ['quizid' => $selected_quizid, 'gradepass' => $gradepass];
        $grades = $DB->get_records_sql($grades_sql, $params);

        echo '<table class ="chart-output-htmltable generaltable" style="width:100%; margin-top:20px; margin-bottom:30px;">';
        echo '<tr><th>Nama Siswa</th><th>Nilai</th></tr>';
        foreach ($grades as $grade) {
            echo '<tr>';
            echo '<td>' . $grade->fullname . '</td>';
            echo '<td>' . round($grade->finalgrade, 2) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

?>