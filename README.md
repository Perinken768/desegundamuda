# DeSegundaMuda

Plataforma web de anuncios clasificados orientada inicialmente al mercado de Canarias.

## Estado

Proyecto en desarrollo.

## Entorno de desarrollo

- Ubuntu Server 24.04 LTS
- Nginx
- PHP 8.3-FPM
- MariaDB
- Redis
- WordPress

## Producción

Despliegue previsto sobre VPS autogestionado.

- Ubuntu Server 24.04 LTS
- Nginx
- PHP 8.3-FPM
- MariaDB
- Redis
- WordPress
- n8n

## Estructura del proyecto

- `wordpress/`: componentes propios relacionados con WordPress.
- `config/`: configuración de los servicios del servidor.
- `database/`: migraciones y estructura de base de datos.
- `scripts/`: scripts de instalación, despliegue, backup y restauración.
- `docs/`: documentación técnica del proyecto.

## Entornos

### Desarrollo

Servidor local utilizado para desarrollo y pruebas.

### Producción

VPS destinado a alojar la plataforma pública.

El código se mantiene mediante Git para permitir control de versiones,
desarrollo seguro y despliegues reproducibles.
