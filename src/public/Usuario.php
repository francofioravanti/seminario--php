<?php
class Usuario{

    

    


    public function registrarUsuario(string $nombre,string $usuario, string $clave): array {
        $errores = [];

        if (!$this->validarNombreUsuario($usuario, $errores)) return $errores; #Si no se cumple alguna condicion del usuario, llena el array
        if (!$this->validarClave($clave, $errores)) return $errores; #Si no se cumple alguna condicion de la contraseña, llena el array

        $db = (new Conexion())->getDb(); #crea una nueva instancia de la clase Conexion e invoca al metodo getDb

        if ($this->existeUsuario($usuario)) {  #Si el usuario esta en uso, llena el array
            $errores[] = "El nombre de usuario ya está en uso.";
            return $errores;
        }
        #Si todas las condicines para registrar un usuario se cumplen... se registra un nuevo usuario

        
        $stmt = $db->prepare("INSERT INTO usuario (nombre,usuario,password) VALUES (:nombre,:usuario, :clave)");# Prepara una consulta SQL que va a insertar un nuevo registro en la tabla con los valores que pongas en :usuario y :clave.
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':usuario', $usuario); #Asocia los parámetros de la consulta (:usuario y :clave) con los valores reales ($usuario y )
        $stmt->bindParam(':clave', $clave); #$hash porq es la clave ya encriptada
        

    

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
        return (bool) $stmt->fetch(); # true si encontró algo, false si no
    }
    public function validarNombreUsuario(string $usuario, array &$errores): bool { #Se fija que el usuario ingresado cumple las condiciones
        
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
    public function validarUsuario($usuario,$clave):bool{
        $db=(new Conexion())->getDb(); 
        $query="SELECT * FROM usuario WHERE usuario = :usuario";
        $stmt=$db->prepare($query);
        $stmt->bindParam(':usuario',$usuario);
        $stmt->execute();
        $result=$stmt->fetch(PDO::FETCH_ASSOC);
        

        if ($result && $clave === $result['password']) {
            return true;
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
    

    public function actualizarCredencialesPorId(int $id, string $nombre, string $clave): bool {
        $db = (new Conexion())->getDb();
        $query = "UPDATE usuario SET nombre = :nombre, password = :password WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':password', $clave);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }


    
    public function info($id): array {
    $db = (new Conexion())->getDb();
    $stmt = $db->prepare("SELECT * FROM usuario WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data ?: []; // si no hay nada, devuelve array vacío
}

}