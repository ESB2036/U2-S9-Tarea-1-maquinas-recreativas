import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import "../../css/modulo_usuario/main.css";

// Este componente React maneja el proceso de inicio de sesión de usuarios.
// Realiza validaciones básicas del formulario, envía una solicitud de autenticación al servidor y redirige al usuario según su tipo y estado.

// Excepción personalizada para credenciales incorrectas:
class CredencialesIncorrectasError extends Error {
  constructor(message = "¡Credenciales incorrectas OwO!") {
    super(message);
    this.name = "CredencialesIncorrectasError";
  }
}

export default function Login() {
  const [windowWidth, setWindowWidth] = useState(window.innerWidth);
  const navigate = useNavigate();
  const [error, setError] = useState("");
  const [formData, setFormData] = useState({
    usuario_asignado: "",
    contrasena: "",
  });
  const [loading, setLoading] = useState(false);
  const { setCurrentUser } = useAuth();

  useEffect(() => {
    const handleResize = () => {
      setWindowWidth(window.innerWidth);
    };

    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  {
    /**valida los campos del formulario, envía los datos al backend (/api/usuario/login), y según la respuesta:
     * Guarda los datos del usuario en localStorage.
     * Actualiza el contexto con setCurrentUser.
     * Redirige al dashboard correspondiente con navigate, según el tipo y estado del usuario (Tecnico, Logistica, Administrador del sistema, etc.). */
  }

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    // Validaciones iniciales
    if (!formData.usuario_asignado || formData.usuario_asignado.length > 15) {
      setError("Usuario inválido (máximo 15 caracteres)");
      setLoading(false);
      return;
    }

    if (!formData.contrasena) {
      setError("La contraseña no puede estar vacía");
      setLoading(false);
      return;
    }

    try {
      const response = await fetch("/api/usuario/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          usuario_asignado: formData.usuario_asignado.trim(),
          contrasena: formData.contrasena,
        }),
        credentials: "include",
      });

      const result = await response.json();

      if (!response.ok) {
        // Si el backend devuelve un mensaje específico para credenciales incorrectas:
        if (result.message === "Usuario o contraseña incorrectos") {
          throw new CredencialesIncorrectasError();
        }
        throw new Error(result.message || "Error al iniciar sesión");
      }

      // Manejar la respuesta cuando success: false viene con status 200:
      if (!result.success) {
        if (result.message === "Usuario o contraseña incorrectos") {
          throw new CredencialesIncorrectasError();
        }
        throw new Error(result.message || "Error desconocido");
      }

      // Si todo está bien, guardar usuario en localStorage y contexto:
      const userData = {
        ...result.usuario,
        ID_Usuario: result.usuario.ID_Usuario,
        fecha_inicio: result.fecha_inicio,
      };

      localStorage.setItem("user", JSON.stringify(userData));
      setCurrentUser(userData);

      // Verificar estado del usuario antes de redirigir:
      if (
        userData.estado === "Inhabilitado" ||
        userData.estado === "Pendiente de asignacion"
      ) {
        navigate("/acceso-restringido", {
          state: {
            userData,
            motivo:
              userData.estado === "Inhabilitado"
                ? "Su cuenta ha sido inhabilitada por los administradores"
                : "Su cuenta está pendiente de asignación de área",
          },
        });
      } else {
        redirectUser(userData);
      }
    } catch (err) {
      // Manejo específico para la excepción personalizada:
      if (err instanceof CredencialesIncorrectasError) {
        setError(err.message);
      } else {
        setError(err.message || "Error de conexión con el servidor");
      }
      console.error("Login error:", err);
    } finally {
      setLoading(false);
    }
  };

  const redirectUser = (userData) => {
    const userType = userData.tipo === "Técnico" ? "Tecnico" : userData.tipo;

    switch (userType) {
      case "Logistica":
        navigate("/dashboard/logistica", {
          state: { userId: userData.ID_Usuario },
        });
        break;
      case "Tecnico":
        if (userData.Especialidad) {
          const path = `/dashboard/${userData.Especialidad.toLowerCase()}`;
          navigate(path, { state: { userId: userData.ID_Usuario } });
        } else {
          navigate("/dashboard/tecnico", {
            state: { userId: userData.ID_Usuario },
          }); // fallback
        }
        break;
      case "Contabilidad":
        navigate("/contabilidad", {
          state: { userId: userData.ID_Usuario },
        });
        break;
      case "Administrador":
        navigate("/dashboard/admin", {
          state: {
            userId: userData.ID_Usuario,
            userData: userData,
          },
        });
        break;
      default:
        navigate("/", { state: { userId: userData.ID_Usuario } });
    }
  };

  // Redirige a la página de registro (/register):
  const handleWorkWithUs = () => {
    navigate("/register");
  };

  return (
    <div className="login-page">
      <header>
        <h1>Bienvenido</h1>
        <nav>
          <a href="/">Iniciar sesión</a>
          <button onClick={handleWorkWithUs}>
            ¿QUIERES TRABAJAR CON NOSOTROS?
          </button>
        </nav>
      </header>

      <main className="pantalla_completa">
        <div className="contenedor_todo">
          {windowWidth > 850 && (
            <div className="caja_trasera">
              <div className="caja_trasera_login">
                <h3>¿Ya tienes una cuenta?</h3>
                <p>Inicia sesión para entrar en la página</p>
              </div>
            </div>
          )}

          <div className="contenedor_login_register">
            <form onSubmit={handleSubmit} className="formulario_login">
              <h2>Iniciar Sesión</h2>
              {error && <div className="error-message">{error}</div>}
              <input
                type="text"
                name="usuario_asignado"
                placeholder="Usuario asignado"
                value={formData.usuario_asignado}
                onChange={handleChange}
                required
              />
              <input
                type="password"
                name="contrasena"
                placeholder="Contraseña"
                value={formData.contrasena}
                onChange={handleChange}
                required
              />
              <div className="enlaces_recuperacion">
                <a href="/usuario/recuperar-contrasena">
                  ¿Olvidaste tu contraseña?
                </a>
                <a href="/usuario/recuperar-usuario">¿Olvidaste tu usuario?</a>
              </div>
              <button type="submit" disabled={loading}>
                {loading ? "Verificando..." : "Entrar"}
              </button>
            </form>
          </div>
        </div>
      </main>
    </div>
  );
}
