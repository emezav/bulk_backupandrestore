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
 * Restore course
 *
 * @package    tool
 * @subpackage  bulk_backupandrestore
 * @copyright  2020 and onwards Erwin Meza Vega <emezav@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.5
 */

/*
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');
*/

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/outputcomponents.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/dataformatlib.php');

require_once(dirname(__FILE__) . '/lib.php');

admin_externalpage_setup('tool_bulk_backupandrestore', '', null);

//Page parameters
$session = optional_param('sesskey', '', PARAM_RAW);
$categoryId = optional_param('category', 0, PARAM_INT);
$name = optional_param('name', '', PARAM_RAW);
$shortname = optional_param('shortname', '', PARAM_RAW);
$folder = optional_param('folder', '', PARAM_RAW);
$filename = optional_param('filename', '', PARAM_RAW);
$idnumber = optional_param('idnumber', '', PARAM_RAW);
$restore_users = optional_param('restoreusers', 0, PARAM_INT);
$restore_blocks = optional_param('restoreblocks', 0, PARAM_INT);

//Unique key for bulk restore task
$key = optional_param('key', '', PARAM_RAW);

// 1 on the last restore of the task
$last = optional_param('last', '', PARAM_RAW);

//Report request
$report = optional_param('report', 0, PARAM_INT);

if (!$session or !confirm_sesskey($session) or !$key) {
  bulk_ajax_helper::response
    (
      [
        'success' => false,
        'message' => get_string('invalidsession', 'tool_bulk_backupandrestore')
      ]
    );
}


if ($report) {

  $sql = "
  SELECT 
  rst.id AS restoreid,
  rst.category,
  rst.folder,
  rst.filename,
  rst.course as courseid,
  rst.idnumber,
  rst.fullname,
  rst.shortname,
  rst.users,
  rst.blocks,
  rst.status

  FROM {bulk_course_restore} rst

  WHERE rst.session= :key
  
";

  $records = $DB->get_records_sql($sql, ['key' => $key ]);
  $columns = array(
    'restoreid' => 'RestoreID',
    'category' => 'CategoryId',
    'folder' => 'Folder',
    'filename' => 'Filename',
    'courseid' => 'Course',
    'idnumber' => 'Id Number',
    'fullname' => 'Name',
    'shortname' => 'Shortname',
    'users' => 'Users',
    'blocks' => 'Blocks',
    'status' => 'Success'
     );

  download_as_dataformat('bulk_restore_' . $key, 'csv', $columns, $records);
  exit;
}

if (!$categoryId) {
  bulk_ajax_helper::response
    (
      [
        'success' => false,
        'message' => get_string('invalidcategory', 'tool_bulk_backupandrestore')  . $folder
      ]
    );
}

$category = $DB->get_record('course_categories', ['id' => $categoryId]);
if (!$category) {
  bulk_ajax_helper::response
    (
      [
        'success' => false,
        'message' => get_string('invalidcategory', 'tool_bulk_backupandrestore')  . $folder
      ]
    );

}


if (!$folder or !is_dir($folder) or !is_readable($folder)) {
  bulk_ajax_helper::response
    (
      [
        'success' => false,
        'message' => get_string('invalidfolder', 'tool_bulk_backupandrestore')  . $folder
      ]
    );
}

$path = "$folder/$filename";

if (!$path or !is_file($path) or !is_readable($path)) {
  bulk_ajax_helper::response
    (
      [
        'success' => false,
        'message' => get_string('invalidfilename', 'tool_bulk_backupandrestore') 
      ]
    );
}

$restore_options = [
  'users' => $restore_users,
  'blocks' =>  $restore_blocks
];

$restore_result = bulk_restore_course($category, $path, $restore_options);

$message = get_string('restoresuccessful', 'tool_bulk_backupandrestore');
$status = true;
if ($restore_result === false) {
  $message = get_string('restorefailed', 'tool_bulk_backupandrestore');
  $status = false;
}


//Course was restored?
$courseId = 0;
$course = false;
if ($restore_result and is_numeric($restore_result)) {
  $courseId = $restore_result;
  $course = $DB->get_record('course', ['id' => $courseId]);
}

if (!$course) {
  bulk_ajax_helper::response
    (
      [
        'success' => false,
        'message' => get_string('coursenotrestored', 'tool_bulk_backupandrestore') 
      ]
    );

}


if ($courseId) {
  $message = get_string('restoredid', 'tool_bulk_backupandrestore', $courseId);
}

if ($name) {
  $course->fullname = $name;
}
if ($shortname) {
  $course->shortname = $shortname;
}
if ($idnumber) {
  $course->idnumber = $idnumber;
}

$DB->update_record('course', $course);


$record = new Stdclass;
$record->session = $key;
$record->timecreated = usertime(time());
$record->userid =  $USER->id;
$record->category = $course->category;
$record->course = $course->id;
$record->idnumber = $course->idnumber;
$record->fullname = $course->fullname;
$record->shortname = $course->shortname;
$record->folder = $folder;
$record->filename  = $filename;
$record->users = $restore_users;
$record->blocks = $restore_blocks;
$record->status = $status;

try {
  $DB->insert_record('bulk_course_restore', $record);
}catch (exception $ex) {
}

$result_data = '';
if ($last) {
  $result_data = html_writer::link
    (
      new moodle_url
      (
        '/admin/tool/bulk_backupandrestore/restore_course.php',
        [
          'sesskey' => $session,
          'key' => $key,
          'report' => 1
        ]
      ),
      get_string('downloadreport', 'tool_bulk_backupandrestore'),
      [ 'target' => '_downloadreport' ]
    );
}

bulk_ajax_helper::response
  (
    [
      'status' => $status,
      'message' => $message,
      'id' => $courseId,
      'session' => $session,
      'key' => $key,
      'result' => $result_data
    ]
);
