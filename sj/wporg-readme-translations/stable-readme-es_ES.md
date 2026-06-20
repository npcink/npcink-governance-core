# es_ES - Stable Readme Translation Draft

## Short Description

Gobernanza, aprobación, comprobación previa al commit y registros de auditoría para operaciones de WordPress asistidas por IA.

## Description

Npcink Governance Core es la capa de gobernanza de Npcink AI para operaciones de WordPress. Ofrece a administradores del sitio y plugins host de confianza una capa de aprobación revisable antes de que las acciones asistidas por IA se confirmen en un sitio WordPress.

Core descubre operaciones disponibles de WordPress Abilities API, registra acciones propuestas por IA, permite aprobar o rechazar, devuelve contexto de commit preflight y guarda evidencia de auditoría. Está diseñado para instalaciones donde herramientas de IA, adapters o plugins de producto pueden solicitar cambios en WordPress, pero el sitio necesita un registro de gobernanza claro.

### Qué hace Core

- Descubre WordPress abilities disponibles para la entrada de gobernanza.
- Almacena propuestas para operaciones de WordPress asistidas por IA.
- Permite decisiones de aprobación y rechazo con evidencia de auditoría.
- Proporciona contexto de commit-preflight antes de que un host o adapter de confianza realice escrituras finales.
- Gestiona scoped app keys para clientes de gobernanza de confianza.
- Registra eventos de auditoría para creación de propuestas, policy evaluation, approval, rejection, commit preflight y execution-result handoff.
- Mantiene la gobernanza separada de la generación de contenido, el enrutamiento de modelos, la facturación de servicios en la nube y la ejecución de escrituras finales.

### Para quién es este plugin

Npcink Governance Core es para administradores de WordPress, plugins host, adapters y desarrolladores que necesitan un límite local de aprobación y auditoría para operaciones de WordPress asistidas por IA. Es útil cuando las herramientas de IA pueden preparar o solicitar cambios, pero el propietario del sitio o un cliente de gobernanza de confianza debe revisar, aprobar y rastrear esos cambios.

Este plugin no es un redactor de IA de un clic, asistente SEO, generador de imágenes, chatbot ni runtime de flujos de trabajo. Los flujos de producto y los ability callbacks pertenecen a plugins provider, adapter o de producto separados.

### Requisitos e integraciones

Core funciona mejor con WordPress 7.0 o superior y proveedores de WordPress Abilities API. El proveedor de referencia de primera parte es Npcink Abilities Toolkit, pero el ciclo de gobernanza base también puede gobernar proveedores externos de WordPress Abilities API que expongan ability ids, schemas, permission callbacks, risk metadata y dry-run previews estables.

Core expone endpoints REST de gobernanza bajo `/wp-json/npcink-governance-core/v1/`. Los adapters y plugins host de confianza pueden usar esos endpoints para crear propuestas, aprobar o rechazar propuestas, solicitar commit preflight y registrar resultados de ejecución externa.

### Privacidad y datos

Npcink Governance Core no llama a servicios de IA externos, no carga recursos remotos y no envía datos del sitio a terceros. Almacena registros de gobernanza localmente en tablas de la base de datos de WordPress, incluidos datos de propuestas, audit events, app-key metadata y rate-limit state. Los secretos de app keys se almacenan con hash, y los bearer tokens de un solo uso solo se muestran al crearse.

### Límites

Core no genera contenido, no enruta modelos, no ejecuta MCP ni workflow runtimes, no almacena provider credentials, no hace proxy de ability execution y no realiza mutaciones finales en WordPress. Las escrituras finales pertenecen a un host, adapter o runtime de confianza fuera de Core después de completar el paso de gobernanza.

## Installation

1. Sube el plugin a `wp-content/plugins/npcink-governance-core`.
2. Activa Npcink Governance Core en WordPress.
3. Abre Npcink AI > Core para revisar el estado de gobernanza, proposals, audit entries y controles avanzados de Core app-key.

## FAQ

### ¿Para quién es este plugin?

Es para administradores del sitio, plugins host, adapters y desarrolladores que necesitan gobernanza revisable para operaciones de WordPress asistidas por IA. Es especialmente útil cuando las herramientas de IA pueden preparar cambios, pero el sitio necesita aprobación, commit preflight y evidencia de auditoría antes de aplicarlos.

### ¿Es un generador de contenido con IA?

No. Core no escribe artículos, no genera medios, no crea textos SEO, no responde comentarios, no elige modelos de IA y no almacena provider credentials. Gobierna proposed operations creadas por herramientas, adapters o proveedores de WordPress Abilities API separados.

### ¿Core ejecuta escrituras de IA?

No. Core registra propuestas de gobernanza y devuelve contexto de commit-preflight. Las escrituras finales pertenecen a un host, adapter o runtime de confianza fuera de Core.

### ¿Qué es una proposal?

Una proposal es una solicitud almacenada para una operación de WordPress asistida por IA. Incluye la ability objetivo, resumen de entrada, preview o dry-run evidence, estado, caller metadata y audit trail necesario para revisión.

### ¿Qué es commit preflight?

Commit preflight es la comprobación de gobernanza que se ejecuta después de la aprobación y antes de que un componente externo de confianza realice la escritura final. Devuelve contexto acotado, correlation data e input binding para que el componente posterior verifique que actúa sobre la solicitud aprobada.

### ¿Necesito otro plugin para las abilities?

Sí, para una entrada completa de abilities. Core gobierna abilities expuestas por proveedores de WordPress Abilities API. Npcink Abilities Toolkit es el proveedor de referencia, y proveedores externos también pueden integrarse exponiendo ability ids, schemas, permissions, risk metadata y dry-run previews estables.

### ¿Este plugin envía datos a un servicio de IA externo?

No. Core no llama a servicios de IA externos y no envía datos del sitio a terceros. Almacena los registros de gobernanza localmente en tablas de la base de datos de WordPress.

### ¿Qué datos almacena Core?

Core almacena proposal records, approval y rejection decisions, commit-preflight evidence, execution-result handoff records, audit events, app-key metadata y rate-limit state. Los secretos de app keys se almacenan con hash, y los bearer tokens de un solo uso solo se muestran al crearse.

### ¿Para qué se usan las scoped app keys?

Las scoped app keys permiten que clientes de gobernanza de confianza llamen a endpoints REST específicos de Core sin concederles acceso amplio de administrador. Están pensadas para hosts controlados, adapters y clientes internos de gobernanza.

### ¿OpenClaw debe conectarse directamente a Core?

Una configuración productiva de OpenClaw debe conectarse mediante un adapter de confianza. Las app keys directas de Core son solo para clientes internos de gobernanza y fallback testing.

### ¿Los proveedores externos de abilities pueden usar Core?

Sí. El proposal lifecycle base es provider-neutral. Los proveedores externos pueden exponer WordPress Abilities API definitions con schemas, permission callbacks, risk metadata y dry-run previews, y luego enviar operaciones de escritura o destructivas para revisión de Core.

## Changelog

### 0.1.0

Plugin inicial de gobernanza con ability intake, proposals, approval/rejection, commit preflight, scoped app keys, rate limiting y audit records.
