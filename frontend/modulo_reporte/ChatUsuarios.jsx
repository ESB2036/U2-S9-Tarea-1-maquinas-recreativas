import React, { useState, useEffect } from 'react';
import { useAuth } from '../src/context/AuthContext';
import { useNavigate, useParams, useLocation } from 'react-router-dom';
import '../css/modulo_reporte/chatUsuarios.css';
//Este componente es una interfaz de chat entre usuarios dentro de un sistema de reportes. Permite visualizar conversaciones previas (comentarios asociados a reportes) y enviar nuevos mensajes. 
// Está diseñado para adaptarse a dos modos de uso: como ventana completa o como panel lateral, dependiendo del prop asPanel.
const ChatUsuarios = ({ currentUser, asPanel = false }) => {
    //const { reporteId } = useParams();
    const { reporteId, emisorId, destinatarioId } = useParams();

    const location = useLocation();
    const queryParams = new URLSearchParams(location.search);
    const currentUserId = queryParams.get('currentUserId');
    //Usuario con quien se está chateando.
    const [destinatario, setDestinatario] = useState(null);
    //Lista de mensajes (comentarios) en el chat.
    const [comentarios, setComentarios] = useState([]);
    //Texto del mensaje que se está escribiendo
    const [nuevoComentario, setNuevoComentario] = useState('');
    //Indicador de carga mientras se obtienen los datos.
    const [loading, setLoading] = useState(true);
    //Mensaje de error en caso de fallos.
    const [error, setError] = useState('');
    //Lista de usuarios con los que hay chats abiertos.
    const [usuariosChat, setUsuariosChat] = useState([]);
    //Reporte actualmente seleccionado para el chat.
    const [selectedReporte, setSelectedReporte] = useState(null);
    // Lista de reportes entre los usuarios.
    const [reportes, setReportes] = useState([]);
    const navigate = useNavigate();

    // Determinar el ID de usuario actual
    const userId = currentUser?.ID_Usuario || currentUserId;

    // Verificar autenticación
    useEffect(() => {
        if (!userId) {
            navigate('/login');
        }
    }, [userId, navigate]);
{/**Carga la lista de usuarios con quienes el actual tiene chats, 
    y si se accedió desde un reporteId, carga el reporte y sus comentarios.
 */}
    useEffect(() => {
        const cargarDatosIniciales = async () => {
            try {
                setLoading(true);
                
                // Cargar usuarios con conversaciones
                const resUsuarios = await fetch(`/api/reportes/usuarios-chat?userId=${userId}`);
                const usuariosData = await resUsuarios.json();
                
                if (!usuariosData.success) {
                    throw new Error(usuariosData.message || 'Error al cargar usuarios');
                }
                
                setUsuariosChat(usuariosData.usuarios);

                // Si viene de un reporte específico
                if (reporteId) {
                    const resReporte = await fetch(`/api/reportes/${reporteId}`);
                    const reporteData = await resReporte.json();
                    
                    if (reporteData.success) {
                        const reporte = reporteData.reporte;
                        setSelectedReporte(reporte);
                        
                        // Determinar destinatario
                        const destId = reporte.ID_Usuario_Emisor === userId 
                            ? reporte.ID_Usuario_Destinatario 
                            : reporte.ID_Usuario_Emisor;
                        
                        const usuarioDest = usuariosData.usuarios.find(u => u.ID_Usuario == destId);
                        if (usuarioDest) {
                            setDestinatario(usuarioDest);
                            await cargarComentariosReporte(reporteId);
                        }
                    }
                }
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };

        if (userId) {
            cargarDatosIniciales();
        }
    }, [userId, reporteId]);
//Recupera los comentarios asociados a un reporte específico.
    const cargarComentariosReporte = async (idReporte) => {
        try {
            const res = await fetch(`/api/comentarios/reporte/${idReporte}`);
            const data = await res.json();
            
            if (data.success) {
                setComentarios(data.data || []);
            } else {
                setComentarios([]);
            }
        } catch (err) {
            console.error('Error al cargar comentarios:', err);
            setComentarios([]);
        }
    };
//Recupera todos los reportes del usuario autenticado.
    const cargarReportesUsuario = async (userId) => {
        try {
            const res = await fetch(`/api/reportes/usuario/${userId}`);
            const data = await res.json();
            
            if (data.success) {
                setReportes(data.reportes);
            }
        } catch (err) {
            console.error('Error al cargar reportes:', err);
        }
    };
//Envía un nuevo comentario al servidor. Si no existe un reporte entre los usuarios, lo crea automáticamente antes de enviar el mensaje. También guarda la acción en el historial.
    const handleEnviarComentario = async (e) => {
        e.preventDefault();
        await fetch('/api/historial-actividades', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                descripcion: `El usuario tuvo una conversación`
            })
        });
        if (!nuevoComentario.trim()) return;

        try {
            let idReporte = selectedReporte?.ID_Reporte;
            
            if (!idReporte && destinatario) {
                const res = await fetch('/api/reportes/crear', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ID_Usuario_Emisor: userId,
                        ID_Usuario_Destinatario: destinatario.ID_Usuario,
                        descripcion: `Chat con ${destinatario.nombre} ${destinatario.apellido}`
                    })
                });
                
                const data = await res.json();
                if (data.success) {
                    idReporte = data.reporteId;
                    setSelectedReporte({ ID_Reporte: idReporte });
                    await cargarReportesUsuario(userId);
                }
            }

            const resComentario = await fetch('/api/comentarios', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ID_Reporte: idReporte,
                    ID_Usuario_Emisor: userId,
                    comentario: nuevoComentario
                })
            });
            
            const comentarioData = await resComentario.json();
            
            if (comentarioData.success) {
                await cargarComentariosReporte(idReporte);
                setNuevoComentario('');
            }
        } catch (err) {
            setError(err.message);
        }
    };
