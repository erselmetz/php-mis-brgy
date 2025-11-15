# Database Schema Documentation
## MIS Barangay Management Information System

**Last Updated:** 2024-01-16  
**Database Engine:** MySQL/MariaDB (InnoDB)  
**Character Set:** utf8mb4

---

## Table Overview

The database consists of 7 main tables:
1. `users` - System user accounts
2. `residents` - Barangay residents information
3. `households` - Household records
4. `families` - Family records
5. `officers` - Barangay officers information
6. `blotter` - Blotter/incident cases
7. `certificate_request` - Certificate requests

---

## Table Details

### 1. `users`
Stores system user accounts with authentication and authorization information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| `username` | VARCHAR(255) | NOT NULL | Login username |
| `name` | VARCHAR(255) | NOT NULL | Full name of user |
| `role` | VARCHAR(244) | NOT NULL | User role: 'admin', 'staff', 'tanod' |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT 'active' | Account status: 'active', 'disabled' |
| `password` | VARCHAR(255) | NOT NULL | Hashed password (bcrypt) |
| `profile_picture` | VARCHAR(255) | NULL | Path to profile picture |
| `position` | VARCHAR(150) | NULL | Position (for non-officer staff) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`

**Relationships:**
- `officers.user_id` → `users.id` (Foreign Key: ON DELETE SET NULL)

---

### 2. `residents`
Stores detailed information about barangay residents.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique resident identifier |
| `household_id` | INT(11) UNSIGNED | NULL, FOREIGN KEY | Reference to household |
| `first_name` | VARCHAR(100) | NOT NULL | First name |
| `middle_name` | VARCHAR(100) | NULL | Middle name |
| `last_name` | VARCHAR(100) | NOT NULL | Last name |
| `suffix` | VARCHAR(10) | NULL | Name suffix (Jr., Sr., etc.) |
| `gender` | ENUM('Male', 'Female') | NOT NULL | Gender |
| `birthdate` | DATE | NOT NULL | Date of birth |
| `birthplace` | VARCHAR(255) | NULL | Place of birth |
| `civil_status` | ENUM('Single', 'Married', 'Widowed', 'Separated') | DEFAULT 'Single' | Civil status |
| `religion` | VARCHAR(100) | NULL | Religion |
| `occupation` | VARCHAR(150) | NULL | Occupation |
| `citizenship` | VARCHAR(100) | DEFAULT 'Filipino' | Citizenship |
| `contact_no` | VARCHAR(20) | NULL | Contact number |
| `address` | VARCHAR(100) | NULL | Address |
| `voter_status` | ENUM('Yes', 'No') | DEFAULT 'No' | Voter registration status |
| `disability_status` | ENUM('Yes', 'No') | DEFAULT 'No' | Disability status |
| `remarks` | TEXT | NULL | Additional remarks |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- FOREIGN KEY on `household_id` → `households.id`

**Relationships:**
- `household_id` → `households.id` (ON DELETE SET NULL, ON UPDATE CASCADE)
- `officers.resident_id` → `residents.id` (ON DELETE SET NULL)

---

### 3. `households`
Stores household information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique household identifier |
| `household_no` | VARCHAR(100) | NOT NULL, UNIQUE | Household number |
| `address` | VARCHAR(255) | NOT NULL | Household address |
| `head_name` | VARCHAR(150) | NOT NULL | Name of household head |
| `total_members` | INT(3) | DEFAULT 0 | Total number of members |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `household_no`

**Relationships:**
- `residents.household_id` → `households.id`
- `families.household_id` → `households.id`

---

### 4. `families`
Stores family information linked to households.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique family identifier |
| `household_id` | INT(11) UNSIGNED | NULL, FOREIGN KEY | Reference to household |
| `family_name` | VARCHAR(150) | NOT NULL | Family name |
| `total_members` | INT(3) | DEFAULT 0 | Total family members |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- FOREIGN KEY on `household_id` → `households.id`

**Relationships:**
- `household_id` → `households.id` (ON DELETE SET NULL, ON UPDATE CASCADE)

---

### 5. `officers`
Stores barangay officers information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique officer identifier |
| `user_id` | INT(11) | NULL, FOREIGN KEY | Reference to user account |
| `resident_id` | INT(11) UNSIGNED | NULL, FOREIGN KEY | Reference to resident (if officer is a resident) |
| `position` | VARCHAR(150) | NOT NULL | Officer position (e.g., Barangay Captain) |
| `term_start` | DATE | NOT NULL | Term start date |
| `term_end` | DATE | NOT NULL | Term end date |
| `status` | ENUM('Active', 'Inactive') | DEFAULT 'Active' | Officer status |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- FOREIGN KEY on `user_id` → `users.id`
- FOREIGN KEY on `resident_id` → `residents.id`

**Relationships:**
- `user_id` → `users.id` (ON DELETE SET NULL, ON UPDATE CASCADE)
- `resident_id` → `residents.id` (ON DELETE SET NULL, ON UPDATE CASCADE)

**Note:** Every officer must have a user account (`user_id`). The `resident_id` is optional if the officer is not a registered resident.

---

### 6. `blotter`
Stores blotter/incident case records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Unique case identifier |
| `case_number` | VARCHAR(50) | NOT NULL, UNIQUE | Auto-generated case number (format: BLT-YYYY-####) |
| `complainant_name` | VARCHAR(255) | NOT NULL | Complainant's name |
| `complainant_address` | TEXT | NULL | Complainant's address |
| `complainant_contact` | VARCHAR(20) | NULL | Complainant's contact number |
| `respondent_name` | VARCHAR(255) | NOT NULL | Respondent's name |
| `respondent_address` | TEXT | NULL | Respondent's address |
| `respondent_contact` | VARCHAR(20) | NULL | Respondent's contact number |
| `incident_date` | DATE | NOT NULL | Date of incident |
| `incident_time` | TIME | NULL | Time of incident |
| `incident_location` | TEXT | NOT NULL | Location of incident |
| `incident_description` | TEXT | NOT NULL | Description of incident |
| `status` | ENUM('pending', 'under_investigation', 'resolved', 'dismissed') | DEFAULT 'pending' | Case status |
| `resolution` | TEXT | NULL | Resolution details |
| `resolved_date` | DATE | NULL | Date case was resolved |
| `created_by` | INT(11) | NOT NULL, FOREIGN KEY | User who created the case |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `case_number`
- INDEX on `status`
- INDEX on `incident_date`
- INDEX on `created_by`

**Relationships:**
- `created_by` → `users.id` (ON DELETE RESTRICT)

**Case Number Format:** `BLT-YYYY-####` (e.g., BLT-2024-0001)

