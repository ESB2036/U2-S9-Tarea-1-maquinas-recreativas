import React, { useState, useEffect } from 'react';
import { useAuth } from '../src/context/AuthContext';
import { useNavigate } from 'react-router-dom';
import '../css/modulo_reporte/gestionReportes.css';
//Este componente permite a los usuarios crear, listar, filtrar y actualizar reportes dentro del sistema. Su comportamiento varía dependiendo si el usuario es un administrador o un usuario restringido.
const GestionReportes = ({ currentUser, adminMode = false, onClose }) => {
    const [reportes, setReportes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [nuevoReporte, setNuevoReporte] = useState({ 
        destinatario: '', 
        descripcion: '',
        tipoDestinatario: ''
    });
    const [usuarios, setUsuarios] = useState([]);
    const [filtroEstado, setFiltroEstado] = useState('todos');
    const [tiposUsuario] = useState([
        'Tecnico',
        'Contabilidad',
        'Logistica',
        'Administrador'
    ]);
    const navigate = useNavigate();
//	Carga los reportes del usuario o la lista de administradores, según adminMode.
    useEffect(() => {
        if (!adminMode) {
            const cargarReportes = async () => {
                try {
                    const response = await fetch(`/api/reportes/usuario/${currentUser.ID_Usuario}`);
                    if (!response.ok) throw new Error('Error al cargar reportes');
                    const data = await response.json();
                    setReportes(data.reportes);
                } catch (err) {
                    setError(err.message);
                } finally {
                    setLoading(false);
                }
            };
            cargarReportes();
        } else {
            const cargarAdministradores = async () => {
                try {
                    const response = await fetch(`/api/usuarios/por-tipo?tipo=Administrador&emisorId=${currentUser.ID_Usuario}`);
                    const data = await response.json();
                    if (data.success) {
                        setUsuarios(data.usuarios);
                    } else {
                        throw new Error(data.message || 'Error al cargar administradores');
                    }
                } catch (err) {
                    setError(err.message);
                } finally {
                    setLoading(false);
                }
            };
            cargarAdministradores();
        }
    }, [adminMode, currentUser]);
//Carga los usuarios filtrados por tipo de área seleccionada (e.g., Técnico, Logística).
    const cargarUsuariosPorTipo = async (tipo) => {
        try {
            const response = await fetch(`/api/usuarios/por-tipo?tipo=${encodeURIComponent(tipo)}&emisorId=${currentUser.ID_Usuario}`);
            if (!response.ok) throw new Error('Error al cargar usuarios');
            const data = await response.json();
            setUsuarios(data.usuarios);
        } catch (err) {
            console.error('Error al cargar usuarios:', err);
            setUsuarios([]);
        }
    };
//Envía un nuevo reporte al servidor y registra la actividad. Valida campos y comportamiento según el rol del usuario.
    const handleSubmitReporte = async (e) => {
        e.preventDefault();
        if (!window.confirm('¿Está seguro de enviar reporte?')) {
            return;
        }
        try {
            if (!nuevoReporte.destinatario || !nuevoReporte.descripcion) {
                throw new Error('Debes seleccionar un destinatario y escribir una descripción');
            }

            const descripcionFinal = adminMode 
                ? `[USUARIO RESTRINGIDO] ${nuevoReporte.descripcion}`
                : nuevoReporte.descripcion;

            const response = await fetch('/api/reportes/crear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ID_Usuario_Emisor: currentUser.ID_Usuario,
                    ID_Usuario_Destinatario: nuevoReporte.destinatario,
                    descripcion: descripcionFinal
                })
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Error al crear reporte');

            if (!adminMode) {
                const resReportes = await fetch(`/api/reportes/usuario/${currentUser.ID_Usuario}`);
                const reportesData = await resReportes.json();
                setReportes(reportesData.reportes);
            }

            alert('Reporte enviado correctamente.');
            await fetch('/api/historial-actividades', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({
                    descripcion: `El usuario envió un reporte`
                })
            });
            setNuevoReporte({ destinatario: '', descripcion: '', tipoDestinatario: '' });
            setUsuarios([]);
            if (onClose) onClose();
        } catch (err) {
            setError(err.message);
        }
    };
//Actualiza los campos del formulario y, si se cambia el tipo de destinatario, carga los usuarios correspondientes.
    const handleChange = (e) => {
        const { name, value } = e.target;
        setNuevoReporte(prev => ({ ...prev, [name]: value }));
        if (name === 'tipoDestinatario') cargarUsuariosPorTipo(value);
    };
//	Redirige al chat relacionado con el reporte seleccionado.
    const handleVerChat = (reporte) => {
        navigate(`/reportes/chat?reporteId=${reporte.ID_Reporte}&currentUserId=${currentUser.ID_Usuario}`);
    };
//Cambia el estado de un reporte (e.g., a "Resuelto") y registra la acción en el historial.
    const handleActualizarEstado = async (reporteId, nuevoEstado) => {
        try {
            const response = await fetch(`/api/reportes/${reporteId}/estado`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ estado: nuevoEstado })
            });

            const data = await response.json();
            if (data.success) {
                const updatedReportes = reportes.map(reporte =>
                    reporte.ID_Reporte === reporteId ? { ...reporte, estado: nuevoEstado } : reporte
                );
                setReportes(updatedReportes);
                await fetch('/api/historial-actividades', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    },
                    body: JSON.stringify({
                        descripcion: `El usuario actualizó el estado de un reporte`
                    })
                });
            } else {
                throw new Error(data.message || 'Error al actualizar estado');
            }
        } catch (err) {
            setError(err.message);
        }
    };
