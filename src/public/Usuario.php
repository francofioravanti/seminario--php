<?php
class Usuario{

    

    


    public function registrarUsuario(string $nombre,string $usuario, string $clave): array {
        $errores = [];

        if (!$this->validarNombreUsuario($usuario, $errores)) return $errores; 
        if (!$this->validarClave($clave, $errores)) return $errores;

        $db = (new Conexion())->getDb(); 

        if ($this->existeUsuario($usuario)) {  
            $errores[] = "El nombre de usuario ya está en uso.";
            return $errores;
        }
        

        
        $stmt = $db->prepare("INSERT INTO usuario (nombre,usuario,password) VALUES (:nombre,:usuario, :clave)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':clave', $clave); 
        

    

        if ($stmt->execute()) {
            
            return [];
        } else {
            $error = $stmt->errorInfo();
            
            $errores[] = "Error al registrar usuario.";
            return $errores;
        }
    }


    private function existeUsuario(string $usuario): bool {
        $db = (new Conexion())->getDb();
        $stmt = $db->prepare("SELECT id FROM usuario WHERE usuario = ?");
        $stmt->execute([$usuario]);
        return (bool) $stmt->fetch(); 
    }
    public function validarNombreUsuario(string $usuario, array &$errores): bool { 
        
        if (strlen($usuario) < 6 || strlen($usuario) > 20) {
            $errores[] = "El nombre de usuario debe tener entre 6 y 20 caracteres.";
            return false;
        }

        if (!ctype_alnum($usuario)) {
            $errores[] = "El nombre de usuario solo puede contener caracteres alfanuméricos.";
            return false;
        }

        return true;
    }
    public function validarUsuario($usuario, $clave): array|false {
    $db = (new Conexion())->getDb();
    $query = "SELECT * FROM usuario WHERE usuario = :usuario";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $clave === $result['password']) {
        return $result; 
    }

    return false;
}

    public function validarClave(string $clave, array &$errores): bool {
        $reglas = [
            'La clave debe tener al menos 8 caracteres.' => strlen($clave) < 8,
            'La clave debe contener al menos una letra mayúscula.' => !preg_match('/[A-Z]/', $clave),
            'La clave debe contener al menos una letra minúscula.' => !preg_match('/[a-z]/', $clave),
            'La clave debe contener al menos un número.' => !preg_match('/[0-9]/', $clave),
            'La clave debe contener al menos un caracter especial.' => !preg_match('/[\W_]/', $clave),
        ];
    
        foreach ($reglas as $mensaje => $condicion) {
            if ($condicion) {
                $errores[] = $mensaje;
            }
        }
    
        return count($errores) === 0;
    }
    

    public function actualizarCredencialesPorNombre(string $username, string $nombre, string $clave): bool {
        $db = (new Conexion())->getDb();
        $query = "UPDATE usuario SET nombre = :nombre, password = :password WHERE usuario = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':password', $clave);
        $stmt->bindParam(':username', $username);

        return $stmt->execute();
    }

    
    public function info($id): array {
    $db = (new Conexion())->getDb();
    $stmt = $db->prepare("SELECT * FROM usuario WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data ?: [];
}

}
