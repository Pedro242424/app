
<?php
session_start();

// Si ya tiene sesión activa, ir directo al dashboard
if (isset($_SESSION['usuario'])) {
    header("Location: app/dashboard.php");
    exit;
}

// Si NO tiene sesión, mostrar splash screen
header("Location: splash.php");
exit;
?>

## CÓMO FUNCIONA

### Flujo completo:
```
1. Usuario entra a tu sitio (index.php)
   ↓
2. ¿Tiene sesión activa?
   │
   ├─ SÍ → Va directo a app/dashboard.php
   │
   └─ NO → Va a splash.php
       ↓
   3. Splash muestra logo 2 segundos
       ↓
   4. Redirige automáticamente a app/login.php
       ↓
   5. Usuario hace login
       ↓
   6. Va al dashboard