//Cuando el usuario selecciona un contacto, se carga el historial de reportes y comentarios con ese usuario.
    const handleSeleccionarUsuario = async (usuario) => {
        try {
            setDestinatario(usuario);
            setLoading(true);
            
            // Cargar reportes entre estos usuarios
            const res = await fetch(
                `/api/reportes/chat/${userId}/${usuario.ID_Usuario}`
            );
            const data = await res.json();
            
            if (data.success && data.reportes.length > 0) {
                setReportes(data.reportes);
                // Seleccionar el reporte más reciente
                setSelectedReporte(data.reportes[0]);
                await cargarComentariosReporte(data.reportes[0].ID_Reporte);
            } else {
                setComentarios([]);
                setSelectedReporte(null);
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };
//Permite seleccionar un reporte específico de la lista para mostrar sus comentarios.
    const handleSeleccionarReporte = async (reporte) => {
        setSelectedReporte(reporte);
        await cargarComentariosReporte(reporte.ID_Reporte);
    };

    if (loading) return <div className="loading">Cargando chat...</div>;
    if (error) return <div className="error">{error}</div>;
{/**
    chat-sidebar: Lista de usuarios con los que se puede conversar.
chat-main: Muestra los mensajes, el historial de reportes y un formulario para enviar nuevos comentarios.
chat-header, chat-mensajes, y chat-form: Subcomponentes visuales internos del chat.
    */}
    return (
        <div className={`chat-container ${asPanel ? 'panel-mode' : 'window-mode'}`}>
            <div className="chat-sidebar">
                <h3>Conversaciones</h3>
                <button onClick={() => navigate(-1)}>Cerrar chat</button>
                <ul className="usuarios-list">
                    {usuariosChat.map((usuario) => {
                        const esSeleccionado = destinatario?.ID_Usuario === usuario.ID_Usuario;
                        return (
                            <li
                            key={usuario.ID_Usuario}
                            onClick={() => handleSeleccionarUsuario(usuario)}
                            className={`usuario-chat-item ${esSeleccionado ? 'activo' : ''}`}
                            style={{ cursor: 'pointer' , color: 'black'}}
                            >
                            <div className="usuario-info">
                                <strong>{usuario.nombre} {usuario.apellido}</strong> {usuario.tipo}, [{usuario.email}]
                            </div>
                            </li>

                        );
                    })}
                </ul>
            </div>

            <div className="chat-main">
                {destinatario ? (
                    <>
                        <div className="chat-header">
                            <h3>Chat con {destinatario.nombre} {destinatario.apellido}</h3>
                            <small>{destinatario.email} - {destinatario.tipo}</small>
                        </div>

                        <div className="reportes-list">
                            <select 
                                value={selectedReporte?.ID_Reporte || ''}
                                onChange={(e) => {
                                    const reporte = reportes.find(r => r.ID_Reporte == e.target.value);
                                    if (reporte) handleSeleccionarReporte(reporte);
                                }}
                            >
                                <option value="">Seleccionar reporte</option>
                                {reportes.map(reporte => (
                                    <option key={reporte.ID_Reporte} value={reporte.ID_Reporte}>
                                        Reporte #{reporte.ID_Reporte} - {reporte.estado}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="chat-mensajes">
    {comentarios.length > 0 ? (
        comentarios.map((comentario) => (
            <div key={comentario.ID_Comentario} className={`comentario-item ${comentario.ID_Usuario_Emisor === userId ? 'emisor' : 'receptor'}`}>
                <p><strong>{comentario.nombre} {comentario.apellido}</strong>: {comentario.comentario}</p>
                <small>{new Date(comentario.fecha_hora).toLocaleString()}</small>
            </div>
        ))
    ) : (
        <p>No hay comentarios aún.</p>
    )}
</div>

<form className="chat-form" onSubmit={handleEnviarComentario}>
    <textarea
        value={nuevoComentario}
        onChange={(e) => setNuevoComentario(e.target.value)}
        placeholder="Escribe un comentario..."
        required
    />
    <button type="submit">Enviar</button>
</form>

                    </>
                ) : (
                    <div className="no-chat-selected">
                        <p>Selecciona un usuario para chatear</p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ChatUsuarios;