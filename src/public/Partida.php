<?php
require_once 'Usuario.php';

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


    public function actualizarTodasLasCartas($db,$mazoid,$estado){
        $query="UPDATE mazo_carta  
                SET estado = :estado  
                WHERE mazo_id = :mazo_id ";
        $stmt=$db->prepare($query);
        $stmt->bindParam(':estado',$estado);
        $stmt->bindParam(':mazo_id',$mazoid);

        if(!$stmt->execute()){
            return false;
        }
        return true;

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
    
    //PARA TESTEAR LOS DOS METODOS DE ABAJO HAY Q CREAR/INSERTAR UN MAZO 
    private function lePerteneceElMazo($db,$id,$mazoid):bool{
        $query="SELECT * FROM mazo WHERE id = :mazo_id AND usuario_id = :usuario_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario_id',$id);
        $stmt->bindParam(':mazo_id',$mazoid);
        $stmt->execute();
        $result=$stmt->fetch(PDO::FETCH_ASSOC);
        var_dump($result);
        var_dump("NASHE");
        if ($result) {
            return true;
        }
        

        return false; 
    }



    public function puedeJugar($token,$mazoid):array|bool{
        //selecciono todo del usuario que tenga ese token y este vigente.
        $result=Usuario::obtenerUsuarioPorToken($token); // esta bien esto? se puede usar en endpoints tmb?
        if (!is_array($result)) {
            return false;
        }
        
        $db = (new Conexion())->getDb();
        $id=$result['id'];
        $usuario=$result['usuario'];



        if ($this->lePerteneceElMazo($db,$id,$mazoid)){
            $query="INSERT INTO partida (usuario_id, el_usuario, fecha, mazo_id, estado)
                    VALUES (:usuario_id, :el_usuario, NOW(), :mazo_id, :estado)";
            $stmt=$db->prepare($query);
            $stmt->bindParam(':usuario_id',$id);
            $stmt->bindParam(':el_usuario',$usuario);
            $stmt->bindParam(':mazo_id',$mazoid);
            $estado = 'en_curso';
            $stmt->bindParam(':estado',$estado);

            if($stmt->execute()) {
                $partida_id = $db->lastInsertId();
                $estado = 'en_mano';
                $this->actualizarTodasLasCartas($db,$mazoid,$estado);
                return ['partida_id' => $partida_id];
            }

        }
        return false;
    }



}






?>
