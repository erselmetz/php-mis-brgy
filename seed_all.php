<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

/* ===========================
   DB CONNECTION (MYSQLI)
=========================== */
$host = "127.0.0.1";
$user = "admin";
$pass = "phpmisbrgy";
$db   = "php_mis_brgy";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

echo "Connected successfully\n";

/* ===========================
   HELPERS
=========================== */
function randDate($start, $end)
{
    return date("Y-m-d", rand(strtotime($start), strtotime($end)));
}

$firstNames = ['Juan', 'Pedro', 'Jose', 'Maria', 'Ana', 'Luis', 'Carla', 'Ramon', 'Liza', 'Mark'];
$lastNames  = ['Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Rizal', 'Torres', 'Mendoza', 'Lopez', 'Diaz'];

function fakeName($f, $l)
{
    return $f[array_rand($f)] . " " . $l[array_rand($l)];
}

/* ===========================
   CLEAR TABLES
=========================== */
$tables = [
    'blotter_history',
    'blotter',
    'certificate_request',
    'medicine_dispense',
    'inventory_audit_trail',
    'inventory',
    'medicines',
    'medicine_categories',
    'consultations',
    'immunizations',
    'health_metrics',
    'residents',
    'families',
    'households',
    'events'
];

foreach ($tables as $t) {
    $conn->query("DELETE FROM `$t`");
}
echo "Tables cleared\n";

/* ===========================
   HOUSEHOLDS (100)
=========================== */
for ($i = 1; $i <= 100; $i++) {
    $conn->query("
        INSERT INTO households (household_no, address, head_name)
        VALUES ('H-$i','Barangay Bombongan Purok " . rand(1, 7) . "', '" . fakeName($firstNames, $lastNames) . "')
    ");
}
echo "Households seeded\n";

/* ===========================
   FAMILIES (100)
=========================== */
// for ($i=1;$i<=100;$i++) {
//     $conn->query("
//         INSERT INTO families (household_id, family_name)
//         VALUES (".rand(1,100).",'".fakeName($firstNames,$lastNames)."')
//     ");
// }
// echo "Families seeded\n";

/* ===========================
   RESIDENTS (100)
=========================== */
for ($i = 1; $i <= 100; $i++) {
    $conn->query("
        INSERT INTO residents
        (first_name,last_name,birthdate,gender,civil_status,religion,occupation,address,contact_no,citizenship)
        VALUES (
            '" . $firstNames[array_rand($firstNames)] . "',
            '" . $lastNames[array_rand($lastNames)] . "',
            '" . randDate('1980-01-01', '2015-12-31') . "',
            '" . (rand(0, 1) ? 'Male' : 'Female') . "',
            'Single',
            'Catholic',
            'Worker',
            'Barangay Bombongan',
            '09" . rand(100000000, 999999999) . "',
            'Filipino'
        )
    ");
}
echo "Residents seeded\n";

/* ===========================
   HEALTH METRICS (100)
=========================== */
for ($i = 1; $i <= 100; $i++) {
    $conn->query("
        INSERT INTO health_metrics
        (resident_id,height,weight,blood_pressure,recorded_at)
        VALUES (
            " . rand(1, 100) . ",
            " . rand(140, 180) . ",
            " . rand(45, 90) . ",
            '" . rand(100, 130) . "/" . rand(70, 90) . "',
            NOW()
        )
    ");
}
echo "Health metrics seeded\n";

/* ===========================
   CONSULTATIONS (100)
=========================== */
for ($i = 1; $i <= 100; $i++) {
    $conn->query("
        INSERT INTO consultations
        (resident_id,complaint,diagnosis,consultation_date)
        VALUES (
            " . rand(1, 100) . ",
            'Fever',
            'Viral Infection',
            '" . randDate('2023-01-01', '2024-12-31') . "'
        )
    ");
}
echo "Consultations seeded\n";

/* ===========================
   IMMUNIZATIONS (100)
=========================== */
for ($i = 1; $i <= 100; $i++) {
    $conn->query("
        INSERT INTO immunizations
        (resident_id,vaccine_name,date_given)
        VALUES (
            " . rand(1, 100) . ",
            'COVID-19',
            '" . randDate('2021-01-01', '2023-12-31') . "'
        )
    ");
}
echo "Immunizations seeded\n";

/* ===========================
   MEDICINE CATEGORIES (10)
=========================== */
for ($i = 1; $i <= 10; $i++) {
    $conn->query("INSERT INTO medicine_categories (name) VALUES ('Category $i')");
}

/* ===========================
   MEDICINES (100)
=========================== */
for ($i = 1; $i <= 100; $i++) {
    $conn->query("
        INSERT INTO medicines (name,category_id,stock_qty,expiration_date)
        VALUES (
            'Medicine $i',
            " . rand(1, 10) . ",
            " . rand(10, 500) . ",
            '" . randDate('2025-01-01', '2028-12-31') . "'
        )
    ");
}
echo "Medicines seeded\n";

/* ===========================
   MEDICINE DISPENSE (100)
=========================== */
for ($i = 1; $i <= 100; $i++) {
    $conn->query("
        INSERT INTO medicine_dispense
        (resident_id,medicine_id,quantity,dispense_date)
        VALUES (
            " . rand(1, 100) . ",
            " . rand(1, 100) . ",
            " . rand(1, 5) . ",
            NOW()
        )
    ");
}
echo "Medicine dispense seeded\n";