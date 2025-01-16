<?php
use PHPUnit\Framework\TestCase;

class vigilanteTest extends TestCase {

    public function testMetodoSolicitudIncorrecto() {
        $_SERVER['REQUEST_METHOD'] = 'POST'; 
        $_SESSION = [];

        $this->expectOutputString('');

        ob_start();
        require_once dirname(__DIR__) . '/src/vigilante.php'; 
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('ERROR', $response['titulo']);
        $this->assertEquals('Intento de operación incorrecta', $response['msj']);
        $this->assertEquals(500, $response['cod']);
    }

    public function testSesionNoIniciada() {
        $_SERVER['REQUEST_METHOD'] = 'GET'; 
        $_SESSION['inicio_sesion'] = null;

        $this->expectOutputString('');

        ob_start();
        require_once dirname(__DIR__) . '/src/vigilante.php'; 
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('ERROR', $response['titulo']);
        $this->assertEquals('No autorizado', $response['msj']);
        $this->assertEquals(500, $response['cod']);
    }

    public function testCargoJefe() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['inicio_sesion'] = [
            'cargo' => 'jefe',
            'documento' => '12345'
        ];

        $mockedConsulta = $this->createMock(mysqli_result::class);
        $mockedConsulta->method('num_rows')->willReturn(1);
        $mockedConsulta->method('fetch_all')->willReturn([[ 
            'documento' => '67890',
            'tipo_documento' => 'CC',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'ubicacion' => 'Sede 1',
            'estado' => 'Activo',
            'cargo' => 'Vigilante'
        ]]);

        $conexionMock = $this->createMock(mysqli::class);
        $conexionMock->method('query')->willReturn($mockedConsulta);

        ob_start();
        require_once dirname(__DIR__) . '/src/vigilante.php';
        $output = ob_get_clean();
        $response = json_decode($output, true);

        $this->assertEquals('EXITO', $response['titulo']);
        $this->assertEquals('¡Consulta ejecutada con éxito!', $response['msj']);
        $this->assertEquals(200, $response['cod']);
        $this->assertCount(1, $response['vigilantes']);
    }

    public function testErrorConexionBD() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['inicio_sesion'] = [
            'cargo' => 'jefe',
            'documento' => '12345'
        ];

        $conexionMock = $this->createMock(mysqli::class);
        $conexionMock->method('query')->willReturn(false);

        $this->expectOutputString('');

        ob_start();
        require_once dirname(__DIR__) . '/src/vigilante.php'; 
        $output = ob_get_clean();
        $response = json_decode($output, true);

        $this->assertEquals('ERROR', $response['titulo']);
        $this->assertEquals('Datos incorrectos para la operación indicada, Error de conexión', $response['msj']);
        $this->assertEquals(500, $response['cod']);
    }

    public function testNoResultadosConsulta() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['inicio_sesion'] = [
            'cargo' => 'jefe',
            'documento' => '12345'
        ];

        $mockedConsulta = $this->createMock(mysqli_result::class);
        $mockedConsulta->method('num_rows')->willReturn(0);

        $conexionMock = $this->createMock(mysqli::class);
        $conexionMock->method('query')->willReturn($mockedConsulta);

        $this->expectOutputString('');

        ob_start();
        require_once dirname(__DIR__) . '/src/vigilante.php'; 
        $output = ob_get_clean();
        $response = json_decode($output, true);

        $this->assertEquals('ERROR', $response['titulo']);
        $this->assertEquals('No fue posible realizar el conteo', $response['msj']);
        $this->assertEquals(500, $response['cod']);
    }
}
