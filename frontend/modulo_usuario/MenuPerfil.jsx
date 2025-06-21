import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import "../css/modulo_usuario/menu_perfil.css";
import { AdminHeader } from './AdminHeader';
//muestra el perfil de un usuario, incluyendo su información personal (nombre, correo electrónico, tipo de usuario, etc.). Utiliza useState para manejar el estado del usuario, la carga de datos y los posibles errores. Con useEffect, hace una solicitud a la API para obtener los detalles del perfil del usuario desde el servidor. Si el usuario está autenticado, se muestra un saludo personalizado según su tipo de usuario. También registra en el historial de actividades la visualización del perfil del usuario.
export default function MenuPerfil() {
    const [usuario, setUsuario] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    useEffect(() => {
        const fetchUsuario = async () => {
            try {
                const user = JSON.parse(localStorage.getItem('user'));
                if (!user || !user.ID_Usuario) {
                    navigate('/login');
                    return;
                }

                const response = await fetch(`/api/usuario/profile/${user.ID_Usuario}`);
                const data = await response.json();
                
                if (data.success) {
                    setUsuario(data.usuario);
                    // Registrar en historial
                    await fetch('/api/historial-actividades', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${localStorage.getItem('token')}`
                        },
                        body: JSON.stringify({
                            ID_Usuario: user.ID_Usuario,
                            descripcion: "El usuario estuvo en su perfil"
                        })
                    });
                } else {
                    setError(data.message);
                }
            } catch (err) {
                setError('Error al cargar el perfil');
            } finally {
                setLoading(false);
            }
        };

        fetchUsuario();
    }, [navigate]);

    const getSaludo = () => {
        if (!usuario) return '';
        const ci = usuario.ci;
        
        switch(usuario.tipo) {
            case "Logistica":
                return `HOLA LOGÍSTICO CON NÚMERO DE CÉDULA: ${ci}`;
            case "Contabilidad":
                return `HOLA CONTADOR CON NÚMERO DE CÉDULA: ${ci}`;
            case "Tecnico":
                return `HOLA TÉCNICO CON NÚMERO DE CÉDULA: ${ci}`;
            case "Administrador":
                return `HOLA ADMINISTRADOR DEL SISTEMA CON NÚMERO DE CÉDULA: ${ci}`;
            default:
                return `Hola, Usuario con número de cédula: ${ci}`;
        }
    };

    if (loading) return <div>Cargando...</div>;
    if (error) return <div className="error">{error}</div>;

    return (
        <div className="contenedor_perfil">
            <AdminHeader/>
            <div className="encabezado">
                <h1 id="saludo_usuario">{getSaludo()}</h1>
            </div>
            <div className="contenedor_todo">
                <div className="caja_trasera">
                    <div>
                        <button onClick={() => navigate('/usuario/actualizar-perfil')}>Editar</button>
                    </div>
                </div>
            </div>
            <div className="perfil_detalles">
                <h2>Detalles de Usuario</h2>
                <form id="form_detalles" readOnly>
                    <label>Cédula</label>
                    <input 
                        type="text" 
                        name="ci" 
                        value={usuario.ci} 
                        readOnly
                    />

                    <label>Nombre</label>
                    <input 
                        type="text" 
                        name="nombre" 
                        value={usuario.nombre} 
                        readOnly
                    />

                    <label>Apellido</label>
                    <input 
                        type="text" 
                        name="apellido" 
                        value={usuario.apellido} 
                        readOnly
                    />

                    <label>Correo electrónico</label>
                    <input 
                        type="email" 
                        name="correo" 
                        value={usuario.email} 
                        readOnly
                    />

                    <label>Usuario asignado</label>
                    <input 
                        type="text" 
                        name="usuario_asignado" 
                        value={usuario.usuario_asignado} 
                        readOnly
                    />

                    <label>Función del sistema</label>
                    <select name="tipo" value={usuario.tipo === 'Administrador' ? 'Administrador' : usuario.tipo} disabled>
                        <option value="Administrador">Administrador del sistema</option>
                        <option value="Logistica">Logística</option>
                        <option value="Contabilidad">Área de Contabilidad</option>
                        <option value="Tecnico">Técnico</option>
                    </select>

                    <label>Estado</label>
                    <select name="estado" value={usuario.estado} disabled>
                        <option value="Activo">Activo</option>
                        <option value="Inhabilitado">Inhabilitado</option>
                    </select>
                    <div id="historial"></div>
                </form>
                <button onClick={() => navigate(-1)}>Regresar</button>
            </div>
        </div>
    );
}