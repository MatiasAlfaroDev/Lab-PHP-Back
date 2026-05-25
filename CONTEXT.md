# Plataforma de Gestión de Servicios Profesionales

## Contexto académico
Proyecto de laboratorio — UTEC Tecnólogo Informático, Desarrollo de Aplicaciones Web con PHP.  
Entrega análisis/prototipo: **11 de mayo**. Entrega final: **21 de junio**. Demo: **25 de junio**.

---

## Visión
Plataforma multi-profesional para gestión de agenda, reservas, pagos y videollamadas.
Permite a profesionales independientes publicar servicios y a clientes reservar turnos,
comprar paquetes de sesiones y calificar los servicios recibidos.

---

## Stack tecnológico (planificado)
- **Backend:** PHP con Laravel + API REST
- **Frontend:** por definir (puede ser separado con React/Vue para +4 pts electivos)
- **Base de datos:** MySQL/PostgreSQL (relacional principal)
- **WebSockets:** Laravel Echo / Pusher / Socket.io
- **Videollamadas:** LiveKit Cloud o WebRTC
- **Colas:** Redis + Laravel Jobs
- **Mapas:** Google Maps o Leaflet
- **Emails:** notificaciones automáticas (SMTP)

---

## Modelo Relacional (MR)

```
USUARIO(usuarioId, nombre, email, contraseña, rol, telefono, avatarUrl, creadoEn)
  PK {usuarioId}

PROFESIONAL(usuarioId, descripcion, ubicacion, calificacionPromedio)
  PK {usuarioId}
  FK {usuarioId} → USUARIO

CLIENTE(usuarioId)
  PK {usuarioId}
  FK {usuarioId} → USUARIO

SERVICIO(servicioId, profesionalId, nombre, descripcion, tipo, precio,
         duracion, buffer, modalidad, minCancelacion, activo)
  PK {servicioId}
  FK {profesionalId} → PROFESIONAL

DISPONIBILIDAD(disponibilidadId, servicioId, diaSemana, horaInicio, horaFin)
  PK {disponibilidadId}
  FK {servicioId} → SERVICIO

EXCEPCION(excepcionId, disponibilidadId, fecha, motivo, tipoExcepcion)
  PK {excepcionId}
  FK {disponibilidadId} → DISPONIBILIDAD

PAQUETE(paqueteId, servicioId, cantidadSesiones, precio, descripcion)
  PK {paqueteId}
  FK {servicioId} → SERVICIO

COMPRA_PAQUETE(compraId, clienteId, paqueteId, sesionesRestantes, fechaCompra, estado)
  PK {compraId}
  FK {clienteId} → CLIENTE
  FK {paqueteId} → PAQUETE

RESERVA(reservaId, clienteId, servicioId, compraPaqueteId?, fecha,
        horaInicio, horaFin, estado, urlVideollamada?)
  PK {reservaId}
  FK {clienteId} → CLIENTE
  FK {servicioId} → SERVICIO
  FK {compraPaqueteId} → COMPRA_PAQUETE  [nullable]

PAGO(pagoId, reservaId?, compraPaqueteId?, monto, fecha, estado, metodoPago)
  PK {pagoId}
  FK {reservaId} → RESERVA           [nullable]
  FK {compraPaqueteId} → COMPRA_PAQUETE  [nullable]

CALIFICACION(calificacionId, reservaId, clienteId, puntuacion, comentario, fecha)
  PK {calificacionId}
  FK {reservaId} → RESERVA
  FK {clienteId} → CLIENTE

NOTIFICACION(notificacionId, usuarioId, tipo, mensaje, leida, creadaEn)
  PK {notificacionId}
  FK {usuarioId} → USUARIO
```

---

## Enums

| Enum | Valores |
|------|---------|
| `EstadoReserva` | pendiente, confirmada, pagada, en_curso, finalizada, cancelada, no_asistida |
| `EstadoPago` | pendiente, aprobado, rechazado, cancelado, fallido |
| `TipoModalidad` | presencial, remota, hibrida |
| `TipoExcepcion` | no_disponible, feriado, licencia |
| `DiaSemana` | lunes, martes, miercoles, jueves, viernes, sabado, domingo |
| `Rol` | admin, profesional, cliente |

