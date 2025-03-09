-- Insert default admin user
INSERT INTO users (
    username,
    password_hash,
    full_name,
    email,
    user_level,
    status,
    created_at
) VALUES (
    'adm',
    '$2y$10$Rl9zWNnkJBDCBJxYeNe6FeHfYnFPQGGnC0Qz2n7YrYVBOHYXVEjGi', -- password: 328050 (properly hashed)
    'Administrador do Sistema',
    'admin@sistema.com',
    'admin',
    'active',
    NOW()
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    email = VALUES(email),
    user_level = VALUES(user_level),
    status = VALUES(status);

-- Clear any existing login attempts
DELETE FROM login_attempts WHERE username = 'adm';
