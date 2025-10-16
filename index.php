<?php

// CRUD de productos en un inventario

// Encabezados para permitir CORS y definir el tipo de contenido como JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Conectar a la base de datos usando los datos del archivo .env
$host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_DATABASE'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$pdo = null;

try {
    $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    http_response_code(503); 
    echo json_encode(["mensaje" => "No se puede conectar a la base de datos."]);
    exit();
}

// Identificar el método de la petición 
$method = $_SERVER['REQUEST_METHOD'];

// Obtener el ID del producto de la URL (si existe)
// Esto divide la URL en partes. Ej: /api.php/1 -> ['', 'api.php', '1']
$uri = explode('/', $_SERVER['REQUEST_URI']);
$productId = isset($uri[2]) && is_numeric($uri[2]) ? (int)$uri[2] : null;

// Ejecutamos las queries correspondientes al método HTTP (POST, PATCH, DELETE)
switch ($method) {
    case 'POST':
        // Crear un nuevo producto 

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->nombre) && !empty($data->descripcion)) {
            $query = "INSERT INTO productos (nombre, descripcion) VALUES (:nombre, :descripcion)";
            $stmt = $pdo->prepare($query);

            // Limpiar datos
            $nombre = htmlspecialchars(strip_tags($data->nombre));
            $descripcion = htmlspecialchars(strip_tags($data->descripcion));

            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);

            if ($stmt->execute()) {
                http_response_code(201); 
                echo json_encode(["mensaje" => "Producto creado exitosamente"]);
            } else {
                http_response_code(503); 
                echo json_encode(["mensaje" => "No se pudo crear el producto"]);
            }
        } else {
            // Bad Request
            http_response_code(400); 
            echo json_encode(["mensaje" => "Datos incompletos. 'nombre' y 'descripcion' son requeridos."]);
        }
        break;

    case 'DELETE':
        // Eliminar producto
        if (!$productId) {
            // Bad request 
            http_response_code(400);
            echo json_encode(["mensaje" => "ID de producto no proporcionado"]);
            break;
        }

        $query = "DELETE FROM productos WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $productId);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                http_response_code(200); 
                echo json_encode(["mensaje" => "Producto eliminado."]);
            } else {
                // Producto no encontrado 
                http_response_code(404); 
                echo json_encode(["mensaje" => "Producto no encontrado."]);
            }
        } else {
            // Error del servidor 
            http_response_code(503);
            echo json_encode(["mensaje" => "No se pudo eliminar el producto."]);
        }
        break;

    // case 'PATCH':
    //     // **Editar un producto (actualización parcial)**
    //     $data = json_decode(file_get_contents("php://input"));

    //     if (!$productId || empty((array)$data)) {
    //         http_response_code(400);
    //         echo json_encode(["mensaje" => "ID o datos para actualizar no proporcionados."]);
    //         break;
    //     }

    //     $fields = [];
    //     if (!empty($data->nombre)) $fields['nombre'] = htmlspecialchars(strip_tags($data->nombre));
    //     if (!empty($data->descripcion)) $fields['descripcion'] = htmlspecialchars(strip_tags($data->descripcion));

    //     if (empty($fields)) {
    //         http_response_code(400);
    //         echo json_encode(["mensaje" => "Ningún campo válido para actualizar."]);
    //         break;
    //     }

    //     $set_clause = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
    //     $query = "UPDATE productos SET $set_clause WHERE id = :id";
        
    //     $stmt = $pdo->prepare($query);
    //     $stmt->bindParam(':id', $productId);
    //     foreach ($fields as $key => &$value) { // Pasamos por referencia para bindParam
    //         $stmt->bindParam(":$key", $value);
    //     }

    //     if ($stmt->execute()) {
    //         if ($stmt->rowCount() > 0) {
    //             http_response_code(200);
    //             echo json_encode(["mensaje" => "Producto actualizado."]);
    //         } else {
    //             http_response_code(404); // Puede que el producto no exista o los datos sean los mismos
    //             echo json_encode(["mensaje" => "Producto no encontrado o sin cambios."]);
    //         }
    //     } else {
    //         http_response_code(503);
    //         echo json_encode(["mensaje" => "No se pudo actualizar el producto."]);
    //     }
    //     break;

    default:
        // Método no soportado
        http_response_code(405); // Method Not Allowed
        echo json_encode(["mensaje" => "Método no soportado."]);
        break;
}
?>
