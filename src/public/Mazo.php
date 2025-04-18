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

    
}


?>