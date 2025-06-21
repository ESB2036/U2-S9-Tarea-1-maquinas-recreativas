USE bd_recrea_sys;
DELIMITER //

-- Procedimiento para crear un comentario
CREATE PROCEDURE sp_crear_comentario(
    IN p_id_reporte INT,
    IN p_id_usuario_emisor INT,
    IN p_comentario TEXT,
    OUT p_id_comentario INT
)
BEGIN
    INSERT INTO comentario (ID_Reporte, ID_Usuario_Emisor, comentario, fecha_hora) 
    VALUES (p_id_reporte, p_id_usuario_emisor, p_comentario, NOW());
    
    SET p_id_comentario = LAST_INSERT_ID();
END //

-- Procedimiento para obtener comentarios por reporte con verificación de acceso
CREATE PROCEDURE sp_obtener_comentarios_por_reporte(
    IN p_id_reporte INT,
    IN p_id_usuario INT
)
BEGIN
    -- Verificar acceso al reporte
    IF EXISTS (
        SELECT 1 FROM reporte 
        WHERE ID_Reporte = p_id_reporte 
        AND (ID_Usuario_Emisor = p_id_usuario OR ID_Usuario_Destinatario = p_id_usuario)
    ) THEN
        SELECT c.*, u.nombre, u.apellido, u.tipo
        FROM comentario c
        JOIN usuario u ON c.ID_Usuario_Emisor = u.ID_Usuario
        WHERE c.ID_Reporte = p_id_reporte
        ORDER BY c.fecha_hora ASC;
    END IF;
END //

-- Procedimiento para obtener comentarios por chat entre dos usuarios
CREATE PROCEDURE sp_obtener_comentarios_por_chat(
    IN p_id_emisor INT,
    IN p_id_destinatario INT
)
BEGIN
    SELECT c.*, u.nombre, u.apellido, u.tipo
    FROM comentario c
    JOIN usuario u ON c.ID_Usuario_Emisor = u.ID_Usuario
    JOIN reporte r ON c.ID_Reporte = r.ID_Reporte
    WHERE (r.ID_Usuario_Emisor = p_id_emisor AND r.ID_Usuario_Destinatario = p_id_destinatario)
    OR (r.ID_Usuario_Emisor = p_id_destinatario AND r.ID_Usuario_Destinatario = p_id_emisor)
    ORDER BY c.fecha_hora ASC;
END //

DELIMITER ;