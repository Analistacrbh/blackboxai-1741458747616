<?php
session_start();
require_once '../../config/database.php';

// Check authentication and admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários - Sistema de Vendas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .action-buttons .btn {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gerenciamento de Usuários</h1>
                    <button class="btn btn-primary" onclick="showUserModal()">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </button>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome Completo</th>
                                        <th>Usuário</th>
                                        <th>Nível</th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userId" name="id">
                        
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="fullName" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuário</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted">Deixe em branco para manter a senha atual (ao editar)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="userLevel" class="form-label">Nível de Acesso</label>
                            <select class="form-select" id="userLevel" name="user_level" required>
                                <option value="admin">Administrador</option>
                                <option value="super">Superusuário</option>
                                <option value="user">Usuário</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let userTable;
        
        $(document).ready(function() {
            // Initialize DataTable
            userTable = $('#usersTable').DataTable({
                ajax: 'api.php?action=list_users',
                columns: [
                    { data: 'id' },
                    { data: 'full_name' },
                    { data: 'username' },
                    { 
                        data: 'user_level',
                        render: function(data) {
                            const levels = {
                                'admin': 'Administrador',
                                'super': 'Superusuário',
                                'user': 'Usuário'
                            };
                            return levels[data] || data;
                        }
                    },
                    {
                        data: 'status',
                        render: function(data) {
                            const badge = data === 'active' ? 
                                'badge bg-success' : 'badge bg-danger';
                            const text = data === 'active' ? 
                                'Ativo' : 'Inativo';
                            return `<span class="${badge}">${text}</span>`;
                        }
                    },
                    { 
                        data: 'created_at',
                        render: function(data) {
                            return new Date(data).toLocaleDateString('pt-BR');
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editUser(${data.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${data.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });
        });

        // Show modal for new user
        function showUserModal() {
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#password').attr('required', true);
            $('#userModal').modal('show');
        }

        // Edit user
        function editUser(id) {
            $.get('api.php', { action: 'get_user', id: id })
                .done(function(response) {
                    const user = response.data;
                    $('#userId').val(user.id);
                    $('#fullName').val(user.full_name);
                    $('#username').val(user.username);
                    $('#userLevel').val(user.user_level);
                    $('#status').val(user.status);
                    $('#password').attr('required', false);
                    $('#userModal').modal('show');
                })
                .fail(function(jqXHR) {
                    Swal.fire('Erro', 'Erro ao carregar dados do usuário', 'error');
                });
        }

        // Save user
        function saveUser() {
            const formData = new FormData($('#userForm')[0]);
            const id = $('#userId').val();
            const action = id ? 'update_user' : 'create_user';
            
            $.ajax({
                url: 'api.php?action=' + action,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#userModal').modal('hide');
                    userTable.ajax.reload();
                    Swal.fire('Sucesso', 'Usuário salvo com sucesso', 'success');
                },
                error: function(jqXHR) {
                    Swal.fire('Erro', 'Erro ao salvar usuário', 'error');
                }
            });
        }

        // Delete user
        function deleteUser(id) {
            Swal.fire({
                title: 'Confirmar exclusão',
                text: 'Tem certeza que deseja excluir este usuário?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api.php', { action: 'delete_user', id: id })
                        .done(function(response) {
                            userTable.ajax.reload();
                            Swal.fire('Sucesso', 'Usuário excluído com sucesso', 'success');
                        })
                        .fail(function(jqXHR) {
                            Swal.fire('Erro', 'Erro ao excluir usuário', 'error');
                        });
                }
            });
        }
    </script>
</body>
</html>
