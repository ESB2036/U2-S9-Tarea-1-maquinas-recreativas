import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import '../css/modulo_administrador/mainAdministrador.css';
import { AdminHeader } from '../modulo_usuario/AdminHeader';
export default function GestionUsuarios() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();
//Este componente representa la interfaz de gestión de usuarios del panel de administrador. Permite registrar, consultar, actualizar y eliminar usuarios. Además, registra una actividad cada vez que el componente se monta (lo simula con un fetch a un endpoint, como si se tratara de una auditoría o bitácora de acciones del usuario).
    //Ejecuta una sola vez al montar el componente para registrar la actividad del usuario.
    useEffect(() => {
        const registrarActividad = async () => {
            try {
                await fetch('/api/historial-actividades', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        descripcion: "El usuario estuvo en la gestión de usuarios"
                    })
                });
            } catch (err) {
                console.error('Error al registrar actividad:', err);
            }
        };

        registrarActividad();
    }, []);
{/**Acciones disponibles en la UI:
Botón "Regresar" para volver al panel principal del administrador.
Botón para registrar un nuevo usuario.
Botón para consultar, actualizar o eliminar usuarios.
    */}
    return (
        <div className="gestion-usuarios-container">
            <AdminHeader/>
            <div className="perfil-contenedor">
                <h2>Gestión de Usuarios</h2>
                
                <button onClick={() => navigate('/dashboard/admin')}>Regresar</button>
                
                <div className="admin-actions">
                <button onClick={() => navigate('/admin/gestion-usuarios/registrar-usuario')}>
                    Registrar usuario
                </button>
                <button onClick={() => navigate('/admin/gestion-usuarios/consultar-usuarios')}>
                    Consultar usuarios(Actualizar y Borrar)
                </button>
                </div>
            </div>
        </div>
    );
}