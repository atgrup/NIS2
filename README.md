# NIS2 Compliance Platform

Plataforma web desarrollada para gestionar y auditar el cumplimiento de la normativa europea de ciberseguridad NIS2. El sistema conecta a empresas con consultores especializados a través de un flujo estructurado de gestión documental y validación.

## 🚀 Funcionalidades Principales

*   **Autenticación Segura:** Sistema de registro de empresas con verificación de identidad mediante correo electrónico.
*   **Gestión Documental:** Acceso a un repositorio centralizado de plantillas oficiales para la adaptación a la normativa NIS2.
*   **Flujo de Subida y Procesamiento:** Las empresas pueden descargar, cumplimentar y subir los documentos requeridos directamente a la plataforma.
*   **Sistema de Roles y Auditoría:** Panel de administración dedicado para consultores, que permite revisar la documentación subida, emitir aprobaciones (OK) o rechazar archivos solicitando revisiones y correcciones a la empresa.

## 🛠️ Stack Tecnológico

*   **Backend:** PHP puro estructurado.
*   **Base de Datos:** MySQL (esquema relacional gestionado mediante sistema de migraciones).
*   **Arquitectura:** Sistema basado en roles (Empresa / Consultor) con separación lógica de privilegios e integración de APIs para el envío de notificaciones.
