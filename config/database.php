<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Render-এর Environment Variables থেকে ডেটা নেবে, না পেলে লোকাল ডিফল্ট ভ্যালু নেবে
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->port = getenv('DB_PORT') ?: '3306';
        $this->db_name = getenv('DB_NAME') ?: 'kormoshathi';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: 'Abdul123!';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Aiven-এর জন্য SSL কানেকশন সেটআপ (লোকালহোস্ট ছাড়া বাকি সব জায়গায়)
            if ($this->host !== '127.0.0.1' && $this->host !== 'localhost') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            // এরর হলে JSON ফরম্যাটে রেসপন্স দেবে, যাতে ফ্রন্টএন্ড জাভাস্ক্রিপ্ট "Connection Error" না দিয়ে আসল এররটি দেখাতে পারে
            header('Content-Type: application/json');
            error_log("Connection Error: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Database Connection Failed: " . $e->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}
?>
