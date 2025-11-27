<?php
/**
 * WorkoutMate - Database Configuration
 * 
 * Este archivo contiene la configuración de conexión a MySQL
 * para uso con XAMPP en desarrollo local
 */

class Database {
    private $host = "localhost";
    private $port = "3306";
    private $db_name = "workoutmate_db";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";
    
    public $conn;

    /**
     * Obtener conexión a la base de datos
     * 
     * @return PDO|null Retorna la conexión PDO o null en caso de error
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . 
                   ";port=" . $this->port .
                   ";dbname=" . $this->db_name . 
                   ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed: " . $exception->getMessage());
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
?>
