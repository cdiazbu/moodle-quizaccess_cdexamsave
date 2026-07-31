<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Spanish language strings for CD ExamFocus.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Supervisión de foco CD ExamFocus';
$string['formheader'] = 'CD ExamFocus: supervisión del foco';
$string['enabled'] = 'Activar la supervisión del foco';
$string['enabled_help'] = 'Registra cuándo la pestaña o ventana del cuestionario deja de estar activa. El profesorado autorizado puede seguir los incidentes en un informe casi en tiempo real.';
$string['warnstudent'] = 'Avisar al alumno después de un incidente';
$string['warnstudent_help'] = 'Cuando el alumno regrese al cuestionario, se mostrará un aviso claro indicando que se ha registrado el cambio de foco.';
$string['graceperiod'] = 'Ignorar cambios inferiores a';
$string['graceperiod_help'] = 'Algunos controles del navegador o del sistema operativo generan cambios de foco muy breves. Este margen reduce los registros accidentales.';
$string['grace_none'] = 'No ignorar ningún cambio';
$string['grace_halfsecond'] = '0,5 segundos';
$string['grace_onesecond'] = '1 segundo';
$string['grace_twoseconds'] = '2 segundos';
$string['grace_threeseconds'] = '3 segundos';
$string['invalidgraceperiod'] = 'Seleccione un margen válido.';
$string['monitoringnotice'] = 'La supervisión CD ExamFocus está activada. Si se abandona esta pestaña o ventana del cuestionario, quedará registrado y se comunicará al profesorado autorizado.';
$string['monitoringbadge'] = 'Supervisión del foco activa';
$string['studentwarningtitle'] = 'Cambio de foco registrado';
$string['studentwarningtext'] = 'Este cuestionario ha dejado de ser la ventana activa. El incidente se ha añadido al informe en directo del profesor.';
$string['studentwarningduration'] = 'Tiempo fuera: {$a}';
$string['continueattempt'] = 'Continuar el examen';
$string['openlivereport'] = 'Abrir informe en directo de CD ExamFocus';

$string['settingsheading'] = 'CD ExamFocus';
$string['settingsheading_desc'] = 'Ajustes generales de rendimiento y conservación. La supervisión se activa de forma independiente en cada cuestionario.';
$string['retentiondays'] = 'Plazo de conservación (días)';
$string['retentiondays_desc'] = 'La tarea programada elimina los incidentes y sesiones finalizadas que superen este plazo. Valor efectivo mínimo: 1 día.';
$string['reportrefresh'] = 'Actualización del informe (segundos)';
$string['reportrefresh_desc'] = 'Intervalo de consulta del informe docente. Los valores se limitan a 2–30 segundos.';
$string['heartbeatinterval'] = 'Señal del alumno (segundos)';
$string['heartbeatinterval_desc'] = 'Frecuencia con la que un intento activo confirma que el supervisor está conectado. Los valores se limitan a 5–60 segundos.';
$string['staleseconds'] = 'Umbral de desconexión (segundos)';
$string['staleseconds_desc'] = 'Un intento aparece como desconectado después de este tiempo sin señal. Los valores se limitan a 15–300 segundos.';
$string['maxincidents'] = 'Máximo de incidentes por intento';
$string['maxincidents_desc'] = 'Límite de seguridad para impedir que un navegador defectuoso o manipulado llene la base de datos. Los valores se limitan a 100–10000.';

$string['cdexamsave:viewreport'] = 'Ver el informe en directo de CD ExamFocus';
$string['cdexamsave:exportreport'] = 'Exportar los incidentes de CD ExamFocus';

