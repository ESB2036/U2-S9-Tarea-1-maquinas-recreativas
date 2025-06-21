import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
//Este componente muestra el perfil de un usuario específico basado en el ID obtenido desde la URL. 
// Utiliza useEffect para hacer una solicitud al backend y obtener los datos del usuario.
const PerfilUsuario = () => {
    const [searchParams] = useSearchParams();
    const id = searchParams.get('id');
    const [usuario, setUsuario] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
{/**  Condiciones:
Si no hay id, se muestra un mensaje de error.
Si los datos están cargando, se muestra un mensaje "Cargando...".
Si ocurre un error o no se encuentra el usuario, se muestra el mensaje correspondiente.
Si se cargan los datos correctamente, se visualiza el perfil.
    */}
    useEffect(() => {
        if (!id) {
            setError('ID de usuario no proporcionado');
            setLoading(false);
            return;
        }

        const fetchUsuario = async () => {
            try {
                const response = await fetch(`/api/usuario/profile/${id}`);
                const data = await response.json();
                
                if (data.success) {
                    setUsuario(data.usuario);
                } else {
                    setError(data.message);
                }
            } catch (err) {
                setError('Error al cargar el perfil del usuario');
            } finally {
                setLoading(false);
            }
        };

        fetchUsuario();
    }, [id]);

    if (loading) return <div>Cargando...</div>;
    if (error) return <div className="error">{error}</div>;
    if (!usuario) return <div>Usuario no encontrado</div>;

    return (
        <div className="perfil-container">
            <h1>Perfil de Usuario</h1>
            <div className="perfil-info">
                <p><strong>Nombre:</strong> {usuario.nombre} {usuario.apellido}</p>
                <p><strong>Cédula:</strong> {usuario.ci}</p>
                <p><strong>Email:</strong> {usuario.email}</p>
                <p><strong>Usuario:</strong> {usuario.usuario_asignado}</p>
                {usuario.tipo === 'Tecnico' && (
                    <p><strong>Especialidad:</strong> {usuario.Especialidad}</p>
                )}
            </div>
        </div>
    );
};

export default PerfilUsuario;