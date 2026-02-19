<?php
// schema/create_care_visits_table.php
include '../includes/db.php'; // adjust if needed (same pattern ng other schema)

$sql = "
CREATE TABLE IF NOT EXISTS care_visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resident_id INT NOT NULL,
  care_type ENUM('maternal','family_planning','prenatal','postnatal','child_nutrition') NOT NULL,
  visit_date DATE NOT NULL,
  details LONGTEXT NULL,   -- JSON string (type-specific data)
  notes TEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_care_resident (resident_id),
  INDEX idx_care_type_date (care_type, visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql)) {
  echo "✅ care_visits table ready!\n";
} else {
  echo "❌ Error: " . $conn->error . "\n";
}
