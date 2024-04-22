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
    
            // Fungsi untuk menghitung regresi linier
            function linearRegression($x, $y) {
                $n = count($x);
                $sumX = array_sum($x); 
                $sumY = array_sum($y);
                $sumXY = 0;
                $sumXX = 0;
                for ($i = 0; $i < $n; $i++) {
                    $sumXY += $x[$i] * $y[$i];
                    $sumXX += $x[$i] * $x[$i];
                }
                $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
                $intercept = ($sumY - $slope * $sumX) / $n;
                return array('slope' => $slope, 'intercept' => $intercept);
            }
    
            // Contoh data nilai kuis siswa
            $attempts = range(1, count($quiz_averages)); // Indeks percobaan kuis
            $regression = linearRegression($attempts, $quiz_averages);
    
            // Prediksi rata-rata nilai kuis siswa berikutnya
            $nextAttempt = end($attempts) + 1;
            $predictedScore = $regression['slope'] * $nextAttempt + $regression['intercept'];
            // Tampilkan grafik
            $grades_series = new \core\chart_series('Rata-rata Nilai Kuis', $quiz_averages);
            $pass_series = new \core\chart_series('Nilai Kelulusan', $grades_to_pass);
            $predicted_series = new \core\chart_series('Prediksi Nilai Kuis Berikutnya', [$predictedScore]);
            $chart = new \core\chart_line();
            $chart->set_title('Grafik Nilai Kuis Siswa');
            $chart->add_series($grades_series);
            $chart->add_series($pass_series);
            $chart->add_series($predicted_series);
            $chart->set_labels($quiz_names);
    
            echo $OUTPUT->render($chart);
        }
    }


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
            ORDER BY gi.timecreated";  // Urutkan berdasarkan itemname
        $params = ['courseid' => $selected_courseid, 'userid' => $userid];

        // Menjalankan query.
        $grades = $DB->get_records_sql($sql, $params);
        if (empty($grades)) {
            echo '<div style="margin-bottom: 25px; text-align:center;">Tidak ada nilai kuis yang ditemukan untuk kursus ini.</div>';
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
