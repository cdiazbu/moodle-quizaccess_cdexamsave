# Guía para publicar CD ExamFocus en Moodle Marketplace

## Situación actual

En julio de 2026 el antiguo directorio de plugins fue sustituido por Moodle Marketplace. CD ExamFocus ya está aprobado y publicado; la versión 1.0.3 debe añadirse manualmente desde el panel del producto existente. No se debe registrar un producto nuevo. La API de publicación de versiones todavía no está disponible en Marketplace.

## Identidad de la publicación

Datos confirmados:

- Responsable legal y titular del copyright: Carlos Díaz Bueno.
- Responsable de mantenimiento: Carlos Díaz Bueno.
- Afiliación institucional: Colegio Sagrada Familia – Siervas de San José, Salamanca.
- Soporte público: `https://github.com/cdiazbu/moodle-quizaccess_cdexamsave/issues`.
- Usuario de GitHub: `cdiazbu`.
- Repositorio: `moodle-quizaccess_cdexamsave`.
- URL prevista: `https://github.com/cdiazbu/moodle-quizaccess_cdexamsave`.
- Gestor de incidencias previsto: `https://github.com/cdiazbu/moodle-quizaccess_cdexamsave/issues`.
- Licencia: GPL v3 o posterior.

La afiliación institucional debe presentarse como contexto profesional y educativo. La cotitularidad del código o la representación legal del centro solo deben declararse si existe autorización expresa del colegio. Los encabezados del código atribuyen por ello el copyright a Carlos Díaz Bueno.

## Paso 1. Cerrar la validación en Moodle 4.5

1. Instala el ZIP en una copia de Moodle 4.5 con depuración de desarrollador.
2. Ejecuta la matriz completa de `TESTING.md` y `docs/RELEASE_CHECKLIST.md`.
3. Usa un intento real de alumno y una cuenta distinta de profesor.
4. Prueba al menos MySQL/MariaDB y PostgreSQL antes de declarar compatibilidad general.
5. Comprueba navegadores y dispositivos que se anunciarán.
6. Revisa instalación, actualización, copia/restauración, cron, privacidad, grupos, exportación y desinstalación.
7. Corrige cualquier aviso, error o diferencia entre lo anunciado y lo observado.

Las simulaciones incluidas son útiles, pero no sustituyen esta prueba real.

## Paso 2. Publicar el código definitivo

1. Integra la rama `release/1.0.3` en `main` únicamente después de superar las pruebas.
2. Comprueba que la raíz sigue siendo la raíz del plugin: `version.php`, `rule.php`, `lang/`, `classes/`, etc.
3. Crea una etiqueta anotada `v1.0.3` que apunte exactamente al código estable enviado.
4. Crea la entrega 1.0.3 en GitHub y adjunta el ZIP definitivo.
5. Genera el ZIP desde el contenido etiquetado y comprueba que su única carpeta superior sea `cdexamsave`.
6. No incluyas secretos, datos de alumnos, exportaciones, archivos del servidor ni configuraciones locales.

## Paso 3. Preparar recursos visuales

Haz las capturas indicadas en `docs/SCREENSHOT_PLAN.md` en un curso ficticio. No uses datos reales de menores. Elimina nombres, avatares, identificadores y preguntas reales.

Prepara, como mínimo:

- Ajustes de activación del cuestionario.
- Aviso visible al alumno.
- Informe en directo con datos ficticios.
- Historial/exportación o pantalla de ajustes generales.

## Paso 4. Revisar el producto existente

1. Accede a [Moodle Marketplace](https://marketplace.moodle.com/) con tu cuenta Moodle.
2. Abre el panel de proveedor y selecciona el producto existente **CD ExamFocus**.
3. Revisa la descripción breve, las capturas, el icono, el repositorio y el gestor de incidencias.
4. Mantén el producto como gratuito y GPL v3 o posterior.

La interfaz es nueva y puede cambiar; sigue los nombres reales que muestre Marketplace y la documentación enlazada desde el propio portal.

## Paso 5. Completar la ficha

Usa `docs/MARKETPLACE_LISTING_EN.md` como texto principal. El inglés es imprescindible para la revisión internacional. Usa la ficha española como traducción o documentación complementaria según las opciones del portal.

Campos esenciales:

- Nombre: **CD ExamFocus**.
- Componente: `quizaccess_cdexamsave`.
- Tipo: regla de acceso al cuestionario (`quizaccess`).
- Precio: gratuito.
- Licencia: GPL v3 o posterior.
- Dependencias: ninguna externa.
- Servicios externos/credenciales: ninguno.
- Compatibilidad declarada: solo las versiones realmente probadas; objetivo de la versión 1.0.3: Moodle 4.5.
- Código, incidencias y documentación: URL públicas definitivas.
- Privacidad: categorías exactas de datos y ausencia de transferencia externa.
- Limitaciones: no es navegador bloqueado ni prueba automática de copia.

No uses expresiones como «evita copiar», «detecta IA» o «demuestra fraude». No son técnicamente ciertas.

## Paso 6. Añadir y publicar la versión 1.0.3

1. Desde el producto existente, abre **Versions** y selecciona **Add version** o la opción equivalente.
2. Sube el ZIP exacto que haya superado las pruebas.
3. Declara `1.0.3`, etiqueta `v1.0.3`, madurez estable y únicamente las versiones Moodle comprobadas.
4. Comprueba el resultado del validador automático y de Moodle Plugin CI.
5. Corrige todos los errores y documenta justificadamente cualquier aviso que no pueda eliminarse.
6. Añade las notas de versión y publica la nueva versión; no vuelvas a enviar el plugin como producto nuevo.
7. Verifica en una sesión pública que la ficha muestra `Latest release: 1.0.3` y la fecha del día.

Al publicarse, el catálogo ordenado por **Latest release** utilizará la fecha de esta versión, por lo que CD ExamFocus aparecerá temporalmente entre los primeros resultados. La posición exacta dependerá de otras publicaciones realizadas ese mismo día y de los bloques destacados del Marketplace.

## Paso 7. Responder a la revisión

- Trata cada observación como una incidencia pública cuando afecte al código.
- Corrige en una rama, añade pruebas, actualiza el número de versión y publica otra etiqueta.
- No reempaquetes silenciosamente un mismo número de versión con código distinto.
- Mantén sincronizados repositorio, ZIP, notas y ficha.
- Conserva evidencia de las pruebas sin datos personales.

## Decisión sobre el español

Las directrices vigentes de Marketplace exigen que el paquete inicial distribuya únicamente `lang/en`. La traducción española debe enviarse mediante AMOS después de la aprobación. La documentación española permanece en `docs/` porque esta limitación afecta a los paquetes de idioma de la interfaz, no a la documentación.

## Referencias oficiales

- Moodle Marketplace: <https://marketplace.moodle.com/>
- Contribución de plugins (marcada por Moodle como heredada): <https://moodledev.io/general/community/plugincontribution>
- Lista técnica heredada: <https://moodledev.io/general/community/plugincontribution/checklist>
- Tipo `quizaccess`: <https://moodledev.io/docs/4.5/apis/plugintypes/quizaccess>
- Archivos comunes en Moodle 4.5: <https://moodledev.io/docs/4.5/apis/commonfiles>
- RGPD, artículo 13: <https://eur-lex.europa.eu/eli/reg/2016/679/oj>
- Guía de la AEPD para centros educativos: <https://www.aepd.es/guias/guia-centros-educativos.pdf>
