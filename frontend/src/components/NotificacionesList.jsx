import { useState, useEffect } from "react";
//Muestra una lista de notificaciones, indicando cuántas están sin leer. Permite marcar como leídas las notificaciones haciendo clic sobre ellas. Se puede expandir o colapsar la vista.
export default function NotificacionesList({
  notificaciones,
  mostrarNotificaciones,
  setMostrarNotificaciones,
  emptyMessage = "No hay notificaciones...",
  user,
}) {
  const [noLeidas, setNoLeidas] = useState(0);

useEffect(() => {
  const fetchNoLeidas = async () => {
    if (!user || !user.ID_Usuario) return;

    try {
      const response = await fetch(`/api/notificaciones/no-leidas/${user.ID_Usuario}`);

      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        const text = await response.text(); // Captura para debug
        console.error("Respuesta inesperada (no es JSON):", text);
        throw new Error("Respuesta no válida del servidor (no es JSON)");
      }

      const data = await response.json();
      if (data.success) {
        setNoLeidas(parseInt(data.total, 10)); // Asegura número
      }
    } catch (error) {
      console.error("Error fetching unread notifications:", error);
    }
  };

  fetchNoLeidas();
}, [user?.ID_Usuario, notificaciones]);

  const handleMarcarLeida = async (id) => {
    try {
      await fetch("/api/notificaciones/marcar-leida", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idNotificacion: id }),
      });
      setNoLeidas((prev) => (prev > 0 ? prev - 1 : 0));
    } catch (error) {
      console.error("Error marking notification as read:", error);
    }
  };

  // Render condicional si user no está disponible:
  if (!user) return null;

  return (
    <section className="notifications-section">
      <h2 onClick={() => setMostrarNotificaciones(!mostrarNotificaciones)}>
        Notificaciones {noLeidas > 0 && `(${noLeidas})`}
        {mostrarNotificaciones ? "🔽" : "▶️"}
      </h2>
      {mostrarNotificaciones &&
        (notificaciones.length > 0 ? (
          <ul>
            {notificaciones.map((notif) => (
              <li
                key={notif.ID_Notificacion}
                className={`notificacion-item ${
                  notif.Estado === "No leido" ? "no-leida" : ""
                }`}
                onClick={() =>
                  notif.Estado === "No leido" &&
                  handleMarcarLeida(notif.ID_Notificacion)
                }
              >
                <p>
                  <strong>{notif.Tipo}</strong> -{" "}
                  {new Date(notif.Fecha).toLocaleString()}
                  {notif.Mensaje && (
                    <>
                      <br />
                      <span className="notificacion-content">
                        {notif.Mensaje}
                      </span>
                    </>
                  )}
                  <br />
                  <span className="notificacion-content">
                    <strong>Máquina recreativa: </strong> {notif.Nombre_Maquina}
                  </span>
                  <span className="notificacion-content">
                    <strong>Comercio: </strong> {notif.NombreComercio}
                  </span>
                  <span className="notificacion-content">
                    <strong>Dirección: </strong> {notif.DireccionComercio}
                  </span>
                </p>
              </li>
            ))}
          </ul>
        ) : (
          <p>{emptyMessage}</p>
        ))}
    </section>
  );
}
