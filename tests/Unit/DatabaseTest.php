<?php
/**
 * Unit Tests for Database Operations
 * 
 * @group database
 */
class DatabaseTest extends PHPUnit\Framework\TestCase
{
    protected $conn;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../includes/db.php';
        $this->conn = $GLOBALS['conn'] ?? null;
        
        if (!$this->conn) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     * Test database connection
     */
    public function testDatabaseConnection()
    {
        $this->assertNotNull($this->conn);
        $this->assertInstanceOf(mysqli::class, $this->conn);
    }

    /**
     * Test users table exists
     */
    public function testUsersTableExists()
    {
        $result = $this->conn->query("SHOW TABLES LIKE 'users'");
        $this->assertEquals(1, $result->num_rows);
    }

    /**
     * Test residents table exists
     */
    public function testResidentsTableExists()
    {
        $result = $this->conn->query("SHOW TABLES LIKE 'residents'");
        $this->assertEquals(1, $result->num_rows);
    }

    /**
     * Test prepared statement execution
     */
    public function testPreparedStatement()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = ?");
        $role = 'admin';
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $this->assertIsInt((int)$row['count']);
        $stmt->close();
    }

    /**
     * Test database charset is utf8mb4
     */
    public function testDatabaseCharset()
    {
        $result = $this->conn->query("SELECT @@character_set_database as charset");
        $row = $result->fetch_assoc();
        $this->assertEquals('utf8mb4', $row['charset']);
    }
}

