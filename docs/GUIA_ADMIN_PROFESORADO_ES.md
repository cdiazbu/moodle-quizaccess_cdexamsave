# Guía de CDexamSave para administración y profesorado

## 1. Finalidad y límites

CDexamSave registra cambios de foco del navegador durante intentos reales y en curso de cuestionarios Moodle. Ayuda a localizar intentos que requieren revisión. No bloquea otras aplicaciones, no muestra qué abrió el alumno y no demuestra por sí solo una conducta indebida.

## 2. Instalación por el administrador

1. Realiza una copia actual y utiliza primero una réplica de Moodle 4.5.
2. Activa la depuración de desarrollador en la réplica.
3. Sube el ZIP desde **Administración del sitio > Extensiones > Instalar complementos**.
4. Comprueba que Moodle detecta `quizaccess_cdexamsave` y que el ZIP contiene una única carpeta superior llamada `cdexamsave`.
5. Completa la actualización de la base de datos y purga todas las cachés.
6. Comprueba que aparece la tarea **Eliminar datos de supervisión caducados de CDexamSave** y que el cron se ejecuta.
7. Revisa las capacidades de los roles:
   - `quizaccess/cdexamsave:viewreport`
   - `quizaccess/cdexamsave:exportreport`
8. Limita la exportación CSV al personal que realmente la necesite.

## 3. Ajustes generales

Revisa la sección de CDexamSave en los ajustes de reglas de acceso del cuestionario:

- **Plazo de conservación:** tiempo durante el que permanecen los datos finalizados.
- **Actualización del informe:** intervalo de consulta del panel docente.
- **Señal del alumno:** frecuencia de las señales de conexión activa.
- **Umbral de desconexión:** tiempo sin señal para mostrar un intento como desconectado.
- **Máximo de incidentes:** protección frente a fallos o abuso.

Empieza con los valores predeterminados. Sube el intervalo del informe a cinco o diez segundos si varios docentes supervisarán simultáneamente grupos grandes.

## 4. Activar un cuestionario supervisado

1. Abre el cuestionario y entra en **Editar ajustes**.
2. Despliega **Restricciones extra sobre los intentos > CDexamSave**.
3. Activa **Supervisión del foco**.
4. Mantén el aviso al alumno salvo que exista una política local documentada que justifique otra opción.
5. Elige el margen. Un segundo es un punto de partida razonable, pero debe probarse con los dispositivos del centro.
6. Guarda y muestra el cuestionario.

La página del cuestionario debe mostrar el aviso de supervisión. El personal autorizado también debe ver **Abrir informe en directo de CDexamSave**. La ruta directa es:

`/mod/quiz/accessrule/cdexamsave/report.php?cmid=ID_MODULO_CURSO`

El identificador es el valor `id` de `/mod/quiz/view.php?id=123`.

## 5. Prueba de aceptación obligatoria

Usa dos cuentas distintas y, preferiblemente, dos perfiles del navegador:

1. Un alumno inicia un intento real. No utilices la vista previa del profesor.
2. Un profesor abre el informe en directo.
3. El alumno permanece en el cuestionario y se comprueba el estado conectado/activo.
4. El alumno cambia de pestaña durante más tiempo que el margen.
5. El profesor comprueba que aparece la pérdida de foco dentro del intervalo de actualización.
6. El alumno regresa y confirma el aviso.
7. El profesor comprueba que el incidente se cierra con una duración.
8. Se exporta el CSV y se verifica el mismo incidente.
9. Se repite con una salida inferior al margen; no debe quedar un incidente persistente.
10. Se entrega el examen normalmente y se comprueba que la navegación o entrega no genera una falsa incidencia persistente.

Repite la prueba en cada combinación de navegador y dispositivo que se vaya a declarar compatible. Conserva capturas anonimizadas y la lista de validación completada.

## 6. Actuación del profesor durante el examen

1. Abre el informe antes de que comience el alumnado.
2. Si se desean avisos nativos, actívalos en el informe y mantén la página abierta.
3. Distingue estos estados:
   - **Conectado / Activo:** hay señales recientes y el intento tiene el foco.
   - **Fuera de Moodle / Foco perdido:** existe un incidente abierto.
   - **Sin señal reciente:** se superó el umbral; puede deberse a desconexión, suspensión o interrupción del script.
4. Antes de actuar, recoge el contexto: duración, repetición, incidencia técnica y observación en el aula.
5. Exporta el CSV solo cuando sea necesario y protégelo como información de evaluación.

## 7. Interpretación

Nunca trates un registro como prueba automática de copia. Pueden generarlo controles del navegador, diálogos del sistema, herramientas de accesibilidad, notificaciones, cambios de batería/conexión o suspensión móvil. El plugin no sabe qué pestaña o aplicación se abrió.

Aplica un procedimiento de revisión humana y documentado. Valora el patrón, duración, repetición, momento de las preguntas, contexto técnico y explicación del alumno. No impongas automáticamente una penalización académica o disciplinaria basándote solo en CDexamSave.

## 8. Solución de problemas

### Parece que el plugin no hace nada

- Confirma que está activado en ese cuestionario.
- Usa una cuenta de alumno y un intento real; las vistas previas están excluidas.
- Purga las cachés.
- Comprueba JavaScript y que la política de seguridad de contenidos no bloquee los módulos AMD.
- Revisa Consola y Red del navegador buscando errores de `quizaccess_cdexamsave/monitor` y `collector.php`.
- Comprueba que el intento sigue en estado `inprogress`.
- Verifica URL y HTTPS del sitio.
- Activa la depuración de desarrollador en la réplica y reproduce el fallo.

### El profesor no ve el informe

- Comprueba `quizaccess/cdexamsave:viewreport` en el contexto del cuestionario.
- Confirma que la regla está activada y el cuestionario guardado.
- Usa la URL directa con el identificador del módulo.
- Con grupos separados, comprueba el acceso del profesor al grupo correspondiente.

### El incidente queda abierto

Los navegadores móviles pueden suspender la página y retrasar la señal de regreso. Comprueba que el alumno vuelve al mismo intento activo y recupera la conexión.

## 9. Desinstalación

Antes de desinstalar, exporta únicamente los registros cuya conservación sea obligatoria. Sigue el procedimiento estándar de Moodle y comprueba en la réplica el borrado resultante. No elimines la carpeta del plugin antes de que Moodle complete la desinstalación de base de datos.
