-- Create default admin user
INSERT INTO users (
    username,
    password_hash,
    full_name,
    email,
    user_level,
    status,
    created_at
) VALUES (
    'admin',
    -- Password: Admin@123
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewLxH1b6PGnOPP.i',
    'Administrador do Sistema',
    'admin@sistema.com',
    'admin',
    'active',
    NOW()
) ON DUPLICATE KEY UPDATE id = id;
