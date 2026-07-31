# CD ExamFocus — ficha pública en español

Metadatos preparados para la ficha pública. Los marcadores de las plantillas de privacidad se mantienen por separado porque cada centro debe completarlos según su propio contexto.

## Nombre del producto

CD ExamFocus — Supervisión del foco en cuestionarios Moodle

## Componente Frankenstyle

`quizaccess_cdexamsave`

## Descripción breve

Detecta y documenta cuándo un cuestionario Moodle pierde el foco del navegador, con informe docente en directo y sin cámaras, servicios externos ni suscripciones.

## Descripción completa

CD ExamFocus es una regla de acceso para los cuestionarios de Moodle. Cuando se activa en un cuestionario, detecta señales del navegador que indican que un intento en curso ha dejado de ser la pestaña o ventana activa. Agrupa señales solapadas en un único incidente, aplica un margen de tolerancia opcional, avisa al alumno cuando regresa y presenta al profesorado autorizado un informe casi en tiempo real.

Abandonar la ventana activa del examen es un indicador relevante de que el alumno puede estar consultando IA u otro recurso externo no autorizado. CD ExamFocus aporta evidencias de esos cambios de foco sin capturar la cámara, grabar la pantalla ni depender de servicios de supervisión de terceros. Un intento sin pérdidas de foco detectadas significa que la ventana supervisada permaneció activa según las señales recibidas del navegador; no garantiza que no se utilizara IA. Pueden quedar fuera de su alcance un segundo dispositivo, un asistente integrado en el navegador, una superposición del sistema operativo, material preparado o la manipulación del cliente.

El informe muestra los intentos activos, el estado actual del foco, la conexión, el número de incidentes, el tiempo total fuera y los incidentes recientes. El personal autorizado puede exportar el historial visible en CSV. Las restricciones de grupos separados y los permisos específicos de consulta y exportación se comprueban en el servidor.

CD ExamFocus es una ayuda para supervisar y disuadir, no un navegador bloqueado. Un navegador normal no puede impedir el cambio de aplicación, identificar la pestaña o programa de destino, detectar el uso de otro dispositivo ni garantizar que un equipo no gestionado sea inmune a manipulaciones. También pueden producir incidentes legítimos por diálogos del sistema operativo, herramientas de accesibilidad, notificaciones, problemas de conexión o suspensión en segundo plano. Los registros son indicios que requieren revisión humana, no una prueba automática de conducta indebida.

No requiere servicios externos, suscripciones ni claves API. Los datos permanecen en la base de datos del propio sitio Moodle. El plugin no recoge URL de destino, historial de navegación, contenido del portapapeles, pulsaciones, capturas de pantalla, cámara, micrófono ni datos biométricos.

## Funciones principales

- Activación independiente en cada cuestionario desde **Ajustes del cuestionario > Restricciones extra sobre los intentos > CD ExamFocus**.
- Detección mediante las API estándar de visibilidad, foco y ciclo de vida del navegador.
- Margen configurable para ignorar cambios muy breves.
- Aviso opcional al alumno después de regresar.
- Informe docente con actualización automática y avisos opcionales del navegador.
- Estado de conexión y foco de los intentos en curso.
- Historial de incidentes y exportación CSV.
- Compatibilidad con grupos separados y capacidades específicas de Moodle.
- Reintentos y deduplicación ante problemas temporales de red.
- Conservación configurable y borrado mediante tarea programada.
- Implementación de la API de privacidad de Moodle.
- Copia y restauración de la configuración; el historial personal de supervisión no se incluye en las copias.
- Interfaz en español e inglés en el paquete institucional distribuido.

## Requisitos

- Moodle 4.0 o posterior según `version.php`.
- Navegador actual de escritorio o móvil con JavaScript activado.
- Se recomienda HTTPS para producción.
- El cron de Moodle debe funcionar para ejecutar la limpieza de datos.
- No requiere otros plugins ni servicios externos.

No se debe anunciar una versión de Moodle como «probada» hasta completar la lista de validación en esa versión concreta. El objetivo inicial de publicación es Moodle 4.5.

## Instalación

