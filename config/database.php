<?php
class Database {
    private $host = '127.0.0.1';
    private $port = '3306';
    private $db_name = 'kormoshathi';
    private $username = 'root';
    private $password = 'Abdul123'; // পাসওয়ার্ড থেকে ! চিহ্ন বাদ দেওয়া হয়েছে
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            // ডিবাগ করার জন্য সরাসরি এরর দেখাবে
            die("Database Connection Failed: " . $e->getMessage()); 
        }
        return $this->conn;
    }
}
?>
