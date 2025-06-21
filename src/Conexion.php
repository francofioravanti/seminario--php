<?php

class Conexion {
    private $host = "localhost";
    private $user = "root";
    private $db = "pokemones";
    private $pass = "";
    private $charset = "utf8mb4";
    private ?PDO $pdo = null; 

    public function conectar() {
        try {
            
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}",
                $this->user,
                $this->pass
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
           
        } catch (PDOException $e) {
            die("Error de conexion: " . $e->getMessage());
        }
    }

    public function getDb(): PDO {
        if ($this->pdo === null) { 
            $this->conectar();
        }
        return $this->pdo;
    }

    public function cerrarConexion() {
        $this->pdo = null; 
    }
    
}
?>

