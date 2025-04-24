<?php

class Estadistica {


    public function obtenerEstadisticas(): array {
        $db = (new Conexion())->getDb();
        //HAGO LA CONSULTA SEPARANDO LOS TRES DIFERENTES RESUTLADOS EN COLUMNAS :)
        //
        $query = "SELECT u.nombre,
                  SUM(CASE WHEN p.el_usuario = 'gano' THEN 1 ELSE 0 END) AS gano,
                  SUM(CASE WHEN p.el_usuario = 'perdio' THEN 1 ELSE 0 END) AS perdio,
                  SUM(CASE WHEN p.el_usuario = 'empato' THEN 1 ELSE 0 END) AS empato
                  FROM partida p
                  JOIN usuario u ON p.usuario_id = u.id
                  GROUP BY u.nombre";
        $stmt = $db->query($query);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
       
       
        $estadisticas = [];
        
        foreach ($result as $fila) {
            $usuario = $fila['nombre'];
            $estadisticas[$usuario] = [
                'ganó' => (int) $fila['gano'],
                'perdió' => (int) $fila['perdio'],
                'empató' => (int) $fila['empato']
            ];
        }
        
        return $estadisticas;
    }
    


    


   



}
?>


