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
 * Language file for tool_bulk_backupandrestore
 *
 * @package    tool
 * @subpackage  bulk_backupandrestore
 * @copyright  2020 and onwards Erwin Meza Vega <emezav@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.5
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Copia de seguridad y restauración masiva de cursos';
$string['privacy:metadata'] = 'Este plugin no almacena ninguna información personal.';
$string['restorecourses'] = 'Restaurar cursos';
$string['backupcategory'] = 'Copia de seguridad de categoría';
$string['back'] = 'Regresar';
$string['coursesonthiscategory'] = 'Cursos en esta categoría: {$a}';
$string['nocoursesin'] = 'No hay cursos en la categoría {$a}';
$string['firstncourses'] = 'Primeros {$a} cursos en ';
$string['total'] = 'total';
$string['categories'] = 'Categorías';
$string['categoriesof'] = 'Sub-categorías de {$a}';
$string['backupthiscategory'] = 'Realizar copia de seguridad';
$string['startbackup'] = 'Iniciar copia de seguridad';

$string['outdir'] = 'Directorio de salida';
$string['outdir_help'] = 'Directorio en el cual se copian los archivos de copia de seguridad';

$string['backupusers'] = 'Incluir usuarios';
$string['backupusers_help'] = 'Incluye los usuarios en la copia de seguridad';

$string['backupblocks'] = 'Incluir bloques';
$string['backupblocks_help'] = 'Incluye los bloques en la copia de seguridad';

$string['invalidoutdir'] = 'Directorio de copia inválido';
$string['invalidcategory'] = 'Categoría no válida';
$string['invalidcourse'] = 'ID de curso no válido';
$string['invalidsession'] = 'Sesión no válida';

$string['id'] = 'Id';
$string['name'] = 'Nombre';
$string['status'] = 'Estado';

$string['ready'] = 'Listo';
$string['ok'] = 'Correcto';
$string['failed'] = 'Falló';

$string['downloadreport'] = 'Descargar reporte';

$string['backupsuccessful'] = 'Copia exitosa';
$string['backupfailed'] = 'Falló la copia';

$string['continueonerror'] = 'Continuar si hay error';
$string['continueonerror_help'] = 'Continúa aun si existen registros con error';

$string['containsheader'] = 'El archivo contiene encabezado';
$string['containsheader_help'] = 'La primera línea del archivo CSV contiene el nombre de las columnas (no se usa)';

$string['restoreusers'] = 'Restaurar usuarios';
$string['restoreusers_help'] = 'Incluir los usuarios en la restauración';

$string['restoreblocks'] = 'Restaurar bloques';
$string['restoreblocks_help'] = 'Incluir los bloques en la restauración';

$string['csvdelimiter'] = 'Delimitador de los campos';
$string['encoding'] = 'Codificación';

$string['restore'] = 'Restaurar';

$string['invalidcolumns'] = 'El archivo CSV debe tener 8 columnas en el siguiente orden: CategoryId,Folder,Filename,Name,Shortname,IdNumber,Users,Blocks';

$string['invalidfolder'] = 'El directorio no existe o no es accesible';
$string['invalidfile'] = 'El archivo no existe o no es accesible';
$string['idnumberexists'] = 'Número ID ya existe en otro curso';
$string['shortnameexists'] = 'Nombre corto ya existe en otro curso';
$string['norecords'] = 'No hay registros para restaurar';

$string['records'] = 'Registros: {$a}';
$string['startrestore'] = 'Iniciar restauración';
$string['shortname'] = 'Nombre corto';
$string['idnumber'] = 'Numero ID';
$string['users'] = 'Usuarios';
$string['blocks'] = 'Bloques';

$string['coursenotrestored'] = 'El curso no pudo ser restaurado';
$string['restoresuccessful'] = 'Restauración correcta';
$string['restoredid'] = 'Restaurado con Id: {$a}';

$string['examplecsv'] = 'Archivo CSV de ejemplo';
$string['examplecsv_help'] = 'Ejemplo de archivo CSV con el formato requerido.';