//Filtra los reportes según el estado seleccionado (Todos, Pendiente, En proceso, Resuelto).
    const reportesFiltrados = filtroEstado === 'todos' 
        ? reportes 
        : reportes.filter(r => r.estado === filtroEstado);

    if (!currentUser || !currentUser.ID_Usuario) {
        return <div className="error">Usuario no autenticado</div>;
    }

    if (loading) return <div className="loading">Cargando...</div>;
    if (error) return <div className="error">{error}</div>;
/**
 * Si el componente está en modo admin (adminMode es true), se muestra un formulario simplificado para enviar reportes directamente a administradores.
Si está en modo usuario normal, se muestra el listado de reportes enviados por el usuario, filtros por estado, y un formulario más completo para enviar nuevos reportes a distintas áreas.
 * 
 */
    return (
        <div className={`gestion-reportes-container ${adminMode ? 'admin-mode' : ''}`}>
            {adminMode ? (
                <>
                    <h2>Contactar con Administrador</h2>
                    <p>Estás enviando un reporte como usuario con acceso restringido</p>
                    <form onSubmit={handleSubmitReporte}>
                        <div className="form-group">
                            <label>Administrador:</label>
                            <select 
                                name="destinatario" 
                                value={nuevoReporte.destinatario}
                                onChange={handleChange}
                                required
                            >
                                <option value="">Seleccionar administrador</option>
                                {usuarios.map(user => (
                                    <option key={user.ID_Usuario} value={user.ID_Usuario}>
                                        {user.nombre} {user.apellido} ({user.email})
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="form-group">
                            <label>Descripción:</label>
                            <textarea
                                name="descripcion"
                                value={nuevoReporte.descripcion}
                                onChange={handleChange}
                                placeholder="Describe tu situación..."
                                required
                            />
                        </div>
                        <div className="button-group">
                            <button type="submit" className="btn-enviar">Enviar Reporte</button>
                            {onClose && (
                                <button type="button" onClick={onClose} className="btn-cancel">Cancelar</button>
                            )}
                        </div>
                    </form>
                </>
            ) : (
                <>
                    <h2>Gestión de Reportes</h2>
                    <div className="filtros">
                        <label>Filtrar por estado:</label>
                        <select value={filtroEstado} onChange={(e) => setFiltroEstado(e.target.value)}>
                            <option value="todos">Todos</option>
                            <option value="Pendiente">Pendientes</option>
                            <option value="En proceso">En proceso</option>
                            <option value="Resuelto">Resueltos</option>
                        </select>
                    </div>

                    <div className="nuevo-reporte">
                        <h3>Crear Nuevo Reporte</h3>
                        <form onSubmit={handleSubmitReporte}>
                            <div className="form-group">
                                <label>Área del destinatario:</label>
                                <select 
                                    name="tipoDestinatario" 
                                    value={nuevoReporte.tipoDestinatario}
                                    onChange={handleChange}
                                    required
                                >
                                    <option value="">Seleccionar área</option>
                                    {tiposUsuario.map(tipo => (
                                        <option key={tipo} value={tipo}>{tipo}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Destinatario:</label>
                                <select 
                                    name="destinatario" 
                                    value={nuevoReporte.destinatario}
                                    onChange={handleChange}
                                    required
                                    disabled={!nuevoReporte.tipoDestinatario}
                                >
                                    <option value="">Seleccionar destinatario</option>
                                    {usuarios.map(user => (
                                        <option key={user.ID_Usuario} value={user.ID_Usuario}>
                                            {user.nombre} {user.apellido} ({user.email})
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Descripción:</label>
                                <textarea
                                    name="descripcion"
                                    value={nuevoReporte.descripcion}
                                    onChange={handleChange}
                                    placeholder="Describe tu situación..."
                                    required
                                />
                            </div>
                            <button type="submit" className="btn-enviar">Enviar Reporte</button>
                        </form>
                    </div>

                    <div className="lista-reportes">
                    <h3>Mis Reportes</h3>
                    {reportesFiltrados.length === 0 ? (
                        <p>No hay reportes con este filtro.</p>
                    ) : (
                       <table className="tabla-reportes">
    <thead>
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Emisor</th>
            <th>Destinatario</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        {reportesFiltrados.map(reporte => (
            <tr key={reporte.ID_Reporte}>
                <td>{reporte.ID_Reporte}</td>
                <td>{new Date(reporte.fecha_hora).toLocaleString()}</td>
                <td>{reporte.descripcion}</td>
                <td>
                    <select 
                        value={reporte.estado}
                        onChange={(e) => handleActualizarEstado(reporte.ID_Reporte, e.target.value)}
                    >
                        <option value="Pendiente">Pendiente</option>
                        <option value="En proceso">En proceso</option>
                        <option value="Resuelto">Resuelto</option>
                    </select>
                </td>
                <td>
                    {reporte.emisor_nombre && reporte.emisor_apellido
                        ? `${reporte.emisor_nombre} ${reporte.emisor_apellido}`
                        : 'Sin nombre'}
                </td>
                <td>
                    {reporte.destinatario_nombre && reporte.destinatario_apellido
                        ? `${reporte.destinatario_nombre} ${reporte.destinatario_apellido}`
                        : 'Sin nombre'}
                </td>
                <td>
                    <button onClick={() => handleVerChat(reporte)}>Ver Chat</button>
                </td>
            </tr>
        ))}
    </tbody>
</table>
                    )}
                </div>

           
                </>
            )}
        </div>
    );
};

export default GestionReportes;
