import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../src/context/AuthContext';
import '../css/modulo_administrador/mainAdministrador.css';
import NotificacionesPanel from '../modulo_reporte/NotificacionesPanel';
import GestionReportes from '../modulo_reporte/GestionReportes';
import ChatUsuarios from '../modulo_reporte/ChatUsuarios';

export function AdminHeader({ showReportesButton = true, showChatButton = true, showNotificacionesButton = true }) {
  const { currentUser, logout } = useAuth();
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [showReportes, setShowReportes] = useState(false);
  const [showChat, setShowChat] = useState(false);
  const [showNotificaciones, setShowNotificaciones] = useState(false);
  const navigate = useNavigate();

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
        localStorage.removeItem('user');
        navigate('/');
      } else {
        console.error(result.message || 'Error al cerrar sesión');
      }
    } catch (err) {
      console.error('Error al cerrar sesión:', err);
    }
  };

  return (
    <header className="admin-header">
      <h1>Bienvenido</h1>
      
      <div className="header-controls">
        {showReportesButton && (
          <button
            onClick={() => {
              setShowReportes(!showReportes);
              setShowChat(false);
              setShowNotificaciones(false);
            }}
          >
            {showReportes ? 'Ocultar Reportes' : 'Mostrar Reportes'}
          </button>
        )}
        
        {showNotificacionesButton && (
          <button
            onClick={() => {
              setShowNotificaciones(!showNotificaciones);
              setShowReportes(false);
              setShowChat(false);
            }}
          >
            {showNotificaciones ? 'Ocultar Notificaciones' : 'Mostrar Notificaciones'}
          </button>
        )}
        
        {showChatButton && (
          <button
            onClick={() => {
              setShowChat(!showChat);
              setShowReportes(false);
              setShowNotificaciones(false);
            }}
          >
            {showChat ? 'Ocultar Chat' : 'Mostrar Chat'}
          </button>
        )}
      </div>

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
              <button onClick={handleLogout} className="logout-button">Cerrar Sesión</button>
            </div>
          )}
        </div>
      )}

      {/* Paneles desplegables */}
      {showNotificaciones && <NotificacionesPanel currentUser={currentUser} />}
      {showReportes && <GestionReportes currentUser={currentUser} />}
      {showChat && <ChatUsuarios currentUser={currentUser} asPanel={true} />}
    </header>
  );
}