1. Realiza una copia de seguridad y prueba primero en una réplica del sitio.
2. Ve a **Administración del sitio > Extensiones > Instalar complementos**.
3. Sube el ZIP de la versión; su carpeta superior debe llamarse `cdexamsave`.
4. Confirma el componente `quizaccess_cdexamsave` y completa la actualización de la base de datos.
5. Purga las cachés de Moodle.
6. Revisa los ajustes generales y comprueba el cron.

Para instalarlo desde el servidor, copia `cdexamsave` en `mod/quiz/accessrule/`, entra en **Administración del sitio > Notificaciones**, completa la actualización y purga las cachés. No modifica archivos del núcleo de Moodle.

## Configuración y uso

Los ajustes globales de rendimiento y conservación se encuentran en la configuración de las reglas de acceso del cuestionario. La supervisión está desactivada por defecto y debe activarse de forma independiente en cada cuestionario.

Para supervisar un examen:

1. Edita el cuestionario.
2. Abre **Restricciones extra sobre los intentos > CD ExamFocus**.
3. Activa la supervisión, elige el margen y decide si el alumno debe confirmar el aviso al regresar.
4. Guarda el cuestionario.
5. Ábrelo con una cuenta docente autorizada y selecciona **Abrir informe en directo de CD ExamFocus**.
6. Comprueba el funcionamiento con otra cuenta de alumno y un intento real. Las vistas previas del profesor no se registran deliberadamente.

## Privacidad y protección de datos

El plugin trata el identificador Moodle del alumno, los identificadores del cuestionario e intento, identificadores aleatorios de evento y sesión, marcas temporales del servidor y cliente, categoría de la señal de foco, duración del incidente, estado de conexión/foco y última señal. El informe resuelve la identidad Moodle para el personal autorizado. El responsable que explota el sitio Moodle determina la finalidad, base jurídica, destinatarios y conservación.

Antes de utilizarlo, el centro debería documentar la necesidad y proporcionalidad, consultar a su delegado de protección de datos cuando proceda, informar al alumnado con lenguaje claro, restringir los permisos, fijar un plazo proporcionado, definir cómo revisar falsos positivos y valorar si se necesita una evaluación de impacto. El plugin no debe utilizarse para adoptar de forma exclusivamente automatizada decisiones disciplinarias o de calificación.

## Soporte e incidencias

- Código fuente: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave
- Incidencias: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave/issues
- Documentación: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave/blob/main/README.md
- Autor y responsable del mantenimiento: Carlos Díaz Bueno
- Afiliación institucional: Colegio Sagrada Familia – Siervas de San José, Salamanca, España
- Soporte: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave/issues

El copyright pertenece a Carlos Díaz Bueno. La afiliación identifica el entorno educativo en el que se desarrolló el plugin y no transfiere por sí sola la titularidad ni la responsabilidad jurídica al colegio.

Al comunicar un problema, indica Moodle, PHP, base de datos, navegador y sistema operativo; pasos exactos; mensajes relevantes con depuración de desarrollador; y si se trató de un intento real o de una vista previa. No publiques nombres de alumnos, preguntas, identificadores de sesión ni exportaciones de supervisión.

## Licencia

GNU General Public License v3 o posterior.

## Novedades — 1.0.2

- Primera versión estable enviada a Marketplace bajo la marca **CD ExamFocus**.
- Componente técnico estable `quizaccess_cdexamsave` para conservar la compatibilidad de instalación y actualización.
- Identidad visual profesional y recursos gráficos para Marketplace.
- Posicionamiento verificable frente al acceso a IA, sin afirmar una garantía imposible sobre su uso.
- Registro de pérdidas de foco con margen, reintentos y deduplicación.
- Aviso al alumno al regresar.
- Informe docente compatible con grupos y exportación CSV.
- Tarea de conservación, API de privacidad, permisos y copia/restauración de ajustes.
- Interfaz institucional en inglés y español.
- Documentación bilingüe de administración, publicación y privacidad.

Estado de publicación: versión inicial estable para Moodle 4.5. Las afirmaciones de compatibilidad más amplias se limitarán a los entornos realmente comprobados.
