# Guía para publicar CDexamSave en Moodle Marketplace

## Situación actual

En julio de 2026 el antiguo directorio de plugins fue sustituido por Moodle Marketplace. La publicación inicial debe hacerse en Marketplace siguiendo su documentación de proveedores y condiciones vigentes. Las páginas antiguas de «Plugin contribution» y su lista de comprobación siguen siendo útiles como referencia técnica, pero Moodle las identifica expresamente como un proceso heredado.

## Identidad de la publicación

Datos confirmados:

- Responsable legal y titular del copyright: Carlos Díaz Bueno.
- Responsable de mantenimiento: Carlos Díaz Bueno.
- Afiliación institucional: Colegio Sagrada Familia – Siervas de San José, Salamanca.
- Correo público de soporte: `carlosdiazbueno@gmail.com`.
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

## Paso 2. Crear el repositorio público

1. Crea en GitHub un repositorio público llamado `moodle-quizaccess_cdexamsave`.
2. La raíz del repositorio debe ser la raíz del plugin: `version.php`, `rule.php`, `lang/`, `classes/`, etc. No subas una carpeta contenedora adicional.
3. Activa GitHub Issues.
4. Sube `README.md`, `CHANGES.md`, `LICENSE.md`, `SECURITY.md`, `CONTRIBUTING.md`, `TESTING.md` y `docs/`.
5. Crea una etiqueta firmada o anotada con el número de la versión final solo después de cerrar la prueba. No etiquetes `1.0.1-rc1` como estable.
6. Genera el ZIP desde el contenido de la versión y comprueba que su carpeta superior sea `cdexamsave`.
7. No incluyas secretos, datos de alumnos, exportaciones, archivos del servidor ni configuraciones locales.

## Paso 3. Preparar recursos visuales

Haz las capturas indicadas en `docs/SCREENSHOT_PLAN.md` en un curso ficticio. No uses datos reales de menores. Elimina nombres, avatares, identificadores y preguntas reales.

Prepara, como mínimo:

- Ajustes de activación del cuestionario.
- Aviso visible al alumno.
- Informe en directo con datos ficticios.
- Historial/exportación o pantalla de ajustes generales.

## Paso 4. Crear o completar el perfil de proveedor

1. Accede a [Moodle Marketplace](https://marketplace.moodle.com/) con tu cuenta Moodle.
2. Inicia el proceso para listar un plugin y completa el perfil de proveedor que solicite la interfaz.
3. Acepta personalmente las condiciones de proveedor y declara correctamente si actúas como persona o entidad.
4. Introduce los datos fiscales/comerciales únicamente si el portal los requiere. CDexamSave puede plantearse como plugin gratuito y GPL.

La interfaz es nueva y puede cambiar; sigue los nombres reales que muestre Marketplace y la documentación enlazada desde el propio portal.

## Paso 5. Completar la ficha

Usa `docs/MARKETPLACE_LISTING_EN.md` como texto principal. El inglés es imprescindible para la revisión internacional. Usa la ficha española como traducción o documentación complementaria según las opciones del portal.

Campos esenciales:

- Nombre: **CDexamSave focus monitoring**.
- Componente: `quizaccess_cdexamsave`.
- Tipo: regla de acceso al cuestionario (`quizaccess`).
- Precio: gratuito.
- Licencia: GPL v3 o posterior.
- Dependencias: ninguna externa.
- Servicios externos/credenciales: ninguno.
- Versión inicial declarada: solo las versiones realmente probadas; objetivo inicial Moodle 4.5.
- Código, incidencias y documentación: URL públicas definitivas.
- Privacidad: categorías exactas de datos y ausencia de transferencia externa.
- Limitaciones: no es navegador bloqueado ni prueba automática de copia.

No uses expresiones como «evita copiar», «detecta IA» o «demuestra fraude». No son técnicamente ciertas.

## Paso 6. Subir la versión

1. Sube el ZIP exacto que haya superado las pruebas.
2. Comprueba el resultado del validador automático.
3. Corrige todos los errores y documenta justificadamente cualquier aviso que no pueda eliminarse.
4. Adjunta las capturas y las notas de versión.
5. Envía la ficha a revisión desde tu cuenta.

## Paso 7. Responder a la revisión

- Trata cada observación como una incidencia pública cuando afecte al código.
- Corrige en una rama, añade pruebas, actualiza el número de versión y publica otra etiqueta.
- No reempaquetes silenciosamente un mismo número de versión con código distinto.
- Mantén sincronizados repositorio, ZIP, notas y ficha.
- Conserva evidencia de las pruebas sin datos personales.

## Decisión sobre el español

La guía heredada del directorio recomendaba distribuir solo `lang/en` y aportar otras traducciones mediante AMOS después de la aprobación. Como Marketplace acaba de sustituir al directorio, confirma en el formulario actual si mantiene esta regla. Hasta tener confirmación, conserva `lang/es` en el repositorio institucional, pero prepara una variante de envío solo con inglés si el revisor la exige. La documentación española puede permanecer en `docs/`.

## Referencias oficiales

- Moodle Marketplace: <https://marketplace.moodle.com/>
- Contribución de plugins (marcada por Moodle como heredada): <https://moodledev.io/general/community/plugincontribution>
- Lista técnica heredada: <https://moodledev.io/general/community/plugincontribution/checklist>
- Tipo `quizaccess`: <https://moodledev.io/docs/4.5/apis/plugintypes/quizaccess>
- Archivos comunes en Moodle 4.5: <https://moodledev.io/docs/4.5/apis/commonfiles>
- RGPD, artículo 13: <https://eur-lex.europa.eu/eli/reg/2016/679/oj>
- Guía de la AEPD para centros educativos: <https://www.aepd.es/guias/guia-centros-educativos.pdf>
