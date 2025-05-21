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
       
        
        if ($result) {
            return true;
        }
        return false; 
    }



    public function puedeJugar(int $usuarioId, int $mazoid): array|bool {
    $db = (new Conexion())->getDb();

   
    $stmt = $db->prepare("SELECT usuario FROM usuario WHERE id = :id");
    $stmt->bindParam(':id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $usuarioNombre = $stmt->fetchColumn();

    if (!$usuarioNombre) {
        return ['error' => 'Usuario no válido'];
    }

   
    $stmt = $db->prepare("SELECT COUNT(*) FROM partida WHERE estado = 'en_curso'");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        return ['error' => 'Ya hay una partida en curso. Solo se permite una a la vez.'];
    }

    
    if (!$this->lePerteneceElMazo($db, $usuarioId, $mazoid)) {
        return ['error' => 'El mazo no te pertenece'];
    }

  
    $query = "INSERT INTO partida (usuario_id, el_usuario, fecha, mazo_id, estado)
              VALUES (:usuario_id, :el_usuario, NOW(), :mazo_id, 'en_curso')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario_id', $usuarioId);
    $stmt->bindParam(':el_usuario', $usuarioNombre);
    $stmt->bindParam(':mazo_id', $mazoid);

    if ($stmt->execute()) {
        $partida_id = $db->lastInsertId();

        
        $this->actualizarTodasLasCartas($db, $mazoid, 'en_mano');
        $this->actualizarTodasLasCartas($db, 1, 'en_mano');

        return ['partida_id' => $partida_id];
    }

    return ['error' => 'No se pudo crear la partida por un error inesperado'];
}
    
    public function verificarPertenenciaMazo($usuarioId, $mazoId): bool {
        $db = (new Conexion())->getDb();
        return $this->lePerteneceElMazo($db, $usuarioId, $mazoId);
    }


    private function obtenerMazoId($db, $partidaId, $esServidor = false) {
     if ($esServidor) {
        
        $stmt = $db->prepare("SELECT id FROM mazo WHERE usuario_id = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    
    $stmt = $db->prepare("SELECT mazo_id FROM partida WHERE id = :id");
    $stmt->bindParam(':id', $partidaId);
    $stmt->execute();
    return $stmt->fetchColumn();
    }


    public function cartaValidaParaPartida($usuarioId, $partidaId,  $cartaId): bool { 
        $db = (new Conexion())->getDb();
        $stmt = $db->prepare ("SELECT  mazo_id FROM partida WHERE id = :partida_id AND usuario_id = :usuario_id AND  estado = 'en_curso'"); 
        $stmt -> bindParam(':partida_id',$partidaId);
        $stmt -> bindParam(':usuario_id',$usuarioId);
        $stmt->execute();
        $mazo = $stmt->fetch(PDO::FETCH_ASSOC); 
        if  (!$mazo) {
            return false;
        }
        $mazoId = $mazo['mazo_id'];

     
        if (!$this->lePerteneceElMazo($db, $usuarioId, $mazoId)) return false;
        $stmt = $db->prepare("SELECT * FROM mazo_carta WHERE mazo_id = :mazo_id AND carta_id = :carta_id AND estado = 'en_mano'");
        $stmt->bindParam(':mazo_id', $mazoId);
        $stmt->bindParam(':carta_id', $cartaId);
        $stmt->execute();
    
        $carta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $carta ? true : false;
    }

    

     
    private function cerrarPartidaSiCorresponde($db, $partidaId){
        $stmt = $db->prepare("SELECT COUNT(*) as jugadas FROM jugada WHERE partida_id = :id"); 
        $stmt->execute([':id' => $partidaId]);
        $ronda = $stmt->fetch(PDO::FETCH_ASSOC)['jugadas'];

        if ((int)$ronda < 5) return null; 

        $stmt = $db->prepare("SELECT el_usuario, COUNT(*) as total FROM jugada WHERE partida_id = :id GROUP BY el_usuario");// 
        $stmt->execute([':id' => $partidaId]);
        $conteo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gano =  $perdio = 0;
        foreach ($conteo as $fila) { 
            if ($fila['el_usuario'] === 'gano') 
                $gano = $fila['total'];
            if ($fila['el_usuario'] === 'perdio') 
                $perdio = $fila['total'];
        }

        $stmt = $db->prepare("UPDATE partida SET estado = 'finalizada', el_usuario = :res WHERE id = :id");
        if ($gano > $perdio){
            $res= 'ganó';
            $stmt->execute([':res' => $res, ':id' => $partidaId]);
            return 'usuario';
        } elseif ($gano < $perdio){ 
            $res= 'perdió';
            $stmt->execute([':res' => $res, ':id' => $partidaId]);
            return 'servidor';
        } else {
            $res ='empató';
            $stmt->execute([':res' => $res, ':id' => $partidaId]);
            return 'empate';
        } 
    } 


    private function getDatosCartas($db,int $cartaId):array{
        $stmt =  $db ->prepare("SELECT ataque,atributo_id FROM carta WHERE id= :id");
        $stmt->execute([':id'=>$cartaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);   
    }

    private function calcularBonus($db,int $atributoJugador,int $atributoServidor):array{
        $bonus = 1.3;
        $jugadorBonus = $servidorBonus = 1;// inicializa bonus
        $stmt = $db->prepare("SELECT * FROM gana_a WHERE atributo_id = :a1 AND atributo_id2 = :a2");
        $stmt->execute([':a1' => $atributoJugador, ':a2' => $atributoServidor]);
        if ($stmt->fetch()) $jugadorBonus = $bonus;
        $stmt->execute([':a1' => $atributoServidor, ':a2' => $atributoJugador]);
        if ($stmt->fetch()) $servidorBonus = $bonus;
        return [$jugadorBonus, $servidorBonus];
    }

    private function guardarJugada($db,int $partidaId,int $cartaJugador,int $cartaServidor,string $resultado){
        $stmt = $db->prepare("INSERT INTO jugada (partida_id, carta_id_a, carta_id_b, el_usuario)  VALUES (:partida_id, :carta_id_a, :carta_id_b, :resultado)");
        $stmt->bindParam(':partida_id', $partidaId);
        $stmt->bindParam(':carta_id_a', $cartaJugador);
        $stmt->bindParam(':carta_id_b', $cartaServidor);
        $stmt->bindParam(':resultado', $resultado); 
        $stmt->execute();
     
        $this->actualizarEstadoCarta($cartaJugador,$this->obtenerMazoId($db, $partidaId),'descartado');
        $this->actualizarEstadoCarta($cartaServidor,    1   ,'descartado');
    }
    
    public function procesarJugada($usuarioId, $cartaId, $partidaId) {
        if (!$this->cartaValidaParaPartida($usuarioId, $partidaId, $cartaId)) {
            return ['error' => 'Carta inválida para esta partida'];
        }
    
        $db = (new Conexion())->getDb();
    
        try {
            $cartaServidorId = $this->jugadaServidor();
        } catch (Exception $e) {
            return ['error' => 'No hay cartas disponibles para el servidor'];
        }

        
        
        $jugador = $this->getDatosCartas($db, $cartaId);
        $servidor = $this->getDatosCartas($db, $cartaServidorId);
    
        [$bonusJugador, $bonusServidor] = $this->calcularBonus($db, $jugador['atributo_id'], $servidor['atributo_id']);
    
        $ataqueJugador = $jugador['ataque'] * $bonusJugador;
        $ataqueServidor = $servidor['ataque'] * $bonusServidor;
    
        
        if ($ataqueJugador > $ataqueServidor) {
            $resultado = 'ganó';
        } elseif ($ataqueJugador < $ataqueServidor) {
            $resultado = 'perdió';
        } else {
            $resultado = 'empató';
        }
        
    
        
        $this->guardarJugada($db, $partidaId, $cartaId, $cartaServidorId, $resultado);
    
        
        $ganadorFinal = $this->cerrarPartidaSiCorresponde($db, $partidaId);
    
        return [
            'carta_servidor' => $cartaServidorId,
            'ataque_jugador' => $ataqueJugador,
            'ataque_servidor' => $ataqueServidor,
            'resultado_final' => $ganadorFinal
        ];
    }

    public function obtenerCartasEnMano($usuarioId, $partidaId): array  {
        $db= (new Conexion())->getDb();
        $mazoId= $this->obtenerMazoId($db,$partidaId);
        $query = "SELECT carta.nombre, carta.ataque, carta.ataque_nombre, atributo.nombre AS atributo
                  FROM mazo_carta, carta, atributo
                  WHERE mazo_carta.carta_id = carta.id 
                  AND carta.atributo_id = atributo.id 
                  AND mazo_carta.mazo_id = :mazo_id 
                  AND mazo_carta.estado = 'en_mano'";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':mazo_id', $mazoId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}






?>
