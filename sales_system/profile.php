<?php
/**
 * User Profile Management Script
 * Allows users to update their information and change password
 */

require_once 'init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user data
try {
    $stmt = $conn->prepare("
        SELECT id, username, full_name, email, user_level, created_at, last_login
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    header('Location: error.php?code=500&message=Erro ao carregar dados do usuário');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_profile':
                    // Validate input
                    $validator = new Validator($_POST, [
                        'full_name' => 'required|min:3',
                        'email' => 'required|email'
                    ]);
                    
                    if ($validator->validate()) {
                        $data = $validator->getSanitized();
                        
                        // Check if email is already used
                        $stmt = $conn->prepare("
                            SELECT id FROM users 
                            WHERE email = ? AND id != ?
                        ");
                        $stmt->execute([$data['email'], $user['id']]);
                        if ($stmt->fetch()) {
                            throw new Exception('Este email já está em uso.');
                        }
                        
                        // Update user data
                        $stmt = $conn->prepare("
                            UPDATE users 
                            SET full_name = ?, email = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $data['full_name'],
                            $data['email'],
                            $user['id']
                        ]);
                        
                        $success = 'Perfil atualizado com sucesso.';
                        
                        // Update user data in session
                        $_SESSION['full_name'] = $data['full_name'];
                        
                        // Refresh user data
                        $user['full_name'] = $data['full_name'];
                        $user['email'] = $data['email'];
                        
                        // Log activity
                        $logger->info("Profile updated", [
                            'user_id' => $user['id']
                        ]);
                    } else {
                        throw new Exception('Dados inválidos.');
                    }
                    break;
                    
                case 'change_password':
                    // Validate input
                    $validator = new Validator($_POST, [
                        'current_password' => 'required',
                        'new_password' => 'required|min:8',
                        'confirm_password' => 'required'
                    ]);
                    
                    if ($validator->validate()) {
                        // Verify current password
                        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        $currentHash = $stmt->fetchColumn();
                        
                        if (!password_verify($_POST['current_password'], $currentHash)) {
                            throw new Exception('Senha atual incorreta.');
                        }
                        
                        // Verify new password confirmation
                        if ($_POST['new_password'] !== $_POST['confirm_password']) {
                            throw new Exception('As senhas não conferem.');
                        }
                        
                        // Update password
                        $newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$newHash, $user['id']]);
                        
                        $success = 'Senha alterada com sucesso.';
                        
                        // Log activity
                        $logger->info("Password changed", [
                            'user_id' => $user['id']
                        ]);
                    } else {
                        throw new Exception('Dados inválidos.');
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Include header
$pageTitle = "Meu Perfil";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Meu Perfil</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Informações do Perfil</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Usuário</label>
                                    <input type="text" class="form-control" id="username" 
                                           value="<?php echo h($user['username']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Nome Completo</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo h($user['full_name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo h($user['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nível de Acesso</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo h(ucfirst($user['user_level'])); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Cadastrado em</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo formatDate($user['created_at'], true); ?>" readonly>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Salvar Alterações
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Alterar Senha</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Senha Atual</label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required>
                                    <div class="form-text">A senha deve ter pelo menos 8 caracteres.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Alterar Senha
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Atividades Recentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Ação</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $conn->prepare("
                                            SELECT created_at, action, ip_address 
                                            FROM activity_log 
                                            WHERE user_id = ? 
                                            ORDER BY created_at DESC 
                                            LIMIT 10
                                        ");
                                        $stmt->execute([$user['id']]);
                                        while ($activity = $stmt->fetch()):
                                        ?>
                                        <tr>
                                            <td><?php echo formatDate($activity['created_at'], true); ?></td>
                                            <td><?php echo h($activity['action']); ?></td>
                                            <td><?php echo h($activity['ip_address']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

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
    document.getElementById('confirm_password')?.addEventListener('input', function() {
        const password = document.getElementById('new_password').value;
        if (this.value !== password) {
            this.setCustomValidity('As senhas não conferem');
        } else {
            this.setCustomValidity('');
        }
    });
</script>
