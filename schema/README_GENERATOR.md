# Database SQL Generator for MIS Barangay

This is a flexible system that automatically generates `database_complete.sql` from individual schema files.

## 📁 Files Included

- **generate_sql.bat** - Batch file to run the generator (double-click to execute)
- **generate_sql.php** - PHP script that processes schema files and generates SQL
- **database_complete.sql** - The generated SQL file (auto-created/updated)

## 🚀 How to Use

### Method 1: Quick (Recommended)
1. Go to the `schema` folder
2. **Double-click `generate_sql.bat`**
3. A window will open, process files, and close when done
4. `database_complete.sql` will be updated

### Method 2: Command Line
```bash
cd C:\laragon\www\mis-brgy2\schema
generate_sql.bat
```

### Method 3: Run PHP directly
```bash
php generate_sql.php
```

## ✨ Features

✅ **Automatic SQL Generation** - Reads all schema PHP files and extracts SQL  
✅ **Organized by Sections** - Initial Setup, Feature Additions, Structural Changes  
✅ **Auto-adds Indexes** - Includes performance indexes automatically  
✅ **Flexible** - Add new schema files anytime, just run the generator again  
✅ **Easy Updates** - Make changes to individual schema files, regenerate once  

## 📝 How to Add New Schema Files

1. Create your new schema PHP file in the `schema` folder
   - Example: `create_my_new_table.php`

2. Add your SQL inside a `$sql` variable:
```php
<?php
include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS my_new_table (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table created successfully.";
} else {
    echo "❌ Error: " . $conn->error;
}
$conn->close();
?>
```

3. Add the filename to `generate_sql.php` in the appropriate category:

```php
'Initial Setup' => [
    'files' => [
        // ... existing files ...
        'create_my_new_table.php',  // ← Add this line
    ]
]
```

4. **Run the generator:**
   - Double-click `generate_sql.bat`
   - Or run `php generate_sql.php`

5. The `database_complete.sql` file will be automatically updated!

## 🔄 Workflow Example

### Scenario: You want to add a new "projects" table

1. Create `schema/create_projects_table.php`:
```php
<?php
include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS projects (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'projects' created successfully.";
} else {
    echo "❌ Error: " . $conn->error;
}
$conn->close();
?>
```

2. Add to `generate_sql.php` under 'Initial Setup':
```php
'files' => [
    // ... existing files ...
    'create_projects_table.php',  // ← Your new file
]
```

3. Double-click `generate_sql.bat` in the schema folder

4. **Done!** Your `database_complete.sql` now includes the new table

## 📋 Categories

### Initial Setup
- `create_*.php` - Creates base tables

### Feature Additions
- `add_*.php` - Adds columns to existing tables
- `fix_*.php` - Fixes column definitions
- `create_*_table.php` (enhancements) - Creates additional tables

### Structural Changes
- `merge_*.php` - Merges/reorganizes data
- `make_*.php` - Makes structural changes
- `enhance_*.php` - Enhances existing tables

## ✅ What the Generator Does

1. Reads all PHP files in the schema folder
2. Extracts SQL from `$sql` variables using regex
3. Organizes SQL by category (Initial Setup → Feature Additions → Structural Changes)
4. Adds helpful comments and headers
5. Includes performance indexes
6. Outputs complete `database_complete.sql`

## 🎯 Best Practices

- ✅ ***Keep one table per file** - Makes management easier
- ✅ **Use IF NOT EXISTS** - Prevents errors on reimporting
- ✅ **Add comments** - Help future developers understand the schema
- ✅ **Run generator after changes** - Keep database_complete.sql in sync
- ✅ **Test new tables** - Run the PHP file individually first to catch errors

## 🐛 Troubleshooting

### "PHP is not installed or not in PATH"
- Make sure Laragon is running
- Or add PHP to your system PATH

### "File not found" warning
- Check the filename in `generate_sql.php` matches actual file
- Ensure file is in the schema folder

### SQL not being extracted
- Check that your SQL is in a `$sql` variable
- Make sure it's properly quoted with `"` or `'`

## 📊 Output Example

When you run the generator, you'll see:
```
=====================================================
  MIS Barangay - Database SQL Generator
=====================================================

Schema Directory: C:\laragon\www\mis-brgy2\schema\
Output File: C:\laragon\www\mis-brgy2\schema\database_complete.sql

[*] PHP found: checking version...
PHP 8.1.0 (cli) ...

[*] Generating database_complete.sql...

✅ SUCCESS: database_complete.sql has been generated!
Files processed: 30/30
```

## 💡 Pro Tip

You can add this to Windows Task Scheduler to regenerate the SQL file automatically on a schedule:
- Task: `C:\laragon\www\mis-brgy2\schema\generate_sql.bat`
- Frequency: Daily or after each development session

---

**Created for:** MIS Barangay Database Management System  
**Version:** 1.0  
**Last Updated:** March 2026
