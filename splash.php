<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: app/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharkTask</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .splash-content {
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        /* Logo imagen PNG */
        .logo {
            width: 200px;              /* Ajusta según tu gusto */
            height: auto;
            margin-bottom: 20px;
            animation: zoomIn 1s ease-out;
            filter: drop-shadow(0 10px 40px rgba(0,0,0,0.3));
        }

        /* Si tu logo es cuadrado, puedes agregar: */
        /* .logo { border-radius: 20px; } */

        h1 {
            color: white;
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: slideUp 0.8s ease-out 0.3s both;
        }

        .tagline {
            color: rgba(255,255,255,0.9);
            font-size: 18px;
            animation: slideUp 0.8s ease-out 0.5s both;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            margin: 30px auto 0;
            animation: spin 1s linear infinite;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.5);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="splash-content">
        <!-- TU LOGO PNG AQUÍ -->
        <img src="assets/img/logo_sharktask.png" alt="SharkTask Logo" class="logo">
        
        <h1>SharkTask</h1>
        <p class="tagline">Devora tus pendientes</p>
        <div class="loader"></div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = 'app/login.php';
        }, 2000);
    </script>
</body>
</html>