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
 * Version details
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

require_once(dirname(__FILE__) . '/lib.php');

admin_externalpage_setup('tool_bulk_backupandrestore', '', null);

$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'tool_bulk_backupandrestore'));

echo $OUTPUT->header();

$returnurl = new moodle_url('/admin/tool/bulk_backupandrestore/restore.php');

//Backup var is defined
$form = new restore_form(
  new moodle_url('/admin/tool/bulk_backupandrestore/restore.php')
);


$category_cache = [];
$folder_cache = [];

$display_form = true;
$display_table = false;
$data = [];
if ( ($formdata = $form->get_data())) {
  $iid = csv_import_reader::get_new_iid('restorecsv');
  $cir = new csv_import_reader($iid, 'restorecsv');

  $content = $form->get_file_content('restorecsv');

  $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
  $csvloaderror = $cir->get_error();

  if (!is_null($csvloaderror)) {
    print_error('csvloaderror', '', $returnurl, $csvloaderror);
  }

  $data = get_csv_data($formdata, $cir);

  if (count($data) > 0) {
    $display_form  = false;
    $display_table = true;
  }
}

if ($display_form) {
  $form->display();
}

if ($display_table) {
  if (count($data) == 0) {
    print_error('norecords', 'tool_bulk_backupandrestore', $returnurl);
  }

  $total_records = count($data);
  //Total records
  echo html_writer::tag
    (
      'p',
      get_string 
      (
        'records', 
        'tool_bulk_backupandrestore',
        $total_records
      )
    );


  //Restore button
  echo html_writer::tag(
    'p',
    html_writer::link
    (
      '#',
      get_string('startrestore', 'tool_bulk_backupandrestore'),
      [
        'class' => 'btn btn-primary',
        'id' => 'restore_courses',
        'role' => 'button',
      ]
    )
  );


  echo html_writer::start_tag('div', ['class' => 'progress d-none']); // start progress bar

  echo html_writer::tag
    (
      'div', //tag
      '', //value
      [
        'class' => 'progress-bar bg-success',
        'role' => 'progressbar',
        'aria-valuenow' => '0',
        'aria-valuemin' => '0',
        'aria-valuemax' => '100'
      ]
    );
  echo html_writer::end_tag('div'); //end progress bar

  echo html_writer::tag('div', '', ['class' => 'result']);

  $sesskey = sesskey();
  $key = md5(microtime());

  echo html_writer::start_tag(
    'table', 
    [
      'id' => 'restore-table',
      'class' => 'table table-striped',
      'data-session' => $sesskey,
      'data-key' => $key,
    ]
  );

  echo html_writer::start_tag('thead');

  echo html_writer::tag('th', get_string('category') , ['scope' => 'col']);
  echo html_writer::tag('th', get_string('name', 'tool_bulk_backupandrestore') , ['scope' => 'col']);
  echo html_writer::tag('th', get_string('shortname', 'tool_bulk_backupandrestore') , ['scope' => 'col']);
  echo html_writer::tag('th', get_string('idnumber', 'tool_bulk_backupandrestore') , ['scope' => 'col']);
  echo html_writer::tag('th', get_string('users', 'tool_bulk_backupandrestore') , ['scope' => 'col']);
  echo html_writer::tag('th', get_string('blocks', 'tool_bulk_backupandrestore') , ['scope' => 'col']);
  echo html_writer::tag('th', get_string('status', 'tool_bulk_backupandrestore') , ['scope' => 'col']);

  echo html_writer::end_tag('thead');
  $n = 1;
  foreach ($data as $record) {

    //Path and errors
    $errors = '';
    foreach ($record->errors as $msg) {
      $errors .= html_writer::tag('div', $msg, ['class' => 'text-danger']);
    }

    $attrs = [];

    if (count($record->errors) == 0) {
      $attrs = 
        [
          'id' => 'record-' . $record->id, 
          'class' => 'record-row record-row-' . $n, 
          'data-category' => $record->category,
          'data-folder' => $record->folder,
          'data-filename' => $record->filename,
          'data-name' => $record->name,
          'data-shortname' => $record->shortname,
          'data-idnumber' => $record->idnumber,
          'data-users' => $record->users,
          'data-blocks' => $record->blocks
        ];
    }

    echo html_writer::start_tag
      (
        'tr', 
        $attrs
      );
    echo html_writer::tag('td', $record->category);
    echo html_writer::tag('td', 
     html_writer::tag('div',  $record->name)
     . html_writer::tag('div', "$record->filename") . $errors,
    );
    echo html_writer::tag('td', $record->shortname);
    echo html_writer::tag('td', $record->idnumber);
    echo html_writer::tag('td', $record->users);
    echo html_writer::tag('td', $record->blocks);
    echo html_writer::tag('td', get_string('ready', 'tool_bulk_backupandrestore'), ['class' => 'status']);
    echo html_writer::end_tag('tr');
    $n++;
  }
  echo html_writer::end_tag('table');

}