---

### 7. `certificate_request`
Stores certificate requests from residents.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique request identifier |
| `resident_id` | INT(11) UNSIGNED | NOT NULL, FOREIGN KEY | Reference to resident |
| `certificate_type` | VARCHAR(100) | NOT NULL | Type of certificate requested |
| `purpose` | VARCHAR(255) | NOT NULL | Purpose of certificate |
| `issued_by` | INT(11) | NOT NULL | User who issued the certificate |
| `status` | VARCHAR(50) | DEFAULT 'Pending' | Request status |
| `requested_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Request timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- FOREIGN KEY on `resident_id` → `residents.id`

**Relationships:**
- `resident_id` → `residents.id` (ON DELETE CASCADE)

---

## Entity Relationship Diagram (Text)

```
users (1) ────< (0..1) officers
  │
  │ (1)
  │
  └───< (N) blotter.created_by

households (1) ────< (N) residents
households (1) ────< (N) families

residents (1) ────< (0..1) officers.resident_id
residents (1) ────< (N) certificate_request
```

---

## Database Constraints Summary

### Foreign Keys
1. `residents.household_id` → `households.id` (ON DELETE SET NULL)
2. `families.household_id` → `households.id` (ON DELETE SET NULL)
3. `officers.user_id` → `users.id` (ON DELETE SET NULL)
4. `officers.resident_id` → `residents.id` (ON DELETE SET NULL)
5. `blotter.created_by` → `users.id` (ON DELETE RESTRICT)
6. `certificate_request.resident_id` → `residents.id` (ON DELETE CASCADE)

### Unique Constraints
1. `households.household_no` - UNIQUE
2. `blotter.case_number` - UNIQUE
3. `users.username` - Should be unique (application level)

---

## Migration History

The database schema is managed through migration scripts in the `schema/` directory:

1. **Initial Setup:**
   - `create_users_table.php`
   - `create_households_table.php`
   - `create_families_table.php`
   - `create_residents_table.php`
   - `create_certificates_request_table.php`
   - `create_blotter_table.php`
   - `create_officers_table.php`

2. **Feature Additions:**
   - `add_profile_picture_to_users.php` - Adds profile picture support

3. **Structural Changes:**
   - `merge_staff_officers.php` - Merges staff and officers management

Run migrations using: `php schema/run.php` (CLI) or visit `/schema/run.php` (browser, admin only)

---

## Notes

- All timestamps use `TIMESTAMP` type with automatic defaults
- Character encoding is `utf8mb4` to support full Unicode including emojis
- All tables use `InnoDB` engine for foreign key support and transactions
- Age is calculated dynamically from `birthdate`, not stored
- Case numbers are auto-generated with format: `BLT-YYYY-####`

---

**Document Version:** 1.0  
**Maintained By:** Development Team

