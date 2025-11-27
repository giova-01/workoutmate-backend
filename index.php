<?php
/**
 * WorkoutMate API - Index
 * 
 * Punto de entrada principal de la API
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkoutMate API - Documentación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .status-card {
            background: #e8f5e9;
            border-left: 5px solid #4caf50;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-card {
            background: #e3f2fd;
            border-left: 5px solid #2196f3;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .section {
            margin: 40px 0;
        }
        
        .section h2 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .section h3 {
            color: #764ba2;
            margin: 30px 0 15px 0;
        }
        
        .endpoint {
            background: #f5f5f5;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        
        .method {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-right: 10px;
            color: white;
            font-size: 0.9em;
        }
        
        .post { background-color: #4caf50; }
        .get { background-color: #2196f3; }
        .put { background-color: #ff9800; }
        .delete { background-color: #f44336; }
        
        code {
            background: #263238;
            color: #aed581;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .nav {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        
        .nav a {
            color: #667eea;
            text-decoration: none;
            margin-right: 20px;
            font-weight: 500;
        }
        
        .nav a:hover {
            color: #764ba2;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💪 WorkoutMate API</h1>
            <p>Sistema completo de gestión de rutinas de entrenamiento</p>
        </div>
        
        <div class="content">
            <div class="status-card">
                ✅ <strong>API Operativa</strong> - Todos los servicios funcionando correctamente
            </div>
            
            <div class="info-card">
                📝 <strong>Versión:</strong> 1.0.0 &nbsp;|&nbsp; 
                🗓️ <strong>Última actualización:</strong> <?php echo date('Y-m-d H:i:s'); ?>
            </div>

            <div class="nav">
                <strong>Navegación rápida:</strong><br><br>
                <a href="#auth">Autenticación</a>
                <a href="#workouts">Rutinas</a>
                <a href="#progress">Progreso</a>
                <a href="#admin">Administración</a>
                <a href="#setup">Configuración</a>
            </div>
            
            <a href="test_connection.php" class="btn">🧪 Probar Conexión a BD</a>

            <!-- AUTENTICACIÓN -->
            <div class="section" id="auth">
                <h2>🔐 Autenticación</h2>
                
                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/auth/login</code>
                    <p><strong>Descripción:</strong> Iniciar sesión</p>
                    <pre>{
  "email": "test@workoutmate.com",
  "password": "test123"
}</pre>
                </div>

                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/auth/register</code>
                    <p><strong>Descripción:</strong> Registrar nuevo usuario</p>
                    <pre>{
  "email": "nuevo@test.com",
  "password": "password123",
  "first_name": "Nombre",
  "last_name": "Apellido"
}</pre>
                </div>

                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/auth/logout</code>
                    <p><strong>Descripción:</strong> Cerrar sesión</p>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/auth/me/:userId</code>
                    <p><strong>Descripción:</strong> Obtener información del usuario</p>
                </div>
            </div>

            <!-- WORKOUTS -->
            <div class="section" id="workouts">
                <h2>🏋️ Gestión de Rutinas</h2>
                
                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/workouts/create</code>
                    <p><strong>Descripción:</strong> Crear nueva rutina</p>
                    <pre>{
  "user_id": "uuid",
  "name": "Rutina de Fuerza",
  "category": "STRENGTH",
  "is_public": false,
  "exercises": [
    {
      "name": "Press de banca",
      "sets": 4,
      "repetitions": 12,
      "rest_time": 90,
      "notes": "Mantener forma correcta"
    }
  ]
}</pre>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/workouts/user/:userId</code>
                    <p><strong>Descripción:</strong> Obtener rutinas del usuario</p>
                </div>

                <div class="endpoint">
                    <span class="method put">PUT</span>
                    <code>/workouts/update</code>
                    <p><strong>Descripción:</strong> Actualizar rutina</p>
                </div>

                <div class="endpoint">
                    <span class="method delete">DELETE</span>
                    <code>/workouts/delete/:workoutId</code>
                    <p><strong>Descripción:</strong> Eliminar rutina</p>
                </div>

                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/workouts/share</code>
                    <p><strong>Descripción:</strong> Generar link de compartir</p>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/workouts/search?query=xxx&category=xxx</code>
                    <p><strong>Descripción:</strong> Buscar rutinas públicas</p>
                </div>
            </div>

            <!-- PROGRESS -->
            <div class="section" id="progress">
                <h2>📊 Seguimiento de Progreso</h2>
                
                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/progress/save</code>
                    <p><strong>Descripción:</strong> Guardar progreso de entrenamiento</p>
                    <pre>{
  "user_id": "uuid",
  "workout_id": "uuid",
  "date": "2025-11-10",
  "total_time": 3600,
  "notes": "Excelente sesión",
  "completed_exercises": [
    {
      "exercise_id": "uuid",
      "completed": true,
      "weight": 80.5,
      "actual_reps": 12
    }
  ]
}</pre>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/progress/history/:userId?start_date=xxx&end_date=xxx</code>
                    <p><strong>Descripción:</strong> Obtener historial completo</p>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/progress/weekly/:userId</code>
                    <p><strong>Descripción:</strong> Obtener resumen semanal</p>
                </div>
            </div>

            <!-- ADMIN -->
            <div class="section" id="admin">
                <h2>⚙️ Administración</h2>
                
                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/admin/users?admin_id=xxx</code>
                    <p><strong>Descripción:</strong> Listar todos los usuarios (requiere rol admin)</p>
                </div>

                <div class="endpoint">
                    <span class="method delete">DELETE</span>
                    <code>/admin/user/:userId</code>
                    <p><strong>Descripción:</strong> Eliminar usuario (requiere rol admin)</p>
                </div>

                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/admin/report</code>
                    <p><strong>Descripción:</strong> Crear reporte</p>
                    <pre>{
  "user_id": "uuid (opcional)",
  "type": "USER_REPORT | SYSTEM_ERROR | CONTENT_ISSUE",
  "description": "Descripción del problema"
}</pre>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/admin/reports?admin_id=xxx&status=xxx</code>
                    <p><strong>Descripción:</strong> Obtener reportes (requiere rol admin)</p>
                </div>

                <div class="endpoint">
                    <span class="method put">PUT</span>
                    <code>/admin/report/status</code>
                    <p><strong>Descripción:</strong> Actualizar estado de reporte (requiere rol admin)</p>
                </div>
            </div>

            <!-- CONFIGURACIÓN -->
            <div class="section" id="setup">
                <h2>🔧 Configuración</h2>
                
                <h3>Autenticación</h3>
                <p>Todas las peticiones requieren el siguiente header:</p>
                <code>X-Master-Key: workoutmate_secret_key_2025</code>

                <h3>Categorías de Rutinas</h3>
                <table>
                    <tr>
                        <th>Categoría</th>
                        <th>Descripción</th>
                    </tr>
                    <tr>
                        <td><code>STRENGTH</code></td>
                        <td>Entrenamiento de fuerza</td>
                    </tr>
                    <tr>
                        <td><code>CARDIO</code></td>
                        <td>Ejercicio cardiovascular</td>
                    </tr>
                    <tr>
                        <td><code>FLEXIBILITY</code></td>
                        <td>Flexibilidad y movilidad</td>
                    </tr>
                    <tr>
                        <td><code>FUNCTIONAL</code></td>
                        <td>Entrenamiento funcional</td>
                    </tr>
                    <tr>
                        <td><code>MIXED</code></td>
                        <td>Rutina mixta</td>
                    </tr>
                </table>

                <h3>Estados de Reportes</h3>
                <table>
                    <tr>
                        <th>Estado</th>
                        <th>Descripción</th>
                    </tr>
                    <tr>
                        <td><code>PENDING</code></td>
                        <td>Reporte pendiente de revisión</td>
                    </tr>
                    <tr>
                        <td><code>IN_PROGRESS</code></td>
                        <td>Reporte en proceso</td>
                    </tr>
                    <tr>
                        <td><code>RESOLVED</code></td>
                        <td>Reporte resuelto</td>
                    </tr>
                    <tr>
                        <td><code>REJECTED</code></td>
                        <td>Reporte rechazado</td>
                    </tr>
                </table>

                <h3>Usuarios de Prueba</h3>
                <table>
                    <tr>
                        <th>Rol</th>
                        <th>Email</th>
                        <th>Password</th>
                    </tr>
                    <tr>
                        <td><strong>Admin</strong></td>
                        <td>admin@workoutmate.com</td>
                        <td>admin123</td>
                    </tr>
                    <tr>
                        <td><strong>Usuario</strong></td>
                        <td>test@workoutmate.com</td>
                        <td>test123</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h2>📚 Recursos Adicionales</h2>
                <p>Para más información sobre la configuración e instalación, consulta el archivo <code>README_COMPLETE.md</code></p>
                <p><strong>Repositorio:</strong> <a href="#">GitHub</a></p>
                <p><strong>Documentación completa:</strong> <a href="#">Docs</a></p>
            </div>
        </div>
    </div>
</body>
</html>
