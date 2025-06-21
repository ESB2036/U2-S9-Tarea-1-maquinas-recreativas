import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../src/context/AuthContext';
import '../css/modulo_administrador/mainAdministrador.css';
import GestionReportes from '../modulo_reporte/GestionReportes';
import ChatUsuarios from '../modulo_reporte/ChatUsuarios';
import NotificacionesPanel from '../modulo_reporte/NotificacionesPanel';
//Este componente muestra el panel principal del administrador. Carga y presenta información de usuarios, permite cerrar sesión y acceder a distintas secciones como gestión de usuarios, reportes, notificaciones y chat.
export default function Administrador() {
  const [usuarios, setUsuarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [showReportes, setShowReportes] = useState(false);
  const [showChat, setShowChat] = useState(false);
  const [showNotificaciones, setShowNotificaciones] = useState(false);
// Hook personalizado para acceder al usuario autenticado (currentUser) y a la función de cierre de sesión (logout).
  const { currentUser, logout } = useAuth();
  //Permite redirigir al usuario a otras rutas dentro del sistema.
  const navigate = useNavigate();
// Llama a una API para obtener la lista de usuarios al cargar el componente
  useEffect(() => {
    const fetchUsuarios = async () => {
      try {
        const response = await fetch('/api/administrador/usuarios');
        if (!response.ok) throw new Error('Error al cargar usuarios');
        const data = await response.json();
        if (data.success) {
          setUsuarios(data.usuarios);
        } else {
          setError(data.message || 'Error al cargar usuarios');
        }
      } catch (err) {
        setError(err.message || 'Error de conexión con el servidor');
      } finally {
        setLoading(false);
      }
    };

    fetchUsuarios();
  }, []);
//Cierra sesión, limpia el almacenamiento local (localStorage) y redirige al usuario al inicio.
  const handleLogout = async () => {
    try {
      const user = JSON.parse(localStorage.getItem('user'));
      if (!user || !user.ID_Usuario) {
        navigate('/');
        return;
      }

      const response = await fetch('/api/usuario/logout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ID_Usuario: user.ID_Usuario })
      });

      const result = await response.json();

      if (result.success) {
        // Limpiar localStorage y redirigir
        localStorage.removeItem('user');
        navigate('/');
      } else {
        setError(result.message || 'Error al cerrar sesión');
      }
    } catch (err) {
      console.error('Error al cerrar sesión:', err);
      setError('Error de conexión al cerrar sesión');
    }
  };

  if (loading) return <div className="loading">Cargando panel de administrador...</div>;
  if (error)   return <div className="error">{error}</div>;
{/** Gestión de reportes: Muestra reportes administrativos
  ChatUsuarios: Panel de chat entre usuarios.
NotificacionesPanel: Panel para mostrar notificaciones del sistema.
*Controles principales:
Botón para acceder a gestión de usuarios.
Botones para mostrar u ocultar los paneles de reportes, notificaciones y chat.
Menú de perfil del usuario actual con opciones para ver, editar perfil o cerrar sesión.
  */}
  return (
    <div className="dashboard-container">
      <header className="admin-header">
        <h1>Panel de Administración</h1>
        {currentUser && (
          <div className="profile-section">
            <button
              className="profile-button"
              onClick={() => setShowProfileMenu(!showProfileMenu)}
            >
              <span>{currentUser.usuario_asignado}</span>
              <span className="profile-arrow">▼</span>
            </button>
            {showProfileMenu && (
              <div className="profile-dropdown">
                <button
                  className="dropdown-item"
                  onClick={() => navigate(`/usuario/perfil?id=${currentUser.ID_Usuario}`)}
                >
                  Ver Perfil
                </button>
                <button
                  className="dropdown-item"
                  onClick={() => navigate(`/usuario/actualizar-perfil?id=${currentUser.ID_Usuario}`)}
                >
                  Editar Perfil
                </button>
                <button
                  className="dropdown-item logout"
                  onClick={handleLogout}
                >
                  Cerrar Sesión
                </button>
              </div>
            )}
          </div>
        )}
      </header>

      <div className="header-controls">
        <button onClick={() => navigate('/admin/gestion-usuarios')}>
          Gestión de Usuarios
        </button>

        <button
          onClick={() => {
            setShowReportes(!showReportes);
            setShowChat(false);
            setShowNotificaciones(false);
          }}
        >
          {showReportes ? 'Ocultar Reportes' : 'Mostrar Reportes'}
        </button>
        <button
          onClick={() => {
            setShowNotificaciones(!showNotificaciones);
            setShowReportes(false);
            setShowChat(false);
          }}
        >
          {showNotificaciones ? 'Ocultar Notificaciones' : 'Mostrar Notificaciones'}
        </button>
        <button
          onClick={() => {
            setShowChat(!showChat);
            setShowReportes(false);
            setShowNotificaciones(false);
          }}
        >
          {showChat ? 'Ocultar Chat' : 'Mostrar Chat'}
        </button>
      </div>

      <div className="admin-content">
        {showNotificaciones && <NotificacionesPanel currentUser={currentUser} />}
        {showReportes     && <GestionReportes    currentUser={currentUser} />}
        {showChat && <ChatUsuarios currentUser={currentUser} asPanel={true} />}
        </div>
    </div>
  );
}