$PAGE->requires->js_call_amd('tool_bulk_backupandrestore/restore', 'init');

echo $OUTPUT->footer();


function get_csv_data($formdata, $cir) {
  $columns = $cir->get_columns();

  if ( count($columns) != 8) {
    print_error('csvloaderror', '', $returnurl, get_string('invalidcolumns', 'tool_bulk_backupandrestore'));
  }

  $data = [];

  $id = 1;

  //The first line on the file is taken as the "columns" row
  if (!isset($data->containsheader)) {
    if (is_numeric($columns[0])) {
      $row = $columns;
      $obj = new Stdclass;
      $obj->id = $id;
      $obj->category = $row[0];
      $obj->folder = $row[1];
      $obj->filename = $row[2];
      $obj->name = $row[3];
      $obj->shortname = $row[4];
      $obj->idnumber = $row[5];
      $obj->users = $row[6];
      $obj->blocks = $row[7];
      $id++;

      validate_restore_record($obj);

      $data[] = $obj;
    }
  }

  $cir->init();

  while ($row = $cir->next()) {
    $obj = new Stdclass;
    $obj->id = $id;
    $obj->category = $row[0];
    $obj->folder = $row[1];
    $obj->filename = $row[2];
    $obj->name = $row[3];
    $obj->shortname = $row[4];
    $obj->idnumber = $row[5];
    $obj->users = $row[6];
    $obj->blocks = $row[7];

    $id++;

    validate_restore_record($obj);
    $data[] = $obj;
  }

  return $data;

}

function validate_restore_record(&$obj) {
  global $DB;
  global $category_cache;
  global $folder_cache;
  $obj->errors = [];

  if (!isset($obj->category) or !is_numeric($obj->category)) {
    $obj->category = 0;
    $obj->errors[] = get_string('invalidcategory', 'tool_bulk_backupandrestore');
  }

  if ($obj->category and !isset($category_cache[$obj->category])) {
    $cat = $DB->get_record('course_categories', ['id' => $obj->category]);
    if ($cat) {
      //Store category on cache
      $category_cache[$cat->id] = $cat;
    }else {
      $obj->errors[] = get_string('invalidcategory', 'tool_bulk_backupandrestore');
    }
  }

  if (!$obj->folder) {
    $obj->errors[] = get_string('invalidfolder', 'tool_bulk_backupandrestore');
  }

  if ($obj->folder and !in_array($obj->folder, $folder_cache)) {
    if (is_dir($obj->folder) and is_readable($obj->folder)) {
      $folder_cache[] = $obj->folder;
    }else {
      $obj->errors[] = get_string('invalidfolder', 'tool_bulk_backupandrestore');
    }
  }

  $path = $obj->folder . '/' . $obj->filename;
  if (!$obj->filename or !is_file($path) or !is_readable($path)) {
    $obj->errors[] = get_string('invalidfile', 'tool_bulk_backupandrestore');
  }

  if ($obj->idnumber) {
    $course = $DB->get_record('course', ['idnumber' => $obj->idnumber]);
    if ($course) {
      $obj->errors[] = get_string('idnumberexists', 'tool_bulk_backupandrestore');
    }
  }
  if ($obj->shortname) {
    $course = $DB->get_record_sql('select * from {course} where shortname=:shortname', ['shortname' => $obj->shortname]);
    if ($course) {
      $obj->errors[] = get_string('shortnameexists', 'tool_bulk_backupandrestore');
    }
  }
}