$string['livereport'] = 'Informe en directo de CD ExamFocus';
$string['reportfor'] = 'Supervisión del foco: {$a}';
$string['reportintro'] = 'El panel se actualiza automáticamente y muestra todos los intentos en curso y los incidentes recientes de pérdida de foco.';
$string['reportdisabled'] = 'CD ExamFocus no está activado actualmente en este cuestionario.';
$string['live'] = 'En directo';
$string['paused'] = 'En pausa';
$string['lastupdated'] = 'Última actualización: {$a}';
$string['refreshnow'] = 'Actualizar ahora';
$string['pauserefresh'] = 'Pausar la actualización automática';
$string['resumerefresh'] = 'Reanudar la actualización automática';
$string['enablenotifications'] = 'Activar avisos del navegador';
$string['notificationsenabled'] = 'Avisos del navegador activados';
$string['notificationsdenied'] = 'Avisos del navegador bloqueados';
$string['notificationtitle'] = 'CD ExamFocus: nuevo incidente de foco';
$string['notificationbody'] = '{$a->student} — {$a->reason}';
$string['exportcsv'] = 'Exportar todos los incidentes (CSV)';
$string['activeattempts'] = 'Intentos activos';
$string['attentionnow'] = 'Sin foco ahora';
$string['connectedattempts'] = 'Conectados';
$string['totalincidents'] = 'Incidentes';
$string['participants'] = 'Intentos en curso';
$string['recentincidents'] = 'Incidentes recientes';
$string['student'] = 'Alumno';
$string['attempt'] = 'Intento';
$string['connection'] = 'Conexión';
$string['focusstate'] = 'Estado del foco';
$string['incidentcount'] = 'Incidentes';
$string['totaltimeaway'] = 'Tiempo total fuera';
$string['lastheartbeat'] = 'Última señal';
$string['status_connected'] = 'Conectado';
$string['status_attention'] = 'Fuera de Moodle';
$string['status_disconnected'] = 'Sin señal reciente';
$string['status_notstarted'] = 'Conectando';
$string['focus_ok'] = 'Activo';
$string['focus_lost'] = 'Foco perdido';
$string['none'] = 'Ninguno';
$string['noactiveattempts'] = 'No hay intentos en curso.';
$string['noincidents'] = 'No se ha registrado ninguna pérdida de foco.';
$string['started'] = 'Inicio';
$string['ended'] = 'Regreso';
$string['duration'] = 'Duración';
$string['reason'] = 'Detección';
$string['incidentactive'] = 'En curso';
$string['reason_visibility_hidden'] = 'Pestaña oculta';
$string['reason_window_blur'] = 'La ventana perdió el foco';
$string['reason_pagehide'] = 'Página cerrada u oculta';
$string['reason_freeze'] = 'Página suspendida';
$string['reason_unknown'] = 'Cambio de foco';
$string['pollerror'] = 'No se han podido actualizar los datos en directo. CD ExamFocus volverá a intentarlo automáticamente.';
$string['noscript'] = 'Se necesita JavaScript para mostrar el informe en directo.';
$string['privacywarning'] = 'Este informe contiene datos personales de supervisión de evaluaciones. Gestione los archivos exportados según la política de protección de datos del centro.';

$string['export_student'] = 'Alumno';
$string['export_userid'] = 'ID de usuario';
$string['export_attempt'] = 'Intento';
$string['export_started'] = 'Pérdida de foco';
$string['export_returned'] = 'Recuperación del foco';
$string['export_duration'] = 'Duración (segundos)';
$string['export_reason'] = 'Detección';
$string['export_active'] = 'Sigue activo';
$string['yes'] = 'Sí';
$string['no'] = 'No';

$string['taskcleanup'] = 'Eliminar datos de supervisión caducados de CD ExamFocus';
$string['invalidrequest'] = 'Solicitud de supervisión CD ExamFocus no válida.';
$string['monitoringdisabled'] = 'CD ExamFocus no está activado para este cuestionario.';
$string['attemptnotmonitorable'] = 'Este intento no puede supervisarse.';
$string['incidentlimitreached'] = 'Se ha alcanzado el límite de seguridad de incidentes para este intento.';
$string['invalidgroup'] = 'No puede consultar los datos de ese grupo.';

$string['privacy:metadata:quizaccess_cdexamsave_evt'] = 'Incidentes de pérdida de foco detectados durante los intentos de cuestionario supervisados.';
$string['privacy:metadata:quizaccess_cdexamsave_evt:quizid'] = 'El cuestionario supervisado.';
$string['privacy:metadata:quizaccess_cdexamsave_evt:attemptid'] = 'El intento de cuestionario supervisado.';
$string['privacy:metadata:quizaccess_cdexamsave_evt:userid'] = 'El alumno cuyo intento fue supervisado.';
$string['privacy:metadata:quizaccess_cdexamsave_evt:eventuuid'] = 'Identificador aleatorio utilizado para evitar incidentes duplicados.';
$string['privacy:metadata:quizaccess_cdexamsave_evt:pagesessionid'] = 'Identificador aleatorio de la sesión de supervisión del navegador.';
$string['privacy:metadata:quizaccess_cdexamsave_evt:reason'] = 'La señal del navegador que detectó el cambio de foco.';
$string['privacy:metadata:quizaccess_cdexamsave_evt:times'] = 'Marcas temporales del servidor y del cliente y duración del incidente.';
$string['privacy:metadata:quizaccess_cdexamsave_sess'] = 'Estado actual de la conexión de supervisión de un intento.';
$string['privacy:metadata:quizaccess_cdexamsave_sess:quizid'] = 'El cuestionario supervisado.';
$string['privacy:metadata:quizaccess_cdexamsave_sess:attemptid'] = 'El intento de cuestionario supervisado.';
$string['privacy:metadata:quizaccess_cdexamsave_sess:userid'] = 'El alumno cuyo intento fue supervisado.';
$string['privacy:metadata:quizaccess_cdexamsave_sess:pagesessionid'] = 'Identificador aleatorio de la sesión de supervisión del navegador.';
$string['privacy:metadata:quizaccess_cdexamsave_sess:state'] = 'Estado de conexión, foco y última señal.';
$string['privacy:path'] = 'Supervisión del foco CD ExamFocus';
