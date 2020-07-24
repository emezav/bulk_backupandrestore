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

$string['pluginname'] = 'Bulk backup and restore courses';
$string['privacy:metadata'] = 'The Bulk Courses plugin does not store any personal data.';
$string['restorecourses'] = 'Restore courses';
$string['backupcategory'] = 'Backup category';
$string['back'] = 'Go back';
$string['coursesonthiscategory'] = 'Courses on this category: {$a}';
$string['nocoursesin'] = 'There are no courses on category {$a}';
$string['firstncourses'] = 'First {$a} courses on';
$string['total'] = 'total';
$string['categories'] = 'Categories';
$string['categoriesof'] = 'Categories on {$a}';
$string['backupthiscategory'] = 'Backup this category';
$string['startbackup'] = 'Start backup';

$string['outdir'] = 'Output directory';
$string['outdir_help'] = 'Directory where backups will be stored.';

$string['backupusers'] = 'Include users';
$string['backupusers_help'] = 'Include users on course backup';

$string['backupblocks'] = 'Include blocks';
$string['backupblocks_help'] = 'Include blocks on course backup';

$string['invalidoutdir'] = 'Invalid backup dir';
$string['invalidcategory'] = 'Invalid category';
$string['invalidcourse'] = 'Invalid course ID';
$string['invalidsession'] = 'Invalid session';

$string['id'] = 'Id';
$string['name'] = 'Name';
$string['status'] = 'Status';

$string['ready'] = 'Ready';
$string['ok'] = 'OK';
$string['failed'] = 'Failed';

$string['downloadreport'] = 'Download report';

$string['backupsuccessful'] = 'Backup successful';
$string['backupfailed'] = 'Backup failed';

$string['continueonerror'] = 'Continue on error';
$string['continueonerror_help'] = 'Continue even if some records have errors.';

$string['containsheader'] = 'File contains header';
$string['containsheader_help'] = 'File contains a header on the first line';

$string['restoreusers'] = 'Restore users';
$string['restoreusers_help'] = 'Include users on course';

$string['restoreblocks'] = 'Restore blocks';
$string['restoreblocks_help'] = 'Restore blocks on course';

$string['csvdelimiter'] = 'CSV delimiter';
$string['encoding'] = 'Encoding';

$string['restore'] = 'Restore';

$string['invalidcolumns'] = 'The CSV file must have 8 columns: CategoryId,Folder,Filename,Name,Shortname,IdNumber,Users,Blocks';

$string['invalidcategory'] = 'Invalid category';
$string['invalidfolder'] = 'Folder does not exist or not accesible';
$string['invalidfile'] = 'File does not exist or not accesible';
$string['idnumberexists'] = 'Idnumber already exists';
$string['shortnameexists'] = 'Shortname already exists';
$string['norecords'] = 'No records to restore';

$string['records'] = 'Records: {$a}';
$string['startrestore'] = 'Start restore';
$string['shortname'] = 'Shortname';
$string['idnumber'] = 'ID Number';
$string['users'] = 'Users';
$string['blocks'] = 'Blocks';

$string['coursenotrestored'] = 'The course could not be restored';
$string['restoresuccessful'] = 'Restore successful';
$string['restoredid'] = 'Restored Id: {$a}';

$string['examplecsv'] = 'Sample CSV file';
$string['examplecsv_help'] = 'Sample CSV file with the required format.';
