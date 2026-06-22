# CitaPro - Backend (Laravel)

## Descripción del sistema

CitaPro es una plataforma web para la gestión integral de reservas de servicios profesionales. Permite la conexión entre clientes y profesionales, la administración de servicios, reservas, pagos y notificaciones en tiempo real.

El backend está desarrollado en Laravel y funciona como una API REST desacoplada consumida por un frontend en React.

---

## Objetivo del backend

El backend tiene como objetivo principal gestionar toda la lógica del sistema:

- Gestión de usuarios con roles (cliente, profesional, administrador)
- Administración de servicios y disponibilidad
- Gestión del ciclo de vida de las reservas
- Control de pagos
- Envío de notificaciones automáticas y en tiempo real
- Integración con servicios externos

---

## Arquitectura

El backend está desarrollado como una API REST utilizando Laravel y sigue una arquitectura en capas:

Controllers: exponen los endpoints de la API y manejan las solicitudes HTTP.
Services: contienen la lógica de negocio del sistema.
Models: representan las entidades del dominio y gestionan la base de datos mediante Eloquent.
Notifications: gestionan el envío de notificaciones por correo y eventos en tiempo real.
Console: contiene tareas programadas (scheduler).
Routes: definen los endpoints de la API.

---

## Módulos del sistema

### Usuarios
Gestión de registro, autenticación, edición de perfil y roles (cliente, profesional, administrador).

### Servicios
Creación, modificación y eliminación de servicios ofrecidos por los profesionales. Configuración de duración, precio, modalidad y ubicación.

### Reservas
Creación, confirmación, cancelación y reprogramación de reservas. Control de estados y validación de disponibilidad.

### Paquetes
Compra de paquetes de servicios y control de sesiones disponibles por servicio.

### Pagos
Registro de pagos mediante PayPal Sandbox y pagos presenciales.

### Notificaciones
Notificaciones por correo y en tiempo real utilizando Laravel Reverb (WebSockets).

### Videollamadas
Integración con LiveKit para servicios virtuales.

### Calificaciones
Sistema de puntuación y comentarios de servicios.

---

## Tecnologías utilizadas

PHP 8.x, Laravel, PostgreSQL, Laravel Reverb, PayPal Sandbox, LiveKit, Google OAuth, SMTP, GitHub Actions.

---

## Configuración del entorno

Para ejecutar correctamente la aplicación es necesario contar con un archivo `.env`
configurado con las credenciales y parámetros requeridos por el sistema.

El archivo `.env` utilizado para la evaluación se entrega junto con el proyecto.

---

## Instalación

Instalar dependencias:

```bash
composer install
```

Generar la clave de la aplicación:

```bash
php artisan key:generate
```

Generar migraciones:
```bash
php artisan migrate
```
---

## Ejecución del sistema

Para ejecutar el backend:

```bash
php artisan serve
```

Para WebSockets (notificaciones en tiempo real):

```bash
php artisan reverb:start
```

Para tareas automáticas y recordatorios programados:

```bash
php artisan schedule:work
```

---

## Usuario administrador de prueba

Email: admin@citapro.com  
Contraseña: Admin1234

Este usuario permite acceder a todas las funcionalidades de administración del sistema.

---

## Despliegue

Backend desplegado en Railway.  
Frontend desplegado en Vercel.  
Base de datos PostgreSQL en la nube.

---

## Integración continua

Se utiliza GitHub Actions para pruebas automáticas y despliegue continuo del sistema.

---

## Integrantes

Juliana Méndez  
Cecilia Méndez  
Matías Alfaro  
Martina Castro  

Curso: Laboratorio PHP 2026