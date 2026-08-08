# Carmen Mestanza · Inmobiliaria + CRM

Aplicación Laravel Blade para una operación inmobiliaria boutique. Incluye
sitio público, captación de prospectos y un CRM administrativo responsivo.

## Módulos

- Sitio público editable y catálogo de propiedades.
- Formulario web conectado automáticamente con Leads.
- Dashboard ejecutivo con métricas, agenda, tareas y actividad.
- Leads con scoring, origen, estados y conversión a contacto/oportunidad.
- Contactos, inventario de propiedades y pipeline comercial.
- Galería con fotos, videos subidos y enlaces múltiples de YouTube.
- Tareas, citas, reportes y forecast ponderado.
- Recordatorios por correo para seguimientos, agenda y tareas pendientes.
- Panel de contenido para editar textos, datos de contacto y biografía CEO.
- Autenticación, CSRF, validación, throttling y sesiones seguras de Laravel.

## Inicio local

1. Copia `.env.example` como `.env` y configura solo valores locales.
2. Define `CRM_ADMIN_EMAIL` y una contraseña segura en `.env`.
3. Ejecuta `composer install`, `php artisan key:generate` y `npm install`.
4. Ejecuta `php artisan migrate --seed` y `npm run build`.
5. Inicia con `php artisan serve`.

La migración importa una sola vez las 7 propiedades históricas incluidas en
`database/legacy`, junto con sus detalles, fotos y videos. No pertenecen a
ningún seeder: si una propiedad se elimina después desde el CRM, no reaparece.
Los datos ficticios solo se habilitan deliberadamente con
`SEED_DEMO_DATA=true`; el valor recomendado y predeterminado es `false`.

## Correo y recordatorios

Configura un transporte real en `.env` antes de activar los avisos:

```env
APP_URL=https://tu-dominio.com
APP_TIMEZONE=America/Lima
MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-proveedor.com
MAIL_PORT=587
MAIL_USERNAME=tu-usuario
MAIL_PASSWORD=tu-clave
MAIL_FROM_ADDRESS=correo@tu-dominio.com
MAIL_FROM_NAME="Carmen Mestanza Inmobiliaria"
```

Después ejecuta `php artisan config:clear`. En `/admin/notificaciones` eliges
hasta 10 destinatarios y, para cada tipo de aviso (clientes por contactar,
agenda y tareas), por qué canal llega: correo, notificación de la app o ambos.

Hay dos clases de aviso y conviene no confundirlas:

- **Por registro.** Cada tarea o cita con «Avisarme» activado genera su propio
  aviso: uno de anticipación (30 minutos antes por defecto, configurable por
  registro), otro a la hora exacta y, si queda sin cerrar, un recordatorio
  diario mientras siga dentro del margen de días de vencimiento. El texto dice
  qué es, con quién y para cuándo; tocarlo en el celular abre esa ficha.
- **Resúmenes.** Una vez al día, a la hora configurada: agenda del día, tareas
  pendientes y lista de clientes por contactar.

**Revisar ahora** fuerza una pasada completa y **Enviar prueba** comprueba que
el correo y el push llegan. La pantalla muestra además la hora de la última
revisión automática: si está desactualizada, el cron del hosting no corre cada
minuto y los avisos se acumulan hasta la siguiente ejecución.

En cPanel crea un solo trabajo cron con minuto, hora, día, mes y semana en `*`.
Usa como comando, ajustando la ruta del proyecto y la ruta de PHP:

```cron
cd /home/USUARIO/ruta-del-proyecto && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

En desarrollo puedes mantener el scheduler activo con `php artisan schedule:work`.
El comando `php artisan schedule:list` confirma que el recordatorio está registrado.

El archivo `.env` está ignorado por Git. Nunca publiques credenciales reales.
Los archivos creados para la aplicación respetan el límite de 150 líneas;
dependencias de terceros en `vendor` y `node_modules` quedan fuera de esa regla.

## Despliegue en carmenmestanza.com

Requisitos recomendados: PHP 8.3 o superior, MySQL 8/MariaDB con `utf8mb4`,
Composer 2, Node.js para compilar y acceso cron. El document root del dominio
debe apuntar a la carpeta `public`, nunca a la raíz completa del proyecto.

1. Copia `.env.production.example` como `.env` en el servidor.
2. Completa base de datos, SMTP, `CRM_ADMIN_EMAIL` y una clave inicial fuerte.
3. Mantén `SEED_DEMO_DATA=false` para no insertar clientes o propiedades ficticias.
   Despliega también `database/legacy`: la primera migración usa esos archivos
   para instalar las propiedades históricas y sus galerías.
4. Ejecuta:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

`key:generate` se ejecuta solamente en la primera instalación. Nunca reemplaces
`APP_KEY` en un sitio que ya esté funcionando. El seeder crea al usuario
principal solo si todavía no existe. Volver a
ejecutarlo no cambia su correo, su contraseña ni el contenido editado desde el
panel. Si `CRM_ADMIN_PASSWORD` conserva `CHANGE_ME`, el seeder se detiene.

Para recuperación de contraseña, `MAIL_MAILER` debe ser `smtp` y el remitente
debe estar autorizado por el proveedor. Conviene configurar SPF, DKIM y DMARC
para `carmenmestanza.com`; de lo contrario el mensaje puede llegar a spam.

Configura el cron del scheduler cada minuto usando el ejemplo anterior. Tras
cambiar `.env` en producción ejecuta `php artisan optimize:clear` y luego
`php artisan optimize`. Las carpetas `storage` y `bootstrap/cache` deben tener
permiso de escritura para el usuario de PHP.

Comprueba al finalizar:

```bash
php artisan about
php artisan migrate:status
php artisan schedule:list
php artisan route:list
```
