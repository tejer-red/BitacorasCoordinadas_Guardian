# API Cache - Documentación

Este proyecto contiene dos archivos principales que trabajan juntos para gestionar y procesar instancias de APIs de WordPress. A continuación, se describe el propósito y funcionamiento de cada archivo.

## Archivos

### 1. `instancias.php`

Este archivo actúa como un servicio API para registrar, actualizar y consultar instancias de APIs de WordPress. Su funcionalidad principal incluye:

- **Registro de instancias**: Permite registrar nuevas instancias mediante un método `POST`. Cada instancia debe incluir una URL y una API key válida.
- **Actualización de instancias**: Si una instancia ya existe, se puede actualizar su información (como endpoints) siempre que la API key coincida.
- **Consulta de instancias**: Permite consultar las instancias registradas mediante un método `GET`. Los datos sensibles, como la API key, no se incluyen en la respuesta pública.
- **Limpieza automática**: Ocasionalmente, elimina instancias inactivas que no han sido confirmadas en los últimos 30 días.
- **Límite de instancias**: Impone un límite máximo de 1000 instancias registradas para evitar sobrecarga.

#### Estructura de una instancia
Cada instancia registrada tiene la siguiente estructura:
```json
{
    "url": "https://example.com",
    "api_key": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    "endpoints": {
        "posts": "https://example.com/wp-json/wp/v2/posts",
        "categories": "https://example.com/wp-json/wp/v2/categories"
    },
    "fecha_registro": "YYYY-MM-DD HH:MM:SS",
    "ultima_confirmacion": "YYYY-MM-DD HH:MM:SS",
    "activa": true
}
```

#### Uso
- **GET**: Devuelve una lista de instancias públicas.
- **POST**: Registra o actualiza una instancia. Ejemplo de payload:
  ```json
  {
      "url": "https://example.com",
      "api_key": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
      "endpoints": {
          "posts": "https://example.com/wp-json/wp/v2/posts"
      }
  }
  ```

---

### 2. `main.php`

Este archivo procesa las instancias registradas en `instancias.json` y realiza las siguientes tareas:

- **Iteración sobre instancias activas**: Solo procesa las instancias marcadas como activas.
- **Extracción de datos**: Para cada instancia, se recorren los endpoints registrados y se obtienen los datos (como posts y taxonomías).
- **Gestión de taxonomías**: Detecta y fusiona taxonomías evitando duplicados por ID.
- **Almacenamiento de datos**: Guarda los datos obtenidos en archivos JSON organizados por tipo de contenido y host de la instancia.

#### Flujo de trabajo
1. Leer el archivo `instancias.json` para obtener las instancias registradas.
2. Para cada instancia activa:
   - Procesar los endpoints registrados.
   - Extraer posts y sus metadatos.
   - Detectar y guardar taxonomías asociadas.
3. Guardar los datos procesados en la carpeta `output`.

#### Estructura de salida
Los datos procesados se almacenan en la carpeta `output`, organizada de la siguiente manera:
```
output/
└── {host}/
    ├── posts/
    │   └── {id}.json
    ├── taxonomias/
    │   └── {taxonomy}.json
    └── ...
```

#### Uso
Ejecutar el archivo para procesar todas las instancias activas:
```bash
php main.php
```

---

## Ejecución para Desarrollo

Es fácil ejecutar este proyecto para desarrollo utilizando el servidor embebido de PHP en Ubuntu. Solo necesitas ejecutar el siguiente comando desde el directorio raíz del proyecto:

```bash
php -S localhost:9999 -t ./
```

Esto iniciará un servidor local en `http://localhost:9999`, donde podrás probar las funcionalidades de los archivos `main.php` e `instancias.php`.

---

## Requisitos

- PHP 7.4 o superior.
- Extensión `json` habilitada.
- Permisos de escritura en el directorio donde se encuentra `instancias.json` y la carpeta `output`.

## Notas

- **Seguridad**: Asegúrate de proteger el acceso a `instancias.php` para evitar registros no autorizados.
- **Limpieza automática**: La limpieza de instancias inactivas se ejecuta con una probabilidad del 10% en cada solicitud a `instancias.php`.

## Contribución

Si deseas contribuir a este proyecto, por favor abre un issue o envía un pull request.

---
