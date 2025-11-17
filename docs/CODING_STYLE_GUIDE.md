# Coding Style Guide
## MIS Barangay - PHP Coding Standards

This document outlines the coding standards and best practices for the MIS Barangay project.

---

## General Principles

1. **Readability First**: Code should be easy to read and understand
2. **Consistency**: Follow the same patterns throughout the codebase
3. **Security**: Always prioritize security in all code
4. **Performance**: Write efficient code, but don't over-optimize prematurely

---

## PHP Code Style

### Indentation and Spacing

- Use **4 spaces** for indentation (no tabs)
- Use **1 space** after control structures (`if`, `for`, `while`, etc.)
- Use **1 space** around operators (`=`, `+`, `-`, etc.)
- No trailing whitespace at the end of lines

```php
// Good
if ($condition) {
    $result = $value + 1;
}

// Bad
if($condition){
    $result=$value+1;
}
```

### Naming Conventions

#### Variables and Functions
- Use **camelCase** for variables and functions
- Use descriptive names that explain purpose

```php
// Good
$userName = 'John';
$totalResidents = 100;

function getUserById($id) {
    // ...
}

// Bad
$un = 'John';
$tr = 100;

function get($id) {
    // ...
}
```

#### Classes
- Use **PascalCase** for class names
- Use descriptive, noun-based names

```php
// Good
class UserManager {
    // ...
}

class DatabaseConnection {
    // ...
}

// Bad
class user {
    // ...
}

class db {
    // ...
}
```

#### Constants
- Use **UPPER_SNAKE_CASE** for constants

```php
// Good
define('DB_HOST', 'localhost');
define('MAX_UPLOAD_SIZE', 5242880);

// Bad
define('dbHost', 'localhost');
define('maxUploadSize', 5242880);
```

### File Structure

#### PHP Tags
- Always use `<?php` (never short tags `<?`)
- Omit closing `?>` tag in pure PHP files

```php
<?php
// File content here
// No closing tag
```

#### Includes
- Use `include_once` or `require_once` for dependencies
- Use relative paths with `__DIR__`

```php
// Good
include_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

// Bad
include 'config.php';
require 'db.php';
```

### Functions and Methods

#### Function Declaration
- Always include PHPDoc comments
- Use type hints where possible
- Return early for error conditions

```php
/**
 * Get user by ID
 * 
 * @param int $userId User ID
 * @return array|null User data or null if not found
 */
function getUserById($userId) {
    if ($userId <= 0) {
        return null;
    }
    
    // Function logic here
    return $userData;
}
```

#### Function Parameters
- List parameters on separate lines if more than 3 parameters
- Use default values for optional parameters

```php
// Good
function createUser(
    $firstName,
    $lastName,
    $email,
    $role = 'user'
) {
    // ...
}

// Bad
function createUser($firstName, $lastName, $email, $role = 'user') {
    // ...
}
```

### Control Structures

#### If Statements
- Always use braces, even for single-line statements
- Use early returns to reduce nesting

```php
// Good
if ($condition) {
    return true;
}

if ($condition) {
    // Multiple lines
    $result = processData();
    return $result;
}

// Bad
if ($condition) return true;

if ($condition)
    return true;
```

#### Switch Statements
- Always include a `default` case
- Use `break` statements (or document intentional fall-through)

```php
// Good
switch ($status) {
    case 'active':
        $action = 'enable';
        break;
    case 'inactive':
        $action = 'disable';
        break;
    default:
        $action = 'unknown';
        break;
}
```

### Database Queries

#### Prepared Statements
- **Always** use prepared statements for user input
- Never concatenate user input directly into SQL

```php
// Good
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// Bad
$query = "SELECT * FROM users WHERE id = " . $userId;
$result = $conn->query($query);
```

#### Query Formatting
- Format complex queries across multiple lines
- Use uppercase for SQL keywords

```php
// Good
$sql = "
    SELECT 
        id,
        first_name,
        last_name,
        email
    FROM users
    WHERE status = 'active'
    ORDER BY last_name, first_name
";

// Bad
$sql = "select id, first_name, last_name, email from users where status = 'active' order by last_name, first_name";
```

### Error Handling

#### Error Logging
- Always log errors using `error_log()`
- Never expose sensitive information to users

```php
// Good
try {
    $result = $conn->query($sql);
    if ($result === false) {
        error_log("Database error: " . $conn->error);
        throw new Exception("Database query failed");
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    // Show user-friendly message
    echo "An error occurred. Please try again later.";
}

// Bad
$result = $conn->query($sql);
if (!$result) {
    echo "Error: " . $conn->error; // Exposes database details
}
```

### Security Best Practices

1. **Input Validation**: Always validate and sanitize user input
2. **Output Escaping**: Escape output to prevent XSS
3. **SQL Injection**: Use prepared statements
4. **CSRF Protection**: Implement CSRF tokens for forms
5. **Session Security**: Use secure session settings

```php
// Good - Input validation
$userId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($userId === false || $userId <= 0) {
    die("Invalid user ID");
}

// Good - Output escaping
echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

// Good - Prepared statement
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
```

### Comments and Documentation

#### PHPDoc Comments
- Document all functions and classes
- Include parameter types and return types
- Describe what the function does, not how

```php
/**
 * Calculate age from birthdate
 * 
 * @param string $birthdate Birthdate in YYYY-MM-DD format
 * @return int Age in years
 * @throws InvalidArgumentException If birthdate format is invalid
 */
function calculateAge($birthdate) {
    // Implementation
}
```

#### Inline Comments
- Use comments to explain "why", not "what"
- Keep comments up-to-date with code changes

```php
// Good
// Check if user is admin to bypass rate limiting
if ($userRole === 'admin') {
    // ...
}

// Bad
// Set $x to 5
$x = 5;
```

---

## File Organization

### Directory Structure
```
project/
├── config.php          # Configuration
├── includes/           # Core includes
│   ├── app.php        # Application bootstrap
│   ├── auth.php       # Authentication
│   ├── db.php         # Database connection
│   └── function.php   # Helper functions
├── public/            # Public-facing files
│   ├── index.php
│   ├── login.php
│   └── ...
├── schema/            # Database schemas
└── docs/              # Documentation
```

### File Naming
- Use **lowercase** with **underscores** for file names
- Match file name to primary class/function (if applicable)

```
user_manager.php
database_connection.php
resident_list.php
```

---

## Version Control

### Commit Messages
- Use clear, descriptive commit messages
- Start with a verb (Add, Fix, Update, Remove, etc.)

```
Good:
- Add user authentication system
- Fix SQL injection vulnerability in search
- Update database schema for residents table

Bad:
- changes
- fix
- update
```

---

## Additional Resources

- [PHP The Right Way](https://phptherightway.com/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)

---

**Last Updated:** 2024-01-16  
**Version:** 1.0.0

