<?php
class Partida {
    // Constructor
    public function __construct() {
        $this->partidaService = new PartidaService(); // Instancia de PartidaService
    }

    //  Crear partida
    public function crear($data) {
        // Verifica si el usuario está logueado
        if (!Usuario::estaLogueado()) {
            return $this->respuestaError(401, "El usuario no está logueado."); }
            
        // Obtiene el ID del usuario desde la sesión
        $usuario_id = $_SESSION['usuario_id'];

        // Verifica que el mazo pertenece al usuario logueado
        if (!$this->partidaService->verificarMazoPertenece($data['mazo_id'], $usuario_id)) {
            return $this->respuestaError(403, "El mazo no pertenece al usuario logueado.");
        }

        // Crea la partida en la base de datos y obtener el ID de la partida
        $resultado = $this->partidaService->crearPartida($usuario_id, $data['mazo_id']);

        // Actualiza el estado de las cartas a "en_mano"
        $cartas = $this->partidaService->actualizarEstadoCartas($data['mazo_id'], 'en_mano');

        // Establece el código de estado HTTP 200 (OK)
        http_response_code(200);

        // Devolver el resultado con el ID de la partida y las cartas
        return $this->respuestaExito($resultado['id_partida'], $cartas);
    }
 // Respuesta exitosa
 private function respuestaExito($id_partida, $cartas) {
    return [
        'id_partida' => $id_partida,
        'cartas' => $cartas
    ];
}

// Respuesta de error
private function respuestaError($codigo, $mensaje) {
    http_response_code($codigo); // Establecer el código de error
    return [
        'status' => 'error',
        'message' => $mensaje
    ];
}

?>
