<?php 

require_once(__DIR__ . '/../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="exported_users.csv"');

// Membuka output stream
$output = fopen('php://output', 'w');

// Menulis header kolom
fputcsv($output, ['id', 'username', 'email', 'firstname', 'lastname']);

// Mengambil dan menulis data pengguna
$users = $DB->get_records('user', ['deleted' => 0], 'lastname ASC');
foreach ($users as $user) {
    fputcsv($output, [$user->id, $user->username, $user->email, $user->firstname, $user->lastname]);
}

fclose($output);
exit;
?>