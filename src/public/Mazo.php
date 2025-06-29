<?php
class Mazo{


    public function crearMazo(int $usuario_id, string $nombreMazo, array $cartas) {
    $db = (new Conexion())->getDb();

    if (count($cartas) !== count(array_unique($cartas))) {
        return 'Las cartas deben tener IDs distintos.';
    }
    if (count($cartas) > 5) {
        return 'Solo se permiten 5 cartas como máximo.';
    }

    foreach ($cartas as $idCarta) {
        $stmt = $db->prepare("SELECT id FROM carta WHERE id = ?");
        $stmt->execute([$idCarta]);
        if (!$stmt->fetch()) {
            return "La carta con ID $idCarta no existe.";
        }
    }

    $query = "SELECT count(*) AS total FROM mazo WHERE usuario_id = :usuario_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cantidad = $result['total'];
    if ($cantidad >= 3) {
        return "Ya tenés 3 mazos creados.";
    }

    $query = "INSERT INTO mazo (usuario_id, nombre) VALUES (:usuario_id, :nombre)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':nombre', $nombreMazo);
    $stmt->execute();
    $mazoid = $db->lastInsertId();

    if ($mazoid) {
    if (count($cartas) > 0) {
        $estado = 'en_mazo';
        $insertQuery = "INSERT INTO mazo_carta (mazo_id, carta_id, estado) VALUES ";
        $values = [];
        $params = [];

        foreach ($cartas as $i => $cartaId) {
            $values[] = "(:mazo_id_$i, :carta_id_$i, :estado_$i)";
            $params[":mazo_id_$i"] = $mazoid;
            $params[":carta_id_$i"] = $cartaId;
            $params[":estado_$i"] = $estado;
        }

        $insertQuery .= implode(', ', $values);

        $stmt = $db->prepare($insertQuery);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $exito = $stmt->execute();
        if (!$exito) {
            return "No se pudo crear el mazo";
        }
    }
    
    
    return ['mazo_id' => $mazoid, 'nombre' => $nombreMazo];
}

}

  public function eliminarMazo($mazoId): array {
    $db = (new Conexion())->getDb();
    $query = "SELECT COUNT(*) as total FROM partida WHERE mazo_id = :mazo_id";
     $stmt = $db->prepare($query);
    $stmt-> bindParam(':mazo_id', $mazoId);
    $stmt->execute();
    $result=$stmt->fetch(PDO:: FETCH_ASSOC);
    if($result ['total']>0){
        throw new Exception("El mazo no puede ser eliminado debido a que participó en una partida.");
    }
   
    $stmt = $db->prepare("DELETE FROM mazo WHERE id = :mazo_id");
    $stmt->bindParam(':mazo_id',$mazoId);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        return ['mensaje' => 'Mazo eliminado correctamente'];
    } else {
        throw new Exception("No hay mazos para eliminar con ese ID. ");
    }
    }
    public function actualizarNombre($mazo_id,$nuevoNombre): bool {
            $db=(new Conexion())->getDb();
            $stmt=$db->prepare("UPDATE mazo SET nombre =:nombre WHERE id= :mazo_id");
            $stmt->bindParam(':nombre',$nuevoNombre);
            $stmt->bindParam(':mazo_id',$mazo_id);
            return $stmt->execute();    
    }

    public function obtenerCartasDeMazo($mazoId) {
        $db = (new Conexion())->getDb();
        $stmt = $db->prepare("SELECT c.id, c.nombre, c.ataque, a.nombre as atributo FROM mazo_carta mc JOIN carta c ON mc.carta_id = c.id JOIN atributo a ON c.atributo_id = a.id WHERE mc.mazo_id = :mazoId");
        $stmt->bindParam(':mazoId', $mazoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   public static function obtenerMazoConCartarDeUsuario($usuarioId): array {
    $db = (new Conexion())->getDb();

    if (!$usuarioId) {
        return [];
    }

    $stmt = $db->prepare("SELECT id, nombre FROM mazo WHERE usuario_id = :usuarioId");
    $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $datosMazos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mazos = [];

    foreach ($datosMazos as $mazo) {
        $stmtCartas = $db->prepare("SELECT c.id, c.nombre, c.ataque, a.nombre as atributo FROM mazo_carta mc JOIN carta c ON mc.carta_id = c.id JOIN atributo a ON c.atributo_id = a.id WHERE mc.mazo_id = :mazoId");
        $stmtCartas->bindParam(':mazoId', $mazo['id'], PDO::PARAM_INT);
        $stmtCartas->execute();
        $cartas = $stmtCartas->fetchAll(PDO::FETCH_ASSOC);
        $mazos[] = [
            'id' => (int)$mazo['id'],
            'nombre' => $mazo['nombre'], 
            'cartas' => $cartas
        ];
    }

    return $mazos;
}

#//foreach ($datosMazos as $mazo) {
 #   $stmtCartas = $db->prepare("SELECT c.id, c.nombre, c.ataque, a.nombre as atributo FROM mazo_carta mc JOIN carta c ON mc.carta_id = c.id JOIN atributo a ON c.atributo_id = a.id WHERE mc.mazo_id = :mazoId");
  #  $stmtCartas->bindParam(':mazoId', $mazo['id'], PDO::PARAM_INT);
   # $stmtCartas->execute();
   # $cartas = $stmtCartas->fetchAll(PDO::FETCH_ASSOC);
    #$mazos[] = [
   #     'id' => (int)$mazo['id'],
    #    'nombre' => $mazo['nombre'],
     #   'cartas' => $cartas
   # ];
#}
#return ['id' => (int)$mazoid, 'nombre' => $nombreMazo];
#
    
public static function buscarCartas(?string $nombre, ?string $atributo): array {
    $db = (new Conexion())->getDb();
    $query = "SELECT carta.nombre, carta.ataque, carta.ataque_nombre, atributo.nombre AS atributo
              FROM carta
              JOIN atributo ON carta.atributo_id = atributo.id
              WHERE 1 = 1";

   
    $params = [];

    if ($atributo !== null && $atributo !== '') {
        $query .= " AND carta.atributo_id = :atributo_id";
        $params[':atributo_id'] = $atributo;
    }

    if ($nombre !== null && $nombre !== '') {
        $query .= " AND carta.nombre LIKE :nombre";
        $params[':nombre'] = "%$nombre%";
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



}


?>

