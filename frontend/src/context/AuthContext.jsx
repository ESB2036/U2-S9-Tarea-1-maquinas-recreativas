import { createContext, useContext, useState, useEffect } from 'react';
{/**El archivo AuthContext.jsx proporciona un contexto de autenticación global para la aplicación, permitiendo la gestión del estado del usuario autenticado en todo el sistema. 
    Usa el useState y useEffect para manejar el estado de autenticación y recuperar los datos del usuario desde el almacenamiento local al cargar la aplicación. Además, incluye funciones para iniciar sesión (login), 
    cerrar sesión (logout), y pasar la información de autenticación a los componentes a través del contexto (AuthContext.Provider). */}
const AuthContext = createContext();

export function AuthProvider({ children }) {
    const [currentUser, setCurrentUser] = useState(null);
    const [loading, setLoading] = useState(true);
//Autentica al usuario usando las credenciales proporcionadas.
    const login = async (credentials) => {
        try {
            const response = await fetch('/api/usuario/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(credentials)
            });
            
            const data = await response.json();
            
            if (data.success) {
                const userData = {
                    ...data.usuario,
                    ID_Usuario: data.usuario.ID_Usuario || data.usuario.id,
                    // Asegurar que los técnicos tengan el tipo correcto
                    tipo: data.usuario.tipo === 'Tecnico' ? 'Técnico' : data.usuario.tipo
                };
                localStorage.setItem('user', JSON.stringify(userData));
                setCurrentUser(userData);
                return { success: true };
            } else {
                return { success: false, message: data.message };
            }
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, message: 'Error de conexión' };
        }
    };
//Cierra la sesión del usuario.
    const logout = async () => {
      try {
          const response = await fetch('/api/usuario/logout', { 
              method: 'POST',
              credentials: 'include'
          });
          
          const data = await response.json();
          
          if (data.success) {
              localStorage.removeItem('user');
              setCurrentUser(null);
              return { success: true };
          } else {
              return { success: false, message: data.message };
          }
      } catch (error) {
          console.error('Logout error:', error);
          return { success: false, message: 'Error al cerrar sesión' };
      }
  };
  {/**  Devuelve el valor del contexto (currentUser, setCurrentUser, login, logout), permitiendo que los componentes hijos accedan a la información del usuario y las funciones de autenticación.*/}
    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('user'));
        if (user) {
            setCurrentUser({
                ...user,
                ID_Usuario: user.ID_Usuario || user.id
            });
        }
        setLoading(false);
    }, []);

    const value = {
        currentUser,
        setCurrentUser,
        login,
        logout
    };

    return (
        <AuthContext.Provider value={value}>
            {!loading && children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth debe usarse dentro de un AuthProvider');
    }
    return context;
}