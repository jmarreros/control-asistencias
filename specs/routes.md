# Spec: Rutas

## Portal alumno (público, sin autenticación)
```
GET  /                    StudentPortalController@publicSearch  ← búsqueda pública por DNI
GET  /student/lookup      StudentPortalController@lookup        ← endpoint AJAX JSON (dni=?)
```

## Admin (protegidas por `check.pin` + `session.timeout` + `log.access`)
```
GET/POST  /login                         PinController (auth PIN)
POST      /logout                        PinController@logout

GET       /admin                         DashboardController@index
GET       /settings                      SettingController@edit
POST      /settings                      SettingController@update

GET/POST  /students                      StudentController (index, create, store, edit, update, destroy)
GET       /matricula                     MatriculaController@index          ← buscador alumno + gestión de plan
GET       /students/{student}/plans      StudentPlanController@index
POST      /students/{student}/plans      StudentPlanController@store
DELETE    /students/{student}/plans/{plan} StudentPlanController@destroy

GET/POST  /clases                        ClaseController (index, create, store, edit, update, destroy)
GET/POST  /clases/{clase}/enroll         EnrollmentController (edit, update)

GET       /attendance                    AttendanceController@index         ← buscador de alumnos
GET       /attendance/student/{student}  AttendanceController@takeByStudent ← cursos del día del alumno
GET       /attendance/{clase}/take       AttendanceController@take
POST      /attendance/{clase}/save       AttendanceController@save
POST      /attendance/{clase}/toggle     AttendanceController@toggle
POST      /attendance/{clase}/add-student AttendanceController@addStudent

GET       /checkin                         CheckinController@show         ← kiosko de entrada por DNI
POST      /checkin                         CheckinController@store        ← marcar asistencia (JSON)
DELETE    /checkin                         CheckinController@destroy      ← eliminar asistencia (JSON)
GET       /checkin/attendances             CheckinController@attendances  ← asistencias del día para un curso
GET       /checkin/detect-clase            CheckinController@detectClase  ← detección interna (server-side)

GET       /import                        ImportController@show
POST      /import                        ImportController@import

GET       /logs                          AccessLogController@index   ← historial de accesos (paginado, filtrable)
DELETE    /logs                          AccessLogController@destroy ← borrar todos los logs

GET       /reports                       ReportController@index
GET       /reports/students/export       ReportController@studentsExport  ← Excel alumnos + plan actual
GET       /reports/earnings              ReportController@earnings         ← visible solo si show_earnings=1
GET       /reports/earnings/export       ReportController@earningsExport
GET       /reports/clase/{clase}         ReportController@byClase
GET       /reports/clase/{clase}/student/{student} ReportController@byClaseStudent
GET       /reports/student/{student}     ReportController@byStudent
```