---

## Reglas de negocio clave

- `RESERVA.compraPaqueteId` es **nullable** — una reserva puede ser individual o consumir una sesión de paquete.
- `PAGO.reservaId` y `PAGO.compraPaqueteId` son **mutuamente excluyentes** — nunca ambos con valor.
- Las transiciones de `EstadoReserva` deben seguir una **máquina de estados estricta** (no se pueden saltar estados).
- `buffer` y `minCancelacion` viven en `SERVICIO`, no en `DISPONIBILIDAD`.
- `EXCEPCION` es entidad separada (no campo JSON) para permitir consultas y filtros por fecha.
- `calificacionPromedio` en `PROFESIONAL` se recalcula al agregar cada `CALIFICACION`.
- Un `PAQUETE` pertenece a un `SERVICIO` específico, no al profesional en general.
- Al confirmar una reserva remota, el sistema debe generar y persistir la `urlVideollamada`.

---

## Máquina de estados — Reserva

```
pendiente → confirmada → pagada → en_curso → finalizada
    ↓            ↓          ↓
cancelada    cancelada   cancelada
                              ↓
                          no_asistida
```

---

## Casos de uso principales

| # | Caso de uso | Actor |
|---|-------------|-------|
| 1 | Registro y login (con roles) | Todos |
| 2 | Gestión de perfil profesional | Profesional |
| 3 | Configuración de disponibilidad (horarios + excepciones) | Profesional |
| 4 | Búsqueda y filtrado de servicios | Cliente |
| 5 | Reserva de turno | Cliente |
| 6 | Gestión de reservas (confirmar, cancelar, reprogramar) | Cliente / Profesional |
| 7 | Gestión de paquetes (crear, comprar, consumir sesión) | Profesional / Cliente |
| 8 | Pagos (reserva individual o compra de paquete) | Cliente |
| 9 | Videollamadas (generación de URL al confirmar reserva remota) | Sistema |
| 10 | Calificaciones y reputación | Cliente |
| 11 | Notificaciones automáticas (email + tiempo real) | Sistema |
| 12 | Panel administrativo | Admin |

---

## DSS modelados

- Alta de usuario (cliente y profesional) — con fragmentos ALT y OPT
- Listar servicios de un profesional
- Filtrar profesionales
- Reserva de turno

## DSS pendientes de modelar

- Login / Autenticación con roles
- Configuración de disponibilidad (horarios + excepciones)
- Confirmar / Cancelar / Reprogramar reserva
- Gestión de paquetes (crear, comprar, consumir sesión)
- Realizar pago (reserva individual o paquete)
- Calificar servicio (post reserva finalizada)
- Notificaciones automáticas
- Panel administrativo
- Generación de videollamada

---

## Requerimientos electivos apuntados

| Requerimiento | Puntos |
|---------------|--------|
| Arquitectura desacoplada frontend/backend (React/Vue) | 4 |
| CI/CD con GitHub Actions | 3 |
| Colas async con Redis (emails, notificaciones) | 3 |
| Control de concurrencia en reservas | 3 |
| Recordatorios automáticos de turnos | 2 |
| Dockerización | 2 |
| Pasarela de pago — PayPal sandbox | 2 |
| Autenticación OAuth con Google | 2 |
| **Total** | **21** |

---

## Notas para Claude Code

- El proyecto usa **herencia de tabla por clase** (TPH) para Usuario→Profesional/Cliente.
- Todas las rutas de la API deben respetar el rol del usuario autenticado.
- Las reservas deben tener **control de concurrencia** (bloqueo pesimista o transacciones) para evitar doble reserva del mismo horario.
- Los emails y notificaciones push deben ir siempre por **jobs en cola**, nunca síncronos.
- Para videollamadas, generar el token/URL de LiveKit al momento de confirmar la reserva.
