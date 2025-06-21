import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
//Este componente permite registrar un nuevo usuario a través de un formulario. 
// Recoge los datos del formulario, los valida y los envía al servidor para crear un nuevo registro.
export default function RegistrarUsuario() {
    const [formData, setFormData] = useState({
        nombre: '',
        apellido: '',
        ci: '',
        email: '',
        usuario_asignado: '',
        contrasena: '',
        tipo: 'Logistica',
        especialidad: ''
    });
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);
    // Permite redirigir al usuario al inicio tras un registro exitoso.
    const navigate = useNavigate();
// Actualiza los valores del formulario a medida que el usuario escribe.
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };
    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!window.confirm('Seguro desea registrarse?')) {
            return;
        }
        try {
            const payload = {
                nombre: formData.nombre,
                apellido: formData.apellido,
                ci: formData.ci,
                email: formData.email,
                usuario_asignado: formData.usuario_asignado,
                contrasena: formData.contrasena,
                tipo: formData.tipo
            };

            if (formData.tipo === 'Tecnico') {
                payload.especialidad = formData.especialidad;
            }

            const response = await fetch('/api/usuario/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(true);
                setTimeout(() => navigate('/'), 2000);
            } else {
                setError(data.message || 'Error al registrar usuario');
            }
        } catch (err) {
            setError('Error de conexión con el servidor');
        }
    };

    return (
        <div className="register-container">
            <h2>Registro de Usuario</h2>
            
            {error && <div className="error-message">{error}</div>}
            {success && (
                <div className="success-message">
                    <p style={{ color: '#56e335', marginBottom: '5px' }}>
                        ¡Registro exitoso! Redirigiendo...
                    </p>
                </div>
            )}

            <form onSubmit={handleSubmit}>
                <div className="form-group">
                    <label>Nombre: </label>
                    <input
                        type="text"
                        name="nombre"
                        value={formData.nombre}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div className="form-group">
                    <label>Apellido: </label>
                    <input
                        type="text"
                        name="apellido"
                        value={formData.apellido}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div className="form-group">
                    <label>Cédula: </label>
                    <input
                        type="text"
                        name="ci"
                        value={formData.ci}
                        onChange={handleChange}
                        required
                        maxLength="10"
                    />
                </div>

                <div className="form-group">
                    <label>Email: </label>
                    <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div className="form-group">
                    <label>Usuario: </label>
                    <input
                        type="text"
                        name="usuario_asignado"
                        value={formData.usuario_asignado}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div className="form-group">
                    <label>Contraseña: </label>
                    <input
                        type="password"
                        name="contrasena"
                        value={formData.contrasena}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div className="form-group">
                <label>Tipo de Usuario: </label>
                <select
                    name="tipo"
                    value={formData.tipo}
                    onChange={handleChange}
                    required
                >
                    <option value="Logistica">Logística</option>
                    <option value="Tecnico">Técnico</option>
                    <option value="Contabilidad">Área de Contabilidad</option>
                    <option value="Administrador">Administrador del sistema</option>
                </select>
                </div>

                {formData.tipo === 'Tecnico' && (
                    <div className="form-group">
                        <label>Especialidad: </label>
                        <select
                            name="especialidad"
                            value={formData.especialidad}
                            onChange={handleChange}
                            required
                        >
                            <option value="" disabled>Seleccione...</option>
                            <option value="Ensamblador">Ensamblador</option>
                            <option value="Comprobador">Comprobador</option>
                            <option value="Mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                )}

                <button type="submit" className="submit-btn">
                    Registrar
                </button>
            </form>

            <br />
            <div className="auth-links">
                <Link to="/">Ir a inicio de sesión</Link>
            </div>
        </div>
    );
}