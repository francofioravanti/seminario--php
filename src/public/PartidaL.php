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
        var_dump("NASHE");// JAJAJAJAJJAJA
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
    private function obtenerMazoId($db, $partidaId): int { 
        $stmt = $db->prepare("SELECT mazo_id FROM partida WHERE id = :id");
        $stmt->bindParam(':id', $partidaId);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resultado) 
        return (int)$resultado['mazo_id'] ;
    else
        return 0; 
    }

    public function cartaValidaParaPartida($usuarioId, $partidaId,  $cartaId): bool { //validamos la carta
        $db = (new Conexion())->getDb();
        $stmt = $db->prepare ("SELECT  mazo_id FROM partida WHERE id = :partida_id AND usuario_id = :usuario_id AND  estado = 'en_curso'"); //  Obtenemos el mazo de esa partida
        $stmt -> bindParam(':partida_id',$partidaId);
        $stmt -> bindParam(':usuario_id',$usuarioId);
        $stmt->execute();
        $mazo = $stmt->fetch(PDO::FETCH_ASSOC); //devuelve lo que se consulto
        if  (!$mazo) 
            return false;
        else 
            return true;
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
            $res= 'gano' ;}
        elseif ($gano < $perdio){ 
            $res= 'perdio'; }
        else 
            $res ='empato';

        $stmt->execute([':res' => $res, ':id' => $partidaId]);      
    return $res;
    } 


    private function getDatosCartas($db,int $cartaId):array{
        $stmt =  $db ->prepare("SELECT ataque,atributo_id FORM carta WHERE id= :id");
        $stmt->execute([':id' => $cartaId]);
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
        $stmt->execute([
            ' :partida_id' => $partidaId,
            ' :carta_id_a' => $cartaJugador,
            ' :carta_id_b' => $cartaServidor,
            ' :resultado' => $resultado
        ]);
        // actualizamos el estado de las cartas a descartado
            $this->actualizarEstadoCarta($cartaJugador,$this->obtenerMazoId($db, $partidaId),'descartado');
            $this->actualizarEstadoCarta($cartaServidor,1,'descartado');
    }
    
    public function procesarJugada($usuarioId, $cartaId, $partidaId){

    if (!$this->cartaValidaParaPartida($usuarioId, $partidaId, $cartaId)) {
        return false;
    }
    $db = (new Conexion())->getDb();
    $cartaServidorId = $this->jugadaServidor();
            
// de esta manera obntenemos los atributos de las cartas que vamos a utilizar para comparar el ganador  
    $jugador = $this->getDatosCartas($db, $cartaId);
    $servidor = $this->getDatosCartas($db, $cartaServidorId);

// se calcula el bonus de atributo (30%)
    [$bonusJugador, $bonusServidor] = $this->calcularBonus($db, $jugador['atributo_id'], $servidor['atributo_id']);

    $ataqueJugador = $jugador['ataque'] * $bonusJugador; // se suma el posentaje del bonus, si no recibio bonus va a ser *1 , no cambia
    $ataqueServidor = $servidor['ataque'] * $bonusServidor;

//se decide el ganador con el bonus ya incluido
    if ($ataqueJugador > $ataqueServidor ){
    $resultado='ganó';}
    elseif ($ataqueJugador < $ataqueServidor ){
    $resultado='perdió';}
    else 
    $resultado='empató';

// se guarda la partida en la bd
    $this->guardarJugada($db, $partidaId, $cartaId, $cartaServidorId, $resultado);

// Verificar si se debe cerrar la partida
    $ganadorFinal = $this->cerrarPartidaSiCorresponde($db, $partidaId);
    return [
        'carta_servidor' => $cartaServidorId,
        'ataque_jugador' => $ataqueJugador,
        'ataque_servidor' => $ataqueServidor,
        'ganador_final' => $ganadorFinal
    ];
    }
}
?>
