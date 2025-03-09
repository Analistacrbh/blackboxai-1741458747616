-- Create admin user with password: 328050
INSERT INTO users (username, password_hash, full_name, email, user_level, status)
VALUES (
    'adm',
    '$2y$10$vSf1Js/Wbg23.tTDpdGtleD3CujyEQa0aGccIUSBIKbZ1YvOXu2Ta',
    'Administrador',
    'admin@sistema.com',
    'admin',
    'active'
);
