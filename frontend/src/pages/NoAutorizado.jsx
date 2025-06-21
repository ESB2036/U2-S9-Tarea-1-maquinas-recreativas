import { Link } from 'react-router-dom';
//Este componente se muestra cuando un usuario intenta acceder a una página para la cual no tiene permisos (error 403).
export default function NoAutorizado() {
  return (
    <div style={{ textAlign: 'center', padding: '50px' }}>
      <h1>403 - No Autorizado</h1>
      <p>No tienes permisos para acceder a esta página.</p>
      <Link to="/">Volver al inicio</Link>
    </div>
  );
}