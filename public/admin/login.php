<?php
/**
 * PANEL DE CONTROL - LOGIN DE USUARIOS DE SOPORTE
 */

require_once __DIR__ . '/../../includes/auth.php';

// Si ya tiene sesión activa, redirigir al panel
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    $res = loginUser($usuario, $password);
    if ($res['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Soporte TI - <?= APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
                
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 bg-primary bg-opacity-25 rounded-circle text-info mb-2 border border-info border-opacity-25">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1"><?= APP_NAME ?></h3>
                    <p class="text-secondary small">Panel Exclusivo para Técnicos de Soporte</p>
                </div>

                <div class="card card-custom shadow-lg">
                    <div class="card-body p-4 p-sm-5">
                        
                        <h4 class="fw-bold text-dark mb-4">Iniciar Sesión</h4>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center small py-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                                <div><?= htmlspecialchars($error) ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php" autocomplete="off">
                            
                            <div class="mb-3">
                                <label for="usuario" class="form-label fw-semibold">Usuario de Soporte</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Username" required autofocus value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-custom text-white w-100 py-2 fw-bold shadow">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar al Panel
                            </button>

                        </form>


                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="../index.php" class="text-secondary small text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Ir a Formulario Público
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
