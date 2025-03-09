<?php
require_once 'init.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $auth = Auth::getInstance();
        if ($auth->authenticate($username, $password)) {
            // Redirect to dashboard
            header('Location: index.php');
            exit();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get flash message if any
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Vendas</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
        }
        .login-form {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
        .login-form .form-floating {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <main class="login-form text-center">
        <form method="post" class="needs-validation" novalidate>
            <h1 class="h3 mb-4">Sistema de Vendas</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Usuário" required>
                <label for="username">Usuário</label>
            </div>
            
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Senha" required>
                <label for="password">Senha</label>
            </div>
            
            <button class="w-100 btn btn-lg btn-primary" type="submit">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
            
            <p class="mt-3">
                <a href="reset_password.php" class="text-decoration-none">
                    <i class="fas fa-key"></i> Esqueceu sua senha?
                </a>
            </p>
            
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?></p>
        </form>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
