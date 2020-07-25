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
 * Backup course
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
$id = optional_param('id', 0, PARAM_INT);
$session = optional_param('sesskey', '', PARAM_RAW);
$outdir = optional_param('outdir', '', PARAM_RAW);
$backup_users = optional_param('backupusers', 0, PARAM_INT);
$backup_blocks = optional_param('backupblocks', 0, PARAM_INT);

//Unique key for bulk backup task
$key = optional_param('key', '', PARAM_RAW);

// 1 on the last backup of the task
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
  bk.id AS backupid,
  bk.category,
  bk.folder,
  bk.filename,
  bk.course as courseid,
  bk.idnumber,
  bk.fullname,
  bk.shortname,
  bk.users,
  bk.blocks,
  bk.status

  FROM {bulk_course_backup} bk

  WHERE bk.session= :key
  
";

  $records = $DB->get_records_sql($sql, ['key' => $key ]);
  $columns = array(
    'backupid' => 'BackupID',
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

  download_as_dataformat('bulk_backup_' . $key, 'csv', $columns, $records);
  exit;
}


if (!$outdir or !is_dir($outdir) or !is_writable($outdir)) {
  bulk_ajax_helper::response
    (
      [
        'success' => false,
        'message' => get_string('invalidoutdir', 'tool_bulk_backupandrestore') . ' ' . $outdir
      ]
    );


}

$course = $DB->get_record('course', ['id' => $id]);
if (!$course) {
  bulk_ajax_helper::response
    (
      [
        'success' => false,
        'message' => get_string('invalidcourse', 'tool_bulk_backupandrestore'),
        'result' => ''
      ]
    );
}

$backup_options = [
  'users' => $backup_users,
  'blocks' =>  $backup_blocks
];

$backup_result = bulk_backup_course($course, $outdir, $backup_options);

$result = '';
$filename = '';
if (is_string($backup_result)) {
  $filename = $backup_result;
}

$message = get_string('backupsuccessful', 'tool_bulk_backupandrestore');
$status = true;
if ($backup_result === false) {
  $message = get_string('backupfailed', 'tool_bulk_backupandrestore');
  $status = false;
}

$record = new Stdclass;
$record->session = $key;
$record->timecreated = usertime(time());
$record->userid =  $USER->id;
$record->category = $course->category;
$record->course = $course->id;
$record->idnumber = $course->idnumber;
$record->fullname = $course->fullname;
$record->shortname = $course->shortname;
$record->folder = $outdir;
$record->filename  = $filename;
$record->users = $backup_users;
$record->blocks = $backup_blocks;
$record->status = $status;

$DB->insert_record('bulk_course_backup', $record);

$result_data = '';
if ($last) {
  $result_data = html_writer::link
    (
      new moodle_url
      (
        '/admin/tool/bulk_backupandrestore/backup_course.php',
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
      'id' => $id,
      'session' => $session,
      'key' => $key,
      'outdir' => $outdir,
      'result' => $result_data
    ]
);
