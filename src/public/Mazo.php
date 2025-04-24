<?php
class Mazo{


    public function crearMazo(int $usuario_id,string $nombreMazo,array $cartas){
        $db = (new Conexion())->getDb();

       


        //verifico unicidad.
        if (count($cartas) !== count(array_unique($cartas))) {
            return 'Las cartas deben tener IDs distintos.';
        }
        if (count($cartas) > 5) {
            return 'Solo se permiten 5 cartas como máximo.';
        }
       
        //verifico que todas existan.
        foreach ($cartas as $idCarta) {
            $stmt = $db->prepare("SELECT id FROM carta WHERE id = ?");
            $stmt->execute([$idCarta]);
            if (!$stmt->fetch()) {
                return "La carta con ID $idCarta no existe.";
            }
        }
       
        //verificar que el usuario tenga menos de 3 mazo creados.
        $query="SELECT count(*) AS total FROM mazo WHERE usuario_id = :usuario_id";
        $stmt=$db->prepare($query);
        $stmt->bindParam(':usuario_id',$usuario_id);
        $stmt->execute();
        $result=$stmt->fetch(PDO::FETCH_ASSOC);
        $cantidad = $result['total'];
        if ($cantidad>=3){
            return "Ya tenés 3 mazos creados.";
        }

      
        //realizar el insert del Mazo en la tabla mazo.
        $query="INSERT INTO mazo (usuario_id,nombre)
                VALUES (:usuario_id,:nombre)";
        $stmt=$db->prepare($query);
        $stmt->bindParam(':usuario_id',$usuario_id);
        $stmt->bindParam(':nombre',$nombreMazo);     
        $stmt->execute();
        $mazoid = $db->lastInsertId();

        if ($mazoid){
           
            //realizar el insert de las cartas en la tabla mazo_carta.
            $estado = 'en_mazo';
            $insertQuery = "INSERT INTO mazo_carta (mazo_id, carta_id, estado) VALUES ";
            $values = [];
            $params = [];

            foreach ($cartas as $i => $cartaId) {
                $values[] = "(:mazo_id, :carta_id_$i, :estado)";
                $params[":carta_id_$i"] = $cartaId;
            }

            //agrego todos los valores separados por comas
            $insertQuery .= implode(', ', $values);

            $stmt = $db->prepare($insertQuery);
            $stmt->bindParam(':mazo_id', $mazoid);
            $stmt->bindParam(':estado', $estado);

            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }

            $exito = $stmt->execute();
            if($exito){
                return  ['mazo_id' => $mazoid, 'nombre' => $nombreMazo];;
            }
            return "No se pudo crear el mazo";
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
    // Eliminar el mazo
    $stmt = $db->prepare("DELETE FROM mazo WHERE id = :mazo_id");
    $stmt->bindParam(':mazo_id',$mazoId);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {//Devuelve la cantidad de filas afectadas por la sentencia SQL 
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



    public static function obtenerMazoConCartarDeUsuario($usuarioId):array{
        $db = (new Conexion())->getDb();

        //BUSCO TODOS LOS MAZOS DEL SUUARIO LOGEUADO
        $stmt = $db->prepare("SELECT id, nombre FROM mazo WHERE usuario_id = :usuarioId");
        $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $datosMazos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mazos = [];

        foreach ($datosMazos as $mazo) {
            // BUSCO TODOS LOS IDS DE LAS CARTAS DEL MAZO
            $stmtCartas = $db->prepare("SELECT carta_id FROM mazo_carta WHERE mazo_id = :mazoId");
            $stmtCartas->bindParam(':mazoId', $mazo['id'], PDO::PARAM_INT);
            $stmtCartas->execute();
            $cartasIds = $stmtCartas->fetchAll(PDO::FETCH_COLUMN);

           //BUSCO LOS NOMBRES DE LAS CARTAS CON ESOS IDS 
            $nombresCartas = [];
            foreach ($cartasIds as $cartaId) {
                $stmtNombre = $db->prepare("SELECT nombre FROM carta WHERE id = :id");
                $stmtNombre->bindParam(':id', $cartaId, PDO::PARAM_INT);
                $stmtNombre->execute();
                $nombre = $stmtNombre->fetchColumn();
                if ($nombre) {
                    $nombresCartas[] = $nombre;
                }
            }   
            $mazos[] = [
                'nombre_mazo' => $mazo['nombre'],
                'cartas' => $nombresCartas
            ];
        
        }
        return $mazos;
    }

}


?>
