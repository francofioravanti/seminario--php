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



    public function puedeJugar($token,$mazoid):array|bool{
        //selecciono todo del usuario que tenga ese token y este vigente.
        $result=Usuario::obtenerUsuarioPorToken($token); 
        
        
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
                //Se actualizan todas las cartas del servidor a "en_mano"
                $this->actualizarTodasLasCartas($db,1,'en_mano');
                return ['partida_id' => $partida_id];
            }

        }
        return false;
    }
    
    public function verificarPertenenciaMazo($usuarioId, $mazoId): bool {// esto lo uso para delete mazo (solo creo esta funcion para poder reutilizar le perteneceElMazo sin hacer esa funcion publica. aunque podria simplemente hace rpublica la otra funcion, capaz es mejor)
        $db = (new Conexion())->getDb();
        return $this->lePerteneceElMazo($db, $usuarioId, $mazoId);
    }

///////////////////////////////
    private function obtenerMazoId($db, $partidaId, $esServidor = false) {
     if ($esServidor) {
        // Devuelve el mazo del usuario_id = 1 (servidor)
        $stmt = $db->prepare("SELECT id FROM mazo WHERE usuario_id = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Devuelve el mazo del jugador a partir de la partida
    $stmt = $db->prepare("SELECT mazo_id FROM partida WHERE id = :id");
    $stmt->bindParam(':id', $partidaId);
    $stmt->execute();
    return $stmt->fetchColumn();
    }


    public function cartaValidaParaPartida($usuarioId, $partidaId,  $cartaId): bool { //validamos la carta
        $db = (new Conexion())->getDb();
        $stmt = $db->prepare ("SELECT  mazo_id FROM partida WHERE id = :partida_id AND usuario_id = :usuario_id AND  estado = 'en_curso'"); //  Obtenemos el mazo de esa partida
        $stmt -> bindParam(':partida_id',$partidaId);
        $stmt -> bindParam(':usuario_id',$usuarioId);
        $stmt->execute();
        $mazo = $stmt->fetch(PDO::FETCH_ASSOC); //devuelve lo que se consulto
        if  (!$mazo) {
            return false;
        }
        $mazoId = $mazo['mazo_id'];

        // 2. Verificar que la carta esté en ese mazo y en estado 'en_mano'
        if (!$this->lePerteneceElMazo($db, $usuarioId, $mazoId)) return false;
        $stmt = $db->prepare("SELECT * FROM mazo_carta WHERE mazo_id = :mazo_id AND carta_id = :carta_id AND estado = 'en_mano'");
        $stmt->bindParam(':mazo_id', $mazoId);
        $stmt->bindParam(':carta_id', $cartaId);
        $stmt->execute();
    
        $carta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $carta ? true : false;
    }

    

     // se calcula si es la 5ta mano
    private function cerrarPartidaSiCorresponde($db, $partidaId){
        $stmt = $db->prepare("SELECT COUNT(*) as jugadas FROM jugada WHERE partida_id = :id"); // select count cuenta cuántas jugadas hubo en la partida de ese id
        $stmt->execute([':id' => $partidaId]);
        $ronda = $stmt->fetch(PDO::FETCH_ASSOC)['jugadas'];

        if ((int)$ronda < 5) return null; // mientras no sea 5 no se procesa este codigo, no se cierra la partida

        $stmt = $db->prepare("SELECT el_usuario, COUNT(*) as total FROM jugada WHERE partida_id = :id GROUP BY el_usuario");// 
        $stmt->execute([':id' => $partidaId]);
        $conteo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gano =  $perdio = 0;
        foreach ($conteo as $fila) { // cuenta total de ganadas y perdidas
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
        $stmt = $db->prepare("SELECT * FROM gana_a WHERE atributo_id = :a1 AND atributo_id2 = :a2");//  consulta a la tabla gana_a, que es donde tenés definidas las relaciones de ventaja de atributos
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
        // actualizamos el estado de las cartas a descartado
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
    
        //QUE HACE ESTA LINEA?
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
//GET
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
