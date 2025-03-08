<?php
/**
 * Password Reset Script
 * Handles password reset requests and token validation
 */

require_once 'init.php';

// Initialize variables
$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['email'])) {
            // Handle password reset request
            $validator = new Validator($_POST, [
                'email' => 'required|email'
            ]);
            
            if ($validator->validate()) {
                $email = $validator->getSanitized()['email'];
                
                // Check if email exists
                $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? AND status = 'active'");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Save reset token
                    $stmt = $conn->prepare("
                        INSERT INTO password_resets (user_id, token, expires_at) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$user['id'], $token, $expires]);
                    
                    // Send reset email
                    $resetLink = getBaseUrl() . 'reset_password.php?token=' . $token . '&email=' . urlencode($email);
                    $subject = 'Redefinição de Senha - ' . SYSTEM_NAME;
                    $message = "Olá {$user['username']},\n\n";
                    $message .= "Foi solicitada uma redefinição de senha para sua conta.\n";
                    $message .= "Para redefinir sua senha, clique no link abaixo:\n\n";
                    $message .= $resetLink . "\n\n";
                    $message .= "Este link é válido por 1 hora.\n";
                    $message .= "Se você não solicitou esta redefinição, ignore este email.\n\n";
                    $message .= "Atenciosamente,\n";
                    $message .= SYSTEM_NAME;
                    
                    if (mail($email, $subject, $message)) {
                        $success = 'Um email foi enviado com instruções para redefinir sua senha.';
                        
                        // Log password reset request
                        $logger->info("Password reset requested", [
                            'user_id' => $user['id'],
                            'email' => $email
                        ]);
                    } else {
                        throw new Exception('Erro ao enviar email. Tente novamente mais tarde.');
                    }
                } else {
                    // Show same message to prevent email enumeration
                    $success = 'Se o email existir em nossa base, você receberá as instruções em breve.';
                }
            } else {
                $error = 'Email inválido.';
            }
        } elseif (isset($_POST['password'])) {
            // Handle password reset
            $validator = new Validator($_POST, [
                'password' => 'required|min:8',
                'password_confirm' => 'required'
            ]);
            
            if ($validator->validate()) {
                if ($_POST['password'] !== $_POST['password_confirm']) {
                    throw new Exception('As senhas não conferem.');
                }
                
                // Verify token
                $stmt = $conn->prepare("
                    SELECT pr.user_id, u.email 
                    FROM password_resets pr
                    JOIN users u ON pr.user_id = u.id
                    WHERE pr.token = ? 
                    AND pr.used = 0
                    AND pr.expires_at > NOW()
                    AND u.email = ?
                ");
                $stmt->execute([$token, $email]);
                $reset = $stmt->fetch();
                
                if ($reset) {
                    // Update password
                    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$passwordHash, $reset['user_id']]);
                    
                    // Mark token as used
                    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                    $stmt->execute([$token]);
                    
                    $success = 'Senha alterada com sucesso. Você pode fazer login agora.';
                    
                    // Log password reset
                    $logger->info("Password reset completed", [
                        'user_id' => $reset['user_id']
                    ]);
                } else {
                    throw new Exception('Link de redefinição inválido ou expirado.');
                }
            } else {
                $error = 'A senha deve ter pelo menos 8 caracteres.';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .reset-form {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .system-name {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-form">
            <h2 class="system-name"><?php echo SYSTEM_NAME; ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary btn-sm">Ir para Login</a>
                    </div>
                </div>
            <?php elseif ($token && $email): ?>
                <!-- Reset Password Form -->
                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="password" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">A senha deve ter pelo menos 8 caracteres.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="password_confirm" 
                               name="password_confirm" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        Redefinir Senha
                    </button>
                </form>
            <?php else: ?>
                <!-- Request Reset Form -->
                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        Solicitar Redefinição
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i> Voltar para Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

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
        
        // Password confirmation validation
        document.getElementById('password_confirm')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.setCustomValidity('As senhas não conferem');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
