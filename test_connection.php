<?php
/**
 * WorkoutMate - Test de Conexión a Base de Datos
 * 
 * Accede a este archivo desde: http://localhost/workoutmate/test_connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 WorkoutMate - Test de Conexión</h1>";
echo "<hr>";

// Test 1: Verificar extensiones PHP
echo "<h2>1. Verificando extensiones PHP</h2>";
$extensions = ['pdo', 'pdo_mysql'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extensión <strong>$ext</strong> está cargada<br>";
    } else {
        echo "❌ Extensión <strong>$ext</strong> NO está cargada<br>";
    }
}
echo "<br>";

// Test 2: Verificar archivo de configuración
echo "<h2>2. Verificando archivos de configuración</h2>";
if (file_exists('config/database.php')) {
    echo "✅ Archivo <strong>config/database.php</strong> existe<br>";
    require_once 'config/database.php';
} else {
    echo "❌ Archivo <strong>config/database.php</strong> NO existe<br>";
    die();
}
echo "<br>";

// Test 3: Probar conexión a MySQL
echo "<h2>3. Probando conexión a MySQL</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ <strong>Conexión exitosa a MySQL</strong><br>";
        echo "📊 Base de datos: workoutmate_db<br>";
        echo "<br>";
        
        // Test 4: Verificar tablas
        echo "<h2>4. Verificando tablas</h2>";
        $query = "SHOW TABLES";
        $stmt = $db->query($query);
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "✅ Tablas encontradas:<br>";
            foreach ($tables as $table) {
                echo "  &nbsp;&nbsp;&nbsp;📋 $table<br>";
            }
        } else {
            echo "⚠️ No se encontraron tablas. Ejecuta el script SQL.<br>";
        }
        echo "<br>";
        
        // Test 5: Contar usuarios
        echo "<h2>5. Verificando datos de prueba</h2>";
        $query = "SELECT COUNT(*) as total FROM users";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "👥 Usuarios en la base de datos: <strong>" . $result['total'] . "</strong><br>";
        
        if ($result['total'] > 0) {
            echo "<br>📋 Lista de usuarios:<br><br>";
            $query = "SELECT id, email, first_name, last_name, role FROM users";
            $stmt = $db->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>Email</th><th>Nombre</th><th>Apellido</th><th>Rol</th>";
            echo "</tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['first_name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['last_name']) . "</td>";
                echo "<td><strong>" . htmlspecialchars($user['role']) . "</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ <strong>No se pudo conectar a MySQL</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error de conexión:</strong> " . $e->getMessage() . "<br>";
}

echo "<br><hr>";
echo "<h2>✅ Test completado</h2>";
echo "<p>Si todos los tests pasaron, tu configuración está lista.</p>";
echo "<p>Ahora puedes probar los endpoints de autenticación.</p>";
?>
