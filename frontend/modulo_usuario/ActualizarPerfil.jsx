import { useState, useEffect } from 'react'; 
import { useNavigate } from 'react-router-dom';
import "../css/modulo_usuario/perfil.css";
import { AdminHeader } from './AdminHeader';
//se encarga de permitir a un usuario actualizar su perfil. Hace uso de hooks como useState, useEffect y useNavigate de React para gestionar el estado del componente y navegar entre páginas.
export default function ActualizarPerfil() {
    const [usuario, setUsuario] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);

    // Nuevo estado para manejar el tipo y la especialidad seleccionados
    const [tipo, setTipo] = useState('');
    const [especialidad, setEspecialidad] = useState('');

    const navigate = useNavigate();
//Cuando el componente se monta, realiza una solicitud para obtener los datos del usuario desde la API, basándose en el ID de usuario almacenado en el localStorage.
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
                    setTipo(data.usuario.tipo || '');
                    setEspecialidad(data.usuario.Especialidad || '');
                    // Registrar en historial
                    
                    await fetch('/api/historial-actividades', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${localStorage.getItem('token')}`
                        },
                        body: JSON.stringify({
                            descripcion: `El usuario estuvo actualizando su perfil`
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
//Antes de enviar los datos al servidor, pide una confirmación al usuario.
    const handleSubmit = async (e) => {
        e.preventDefault();
        const confirmacion = window.confirm("¿Desea actualizar sus datos?");
        if (!confirmacion) {
            return; // Cancela el envío
        }
        try {
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            // Validar datos antes de enviar
            if (!data.nombre || !data.apellido || !data.correo) {
                setError('Nombre, apellido y correo son obligatorios');
                return;
            }

            const payload = {
                id: usuario.ID_Usuario,
                nombre: data.nombre,
                apellido: data.apellido,
                email: data.correo,
                ci: data.ci,
                tipo: data.tipo,
                estado: data.estado,
                contrasena: data.contrasena || undefined,
                especialidad: data.tipo === 'Tecnico' ? data.especialidad : null
            };

            const response = await fetch('/api/usuario/update-profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }

            const result = await response.json();
            if (result.success) {
                setSuccess(true);
                // Actualizar datos en localStorage
                const updatedUser = {
                    ...usuario,
                    nombre: data.nombre,
                    apellido: data.apellido,
                    email: data.correo,
                    tipo: data.tipo,
                    estado: data.estado,
                    Especialidad: data.tipo === 'Tecnico' ? data.especialidad : null
                };
                localStorage.setItem('user', JSON.stringify(updatedUser));

                setTimeout(() => {
                    navigate(-1);
                }, 2000);
                } else {
                setError(result.message || 'Error al actualizar el perfil');
            }
        } catch (err) {
            console.error('Error al actualizar perfil:', err);
            setError('Error de conexión con el servidor. Por favor, intente nuevamente.');
        }
    };

    if (loading) return <div>Cargando...</div>;
    if (!usuario) return <div className="error">{error || 'Usuario no encontrado'}</div>;
//Permite que el usuario modifique su nombre, apellido, correo, contraseña, tipo de usuario (si es administrador) y especialidad (si es técnico).
    return (
        <div className="perfil-contenedor">
            <AdminHeader/>
            <h2>Editar perfil del usuario</h2>
            {error && <div className="error-message">{error}</div>}
            {success && <div className="success-message">¡Perfil actualizado correctamente! Redirigiendo...</div>}

            <form id="formActualizarPerfil" onSubmit={handleSubmit}>
                <input type="hidden" name="ID_Usuario" value={usuario.ID_Usuario} />

                <label>Cédula</label>
                <input type="text" name="ci" defaultValue={usuario.ci} readOnly />

                <label>Nombre</label>
                <input type="text" name="nombre" defaultValue={usuario.nombre} required />

                <label>Apellido</label>
                <input type="text" name="apellido" defaultValue={usuario.apellido} required />

                <label>Correo electrónico</label>
                <input type="email" name="correo" defaultValue={usuario.email} required />

                <label>Usuario asignado</label>
                <input type="text" name="usuario_asignado" defaultValue={usuario.usuario_asignado} readOnly />

                <label>Contraseña (nueva)</label>
                <input type="password" name="contrasena" placeholder="Dejar vacío para no cambiar" />

                <label>Función del sistema</label>
                <select
                    name="tipo"
                    value={tipo}
                    onChange={(e) => setTipo(e.target.value)}
                    disabled
                    //disabled={usuario.tipo !== 'Administrador'}
                >
                    <option value="Administrador">Administrador del sistema</option>
                    <option value="Contabilidad">Área de Contabilidad</option>
                    <option value="Logistica">Logística</option>
                    <option value="Tecnico">Técnico</option>
                    <option value="Usuario">Usuario</option>
                
                </select>
                <input type="hidden" name="tipo" value={tipo} />

                {tipo === 'Tecnico' && (
                    <div>
                        <label>Especialidad</label>
                        <select
                            name="especialidad"
                            value={especialidad}
                            onChange={(e) => setEspecialidad(e.target.value)}
                            disabled
                        >
                            <option value="">Seleccione una especialidad</option>
                            <option value="Ensamblador">Ensamblador</option>
                            <option value="Comprobador">Comprobador</option>
                            <option value="Mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                )}

                <label>Estado</label>
                <select
                    name="estado"
                    defaultValue={usuario.estado}
                    disabled
                >
                    <option value="Activo">Activo</option>
                    <option value="Inhabilitado">Inhabilitado</option>
                    <option value="Pendiente de asignacion">Pendiente de asignación</option>
                </select>
                <input type="hidden" name="estado" value={usuario.estado} />

                <button type="submit">Actualizar Perfil</button>
            </form>
            <button onClick={() => navigate(-1)}>Regresar</button>
        </div>
    );
}