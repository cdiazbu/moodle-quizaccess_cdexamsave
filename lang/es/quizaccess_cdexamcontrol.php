<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish language strings for CD Exam Control.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activeattempts'] = 'Intentos en curso';
$string['attempt'] = 'Intento';
$string['attemptnotmonitorable'] = 'Este intento no admite seguimiento.';
$string['attentionnow'] = 'Cuestionario sin foco';
$string['blockshortcuts_help'] = 'Intenta interceptar Ctrl/Cmd+T, Ctrl/Cmd+N y enlaces a nuevas pestañas cuando el navegador lo permite. Desactivado por defecto. Mantiene copiar, pegar, ampliar y los controles de accesibilidad.';
$string['blockshortcuts'] = 'Restringir nuevas pestañas y ventanas';
$string['cdexamcontrol:exempt'] = 'Eximir del seguimiento a una persona por una adaptación acordada';
$string['cdexamcontrol:exportreport'] = 'Exportar observaciones de CD Exam Control';
$string['cdexamcontrol:viewreport'] = 'Ver el informe de CD Exam Control';
$string['collectorbusy'] = 'El registro está ocupado. El navegador reintentará el envío.';
$string['connectedattempts'] = 'Conectados';
$string['connection'] = 'Conexión';
$string['continueattempt'] = 'Continuar el cuestionario';
$string['duration'] = 'Duración';
$string['enabled_help'] = 'Registra cambios de foco del cuestionario y los controles configurados. El profesorado autorizado puede consultar las observaciones.';
$string['enabled'] = 'Activar CD Exam Control';
$string['enablenotifications'] = 'Activar avisos del navegador';
$string['ended'] = 'Recuperado';
$string['export_active'] = 'Observación abierta';
$string['export_attempt'] = 'Intento';
$string['export_duration'] = 'Duración estimada (segundos)';
$string['export_finish'] = 'Finalizado';
$string['export_incidentcount'] = 'Número de observaciones';
$string['export_maxduration'] = 'Observación más larga (segundos)';
$string['export_needsreview'] = 'Prioridad de revisión';
$string['export_reason'] = 'Señal del navegador';
$string['export_returned'] = 'Foco recuperado';
$string['export_start'] = 'Inicio';
$string['export_started'] = 'Foco perdido';
$string['export_state'] = 'Estado del intento';
$string['export_student'] = 'Estudiante';
$string['export_totalduration'] = 'Tiempo estimado sin foco (segundos)';
$string['export_userid'] = 'ID del usuario';
$string['exportincidentscsv'] = 'Observaciones detalladas (CSV)';
$string['exportsummarycsv'] = 'Resumen de intentos (CSV)';
$string['filterall'] = 'Todos los intentos en curso';
$string['filterattention'] = 'Cuestionario sin foco';
$string['filterdisconnected'] = 'Sin señal reciente';
$string['filterlabel'] = 'Filtrar intentos';
$string['filterreview'] = 'Revisión recomendada';
$string['focus_lost'] = 'Foco perdido';
$string['focus_ok'] = 'Activo';
$string['focusstate'] = 'Estado del foco';
$string['formheader'] = 'CD Exam Control';
$string['fullscreenbutton'] = 'Entrar en pantalla completa y continuar';
$string['fullscreenerror'] = 'No se ha podido entrar en pantalla completa. Reinténtalo o pide al profesor que ajuste el cuestionario.';
$string['fullscreentext'] = 'Este cuestionario solicita pantalla completa. Salir puede generar una observación del navegador. Usa el botón para regresar. Moodle sigue gestionando tus respuestas y el tiempo.';
$string['fullscreentitle'] = 'Volver al cuestionario en pantalla completa';
$string['fullscreenunsupported'] = 'Este navegador no admite pantalla completa. Puedes continuar con el seguimiento del foco; informa al profesor de esta limitación.';
$string['grace_halfsecond'] = '0,5 segundos';
$string['grace_none'] = 'No ignorar cambios';
$string['grace_onesecond'] = '1 segundo';
$string['grace_threeseconds'] = '3 segundos';
$string['grace_twoseconds'] = '2 segundos';
$string['graceperiod_help'] = 'Los controles del navegador o del sistema pueden provocar cambios breves de foco. Este margen reduce las observaciones accidentales.';
$string['graceperiod'] = 'Ignorar cambios inferiores a';
$string['heartbeatinterval_desc'] = 'Frecuencia de comprobación del monitor. Entre 5 y 60 segundos.';
$string['heartbeatinterval'] = 'Señal de conexión del estudiante (segundos)';
$string['incidentactive'] = 'Abierta';
$string['incidentcount'] = 'Observaciones';
$string['incidentlimitreached'] = 'Se ha alcanzado el límite de observaciones de este intento.';
$string['invalidgraceperiod'] = 'Selecciona un margen válido.';
$string['invalidgroup'] = 'No puedes consultar los datos de ese grupo.';
$string['invalidrequest'] = 'Solicitud de seguimiento no válida.';
$string['lastheartbeat'] = 'Última señal';
$string['lastupdated'] = 'Última actualización: {$a}';
$string['live'] = 'En directo';
$string['livereport'] = 'Informe de CD Exam Control';
$string['maxincidents_desc'] = 'Evita que un navegador defectuoso o manipulado llene la base de datos. Entre 100 y 10000.';
$string['maxincidents'] = 'Máximo de observaciones por intento';
$string['monitorconnecting'] = 'CD Exam Control conectando';
$string['monitoringbadge'] = 'CD Exam Control conectado';
$string['monitoringdisabled'] = 'CD Exam Control no está activado en este cuestionario.';
$string['monitoringnotice'] = 'CD Exam Control está activado. Los cambios de foco y los controles configurados se comunican al profesorado autorizado. Los problemas técnicos o de accesibilidad pueden generar observaciones que deben interpretarse en su contexto.';
$string['monitorpending'] = 'CD Exam Control: observaciones pendientes de conexión';
$string['monitorqueuefull'] = 'CD Exam Control: demasiadas observaciones pendientes; informa al profesor.';
$string['monitorstopped'] = 'CD Exam Control: seguimiento detenido';
$string['no'] = 'No';
$string['noactiveattempts'] = 'No hay intentos en curso.';
$string['nofilteredattempts'] = 'Ningún intento coincide con la búsqueda y el filtro.';
$string['noincidents'] = 'No se han registrado observaciones.';
$string['none'] = 'Ninguno';
$string['noscript'] = 'El informe necesita JavaScript.';
$string['notificationbody'] = '{$a->student} — {$a->reason}';
$string['notificationsdenied'] = 'Avisos bloqueados por el navegador';
$string['notificationsenabled'] = 'Avisos activados';
$string['notificationtitle'] = 'CD Exam Control: nueva observación';
$string['openlivereport'] = 'Abrir el informe de CD Exam Control';
$string['participants'] = 'Intentos en curso';
$string['paused'] = 'Pausado';
$string['pauserefresh'] = 'Pausar actualización automática';
$string['pluginname'] = 'CD Exam Control';
$string['pollerror'] = 'No se han podido actualizar los datos. Se reintentará automáticamente.';
$string['privacy:metadata:quizaccess_cdexamcontrol_evt:attemptid'] = 'Intento del cuestionario supervisado.';
$string['privacy:metadata:quizaccess_cdexamcontrol_evt:eventuuid'] = 'Identificador aleatorio para evitar observaciones duplicadas.';
$string['privacy:metadata:quizaccess_cdexamcontrol_evt:pagesessionid'] = 'Identificador aleatorio de la sesión de la página.';
$string['privacy:metadata:quizaccess_cdexamcontrol_evt:quizid'] = 'Cuestionario supervisado.';
$string['privacy:metadata:quizaccess_cdexamcontrol_evt:reason'] = 'Señal del navegador que originó la observación.';
$string['privacy:metadata:quizaccess_cdexamcontrol_evt:times'] = 'Marcas temporales del servidor y del cliente y duración estimada.';
$string['privacy:metadata:quizaccess_cdexamcontrol_evt:userid'] = 'Estudiante cuyo intento se supervisó.';
$string['privacy:metadata:quizaccess_cdexamcontrol_evt'] = 'Observaciones del navegador durante intentos supervisados.';
$string['privacy:metadata:quizaccess_cdexamcontrol_ses:attemptid'] = 'Intento supervisado.';
$string['privacy:metadata:quizaccess_cdexamcontrol_ses:pagesessionid'] = 'Identificador aleatorio de la sesión de la página.';
$string['privacy:metadata:quizaccess_cdexamcontrol_ses:quizid'] = 'Cuestionario supervisado.';
$string['privacy:metadata:quizaccess_cdexamcontrol_ses:state'] = 'Estado de conexión, foco y última señal.';
$string['privacy:metadata:quizaccess_cdexamcontrol_ses:userid'] = 'Estudiante cuyo intento se supervisó.';
$string['privacy:metadata:quizaccess_cdexamcontrol_ses'] = 'Estado de la conexión del monitor de un intento.';
$string['privacy:path'] = 'Seguimiento de CD Exam Control';
$string['privacywarning'] = 'Este informe contiene datos personales de evaluación. Trata las exportaciones según la política de protección de datos de tu centro.';
$string['reason_freeze'] = 'Página suspendida';
$string['reason_fullscreen_exit'] = 'Salida de pantalla completa';
$string['reason_pagehide'] = 'Página cerrada u oculta';
$string['reason_shortcut_blocked'] = 'Solicitud de nueva pestaña o ventana interceptada';
$string['reason_unknown'] = 'Cambio de foco';
$string['reason_visibility_hidden'] = 'Pestaña oculta';
$string['reason_window_blur'] = 'Ventana sin foco';
$string['reason'] = 'Señal del navegador';
$string['recentincidents'] = 'Observaciones recientes';
$string['refreshnow'] = 'Actualizar ahora';
$string['reportdisabled'] = 'CD Exam Control no está activado en este cuestionario.';
$string['reportfor'] = 'Seguimiento: {$a}';
$string['reportintro'] = 'El panel muestra los intentos en curso y las observaciones recientes. Las duraciones son estimaciones; las señales pueden verse afectadas por la conexión.';
$string['reportrefresh_desc'] = 'Intervalo de actualización del informe. Entre 2 y 30 segundos.';
$string['reportrefresh'] = 'Actualizar el informe (segundos)';
$string['requirefullscreen_help'] = 'Muestra un aviso accesible de pantalla completa. Si el navegador no la admite, permite continuar con el seguimiento del foco. Es una ayuda del navegador. Puedes desactivarla cuando una adaptación lo requiera.';
$string['requirefullscreen'] = 'Solicitar pantalla completa durante el intento';
$string['resumerefresh'] = 'Reanudar actualización automática';
$string['retentiondays_desc'] = 'Elimina datos antiguos de intentos terminados mediante la tarea programada. Se conservan los intentos en curso o pendientes de cierre. Entre 1 y 3650 días.';
$string['retentiondays'] = 'Conservación de los datos (días)';
$string['reviewdisclaimer'] = 'La prioridad de revisión ayuda a ordenar los intentos por número de observaciones y duración estimada. No prueba una conducta indebida. Revisa el contexto técnico y educativo de cada caso.';
$string['reviewduration_desc'] = 'Da prioridad al alcanzar esta duración acumulada. Entre 1 y 86400 segundos.';
$string['reviewduration'] = 'Umbral de duración para revisión (segundos)';
$string['reviewincidentcount_desc'] = 'Da prioridad al alcanzar este número de observaciones. Entre 1 y 100.';
$string['reviewincidentcount'] = 'Umbral de observaciones para revisión';
$string['reviewnotneeded'] = 'Sin prioridad';
$string['reviewpriority'] = 'Prioridad de revisión';
$string['reviewprioritysettings_desc'] = 'Estos umbrales ayudan al profesorado a revisar los intentos. No determinan ni implican una conducta indebida.';
$string['reviewprioritysettings'] = 'Prioridad para revisión humana';
$string['reviewrecommended'] = 'Revisión recomendada';
$string['searchattempts'] = 'Buscar estudiantes';
$string['searchattemptsplaceholder'] = 'Buscar por nombre';
$string['settingsheading_desc'] = 'Ajustes generales de rendimiento y conservación. Activa el seguimiento en cada cuestionario.';
$string['settingsheading'] = 'CD Exam Control';
$string['shortcutnotice'] = 'Este cuestionario restringe la apertura de otras pestañas o ventanas.';
$string['staleseconds_desc'] = 'Tiempo sin señal antes de mostrar una conexión incierta. Entre 15 y 300 segundos.';
$string['staleseconds'] = 'Umbral sin señal reciente (segundos)';
$string['started'] = 'Inicio';
$string['status_attention'] = 'Cuestionario sin foco';
$string['status_connected'] = 'Conectado';
$string['status_disconnected'] = 'Sin señal reciente';
$string['status_exempt'] = 'Adaptación acordada: exento de seguimiento';
$string['status_notstarted'] = 'Conectando';
$string['student'] = 'Estudiante';
$string['studentwarningduration'] = 'Tiempo estimado sin foco: {$a}';
$string['studentwarningtext'] = 'El cuestionario dejó de estar activo o salió de pantalla completa. Se enviará una observación al profesorado autorizado. Esta señal no demuestra una conducta indebida.';
$string['studentwarningtitle'] = 'Ha cambiado el foco del cuestionario';
$string['taskcleanup'] = 'Eliminar datos caducados de CD Exam Control';
$string['totalincidents'] = 'Observaciones';
$string['totaltimeaway'] = 'Tiempo estimado sin foco';
$string['warnstudent_help'] = 'Al regresar al cuestionario, muestra un aviso comprensible de la observación y su comunicación al profesorado.';
$string['warnstudent'] = 'Avisar al estudiante tras una observación';
$string['yes'] = 'Sí';
