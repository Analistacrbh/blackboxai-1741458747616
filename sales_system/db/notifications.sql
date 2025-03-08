-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    module VARCHAR(50),
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for faster queries
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(read_at);
CREATE INDEX idx_notifications_created ON notifications(created_at);

-- Trigger to clean old notifications
DELIMITER //
CREATE TRIGGER clean_old_notifications
BEFORE INSERT ON notifications
FOR EACH ROW
BEGIN
    -- Delete notifications older than 30 days that have been read
    DELETE FROM notifications 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
    AND read_at IS NOT NULL;
END//
DELIMITER ;

-- Sample notifications for testing
INSERT INTO notifications (user_id, message, type, module) VALUES
(1, 'Bem-vindo ao Sistema de Vendas!', 'success', 'system'),
(1, 'Complete seu perfil para melhor experiência', 'info', 'users'),
(1, 'Nova venda registrada: #12345', 'info', 'sales'),
(1, 'Backup do sistema realizado com sucesso', 'success', 'system'),
(1, 'Estoque baixo para produto XYZ', 'warning', 'inventory');
