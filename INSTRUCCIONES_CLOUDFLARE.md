# 🏁 Guía de Despliegue 100% Gratuito en Cloudflare Pages

Esta guía te explica paso a paso cómo poner tu web de **F1 Stars** en **Cloudflare** sin pagar **ni un solo euro** (ni por hosting, ni por base de datos, ni por dominio).

Tendrás un dominio gratis oficial del tipo:
👉 `https://f1stars.pages.dev` (o el nombre que tú elijas terminado en `.pages.dev`).

---

## 🏎️ ¿Qué se ha creado y adaptado?

1. **Frontend idéntico y optimizado:**
   - `public/index.html`: Clasificación con emojis (👑 Andrés 253 pts, 👏 Iván 238 pts, 🤡 José Carlos 235 pts, 💩 Víctor 224 pts), gráfico interactivo de evolución de puntos por GP y botón para actualizar resultados.
   - `public/apostar.html`: Formulario para enviar apuestas con fotos de los 22 pilotos, colores oficiales de escudería y sesiones pendientes de 2026.
   - `public/ver-apuestas.html`: Historial completo de las 51 sesiones, resultados oficiales y apuestas de los 4 amigos con sus puntos desglosados.
   - `public/calendario.html`: Las 23 rondas de Grandes Premios de 2026.
   - `public/ajustes.html`: Panel de gestión con filtros por sesión/participante y botón de borrado de apuestas con modal de seguridad.
   - `public/img/`: Banner y logotipos guardados localmente para no depender de la web antigua.

2. **Backend Serverless (Cloudflare Functions):**
   - `functions/api/standings.js`: Clasificación en tiempo real.
   - `functions/api/chart-data.js`: Datos acumulados por GP para Chart.js.
   - `functions/api/sessions.js`: Listado de sesiones y apuestas.
   - `functions/api/drivers.js`: Lista de 22 pilotos con colores y fotos.
   - `functions/api/submit-bet.js`: Guarda o actualiza apuestas con control de duplicados y fecha límite.
   - `functions/api/delete-bet.js`: Borrado de apuestas desde ajustes.
   - `functions/api/sync-results.js`: Conexión directa con la API de OpenF1 para obtener resultados de podio y calcular puntos automáticamente con la fórmula oficial `3 - |pos_real - pos_pronosticada|`.

3. **Base de Datos Serverless (Cloudflare D1):**
   - `db/schema.sql`: Tablas SQLite (`participants`, `drivers`, `sessions`, `results`, `bets`, `settings`).
   - `db/seed.sql`: Todos los datos históricos exactos de 2026 (los 4 participantes, 201 apuestas, 150 resultados de podio).

---

## 🚀 Paso a Paso para Desplegar (Método Rápido con GitHub)

### Paso 1: Crear cuenta en Cloudflare (Gratis)
1. Entra en [https://dash.cloudflare.com/sign-up](https://dash.cloudflare.com/sign-up) y crea una cuenta si no tienes una. No pide tarjeta de crédito.

### Paso 2: Crear la Base de Datos D1 en Cloudflare
1. En el menú lateral izquierdo de Cloudflare, ve a **Storage & Databases** -> **D1 SQL Database**.
2. Pulsa en **Create database**.
3. Ponle de nombre: `f1_stars_db` y pulsa **Create**.
4. Entra en la base de datos recién creada y ve a la pestaña **Console**.
5. Abre en tu ordenador el archivo `c:\F1 Stars\f1_stars\db\seed.sql`, copia todo su contenido, pégalo en la consola de D1 y pulsa **Execute**.
   *(¡Listo! Ya tienes todos tus participantes, puntos y carreras cargados).*

### Paso 3: Subir el proyecto a GitHub
1. Abre tu cuenta de [GitHub](https://github.com) y crea un nuevo repositorio (puede ser público o privado), por ejemplo llamado `f1_stars`.
2. Sube la carpeta del proyecto a GitHub.

### Paso 4: Conectar GitHub con Cloudflare Pages
1. En Cloudflare, ve a **Workers & Pages** -> **Create**.
2. Selecciona la pestaña **Pages** -> **Connect to Git**.
3. Selecciona tu repositorio `f1_stars`.
4. En la configuración de compilación (**Build settings**):
   - **Framework preset**: `None`
   - **Build command**: (dejar vacío)
   - **Build output directory**: `public`
5. Pulsa **Save and Deploy**.

### Paso 5: Vincular la Base de Datos D1 al Proyecto Pages
1. En tu proyecto de Cloudflare Pages, ve a **Settings** -> **Functions** (o **Bindings**).
2. En la sección **D1 database bindings**, pulsa **Add binding**.
3. Rellena:
   - **Variable name**: `DB`  *(¡OJO!: Debe ser exactamente en mayúsculas `DB`)*
   - **D1 database**: Selecciona `f1_stars_db`.
4. Pulsa **Save**.
5. Ve a la pestaña **Deployments**, haz clic en los tres puntitos del último despliegue y dale a **Retry deployment** (para que tome el enlace de la base de datos).

---

## 🌐 ¡Y a disfrutar!
Tu web estará funcionando al instante en:
`https://<tu-nombre>.pages.dev`

- **Coste mensual:** 0,00 €
- **Coste anual:** 0,00 €
- **Sin necesidad de renovar f1stars.click.**
- **Tus amigos podrán entrar desde el móvil o el ordenador a apostar y ver los resultados como siempre.**
