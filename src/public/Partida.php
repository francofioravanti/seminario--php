<?php
class Partida{

    
    
    private function pertenece($carta_id , $mazo_id):bool{
        $db=(new Conexion())->getDb(); //conecto con la base de datos
        $query="SELECT * FROM mazo_carta WHERE mazo_id = :mazo_id AND carta_id = :carta_id AND estado = 'en_mazo'"; //guardo la consulta 
        $stmt=$db->prepare($query); //preparo consulta
        $stmt->bindParam(':mazo_id',$mazo_id); //le doy el valor a :mazo_id
        $stmt->bindParam(':carta_id',$carta_id); //le doy el valor a :carta_id
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if ( $result ){
            return true;
        }
        return false;
    }
    

    private function actualizarEstadoCarta($carta_id,$mazo_id,$estado):bool{
        $db=(new Conexion())->getDb();
        $query="UPDATE mazo_carta SET estado = :estado WHERE carta_id = :carta_id AND mazo_id = :mazo_id ";
        $stmt=$db->prepare($query);
        $stmt->bindParam(':carta_id',$carta_id);
        $stmt->bindParam(':mazo_id',$mazo_id);
        $stmt->bindParam(':estado',$estado);
        $stmt->execute();
        if ($stmt->rowCount()>0) {
            return true;
        }
        return false;
    }

    public function getCartas($mazo_id):array{
        $db=(new Conexion())->getDb();
        $query="SELECT carta_id,estado FROM mazo_carta WHERE mazo_id = :mazo_id";
        $stmt=$db->prepare($query);
        $stmt->bindParam(':mazo_id',$mazo_id);
        $stmt->execute();
        $cartas = $stmt->fetchAll(PDO::FETCH_COLUMN); 
        return $cartas;
    }


    public function jugadaServidor(): int {
        $mazoServidor = 1;
    
        // 1. Obtener una carta del mazo del servidor que NO esté descartada
        $db = (new Conexion())->getDb();
        $query = "
            SELECT carta_id 
            FROM mazo_carta 
            JOIN mazo ON mazo_carta.mazo_id = mazo.id 
            WHERE mazo.usuario_id = 1 AND mazo_carta.estado != 'descartado'
            LIMIT 1
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $carta = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$carta) {
            throw new Exception("No hay cartas disponibles para el servidor.");
        }
    
        // 2. Actualizar el estado de la carta a 'descartado'
        $this->actualizarEstadoCarta($carta['carta_id'], $mazoServidor, 'descartado');
    
        // 3. Devolver el ID de la carta jugada
        return $carta['carta_id'];
    }
    


}






?>
HOLAHOLA
