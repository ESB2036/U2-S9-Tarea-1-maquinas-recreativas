USE bd_recrea_sys;
DELIMITER //

-- Registrar comercio
CREATE PROCEDURE sp_registrar_comercio(
    IN p_nombre VARCHAR(100),
    IN p_tipo ENUM('Minorista', 'Mayorista'),
    IN p_direccion TEXT,
    IN p_telefono VARCHAR(15),
    OUT p_id_comercio INT
)
BEGIN
    INSERT INTO Comercio (Nombre, Tipo, Direccion, Telefono, Fecha_Registro) 
    VALUES (p_nombre, p_tipo, p_direccion, p_telefono, CURDATE());
    
    SET p_id_comercio = LAST_INSERT_ID();
END //

-- Obtener todos los comercios
CREATE PROCEDURE sp_obtener_comercios()
BEGIN
    SELECT * FROM Comercio;
END //

-- Obtener comercio por ID
CREATE PROCEDURE sp_obtener_comercio_por_id(IN p_id INT)
BEGIN
    SELECT * FROM Comercio WHERE ID_Comercio = p_id;
END //

-- Incrementar máquinas en comercio
CREATE PROCEDURE sp_incrementar_maquinas_comercio(IN p_id_comercio INT)
BEGIN
    UPDATE Comercio SET Cantidad_Maquinas = Cantidad_Maquinas + 1 
    WHERE ID_Comercio = p_id_comercio;
END //

DELIMITER ;