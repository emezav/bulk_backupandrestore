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
 * Backup category
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

//Page parameters
$id = required_param('id', PARAM_INT);


$category = $DB->get_record('course_categories', ['id' => $id ]);

if (!$category) {
  echo $OUTPUT->header();
  echo $OUTPUT->notification(get_string('invalidcategory', 'tool_bulk_backupandrestore'));
  echo $OUTPUT->footer();
  die;
}



$backup_users = 0;
$backup_blocks = 0;
$outdir = 0;

$form = new backup_category_form();

if ( ($data = $form->get_data())) {
  if (isset($data->backupusers)) {
    $backup_users = $data->backupusers;
  }
  if (isset($data->backupblocks)) {
    $backup_blocks = $data->backupblocks;
  }

  if (isset($data->outdir)) {
    $outdir = $data->outdir;
  }
}

//Check for available courses
$sql = "
SELECT c.id,
       c.fullname,
       c.shortname,
       cats.id AS category,
       cats.name AS category_name
FROM {course} c 
JOIN {context} ctx on c.id = ctx.instanceid and ctx.contextlevel=50 
JOIN {course_categories} cats ON c.category = cats.id
WHERE c.category = cats.id
AND (
  cats.path LIKE '%/$id/%'
  OR cats.path LIKE '%/$id'
  )  
AND c.visible = 1";

$courses = $DB->get_records_sql($sql);

$total_courses = count($courses);

if ($total_courses == 0) {
  echo $OUTPUT->header();
  echo $OUTPUT->notification(get_string('nocoursesin', 'tool_bulk_backupandrestore', $category->name) );
  echo $OUTPUT->continue_button
    (
      new moodle_url
      (
        '/admin/tool/bulk_backupandrestore/backup.php',
        ['id' => $id]
      ),
      get_string('back')
    );
  echo $OUTPUT->footer();
  die;

}

//Check for valid out dir
if (!$outdir or !is_dir($outdir) or !is_writable($outdir)) {
  echo $OUTPUT->header();
  echo $OUTPUT->notification(get_string('invalidoutdir', 'tool_bulk_backupandrestore') . ' ' . $outdir);
  echo $OUTPUT->continue_button
    (
      new moodle_url
      (
        '/admin/tool/bulk_backupandrestore/backup.php',
        ['id' => $id]
      ),
      get_string('back')
    );
  echo $OUTPUT->footer();
  die;
}

$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'tool_bulk_backupandrestore'));

// we may need a bit of extra execution time and memory here
core_php_time_limit::raise(HOURSECS);
raise_memory_limit(MEMORY_EXTRA);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('backupcategory', 'tool_bulk_backupandrestore') . ' ' . $category->name);

//Total courses
echo html_writer::tag
  (
    'p',
    get_string 
    (
      'coursesonthiscategory', 
      'tool_bulk_backupandrestore',
      $total_courses
    )
  );


//Backup button
echo html_writer::tag(
  'p',
  html_writer::link
  (
    '#backup_form',
    get_string('startbackup', 'tool_bulk_backupandrestore'),
    [
      'class' => 'btn btn-primary',
      'id' => 'backup_category',
      'role' => 'button',
    ]
  )
);


echo html_writer::tag('p', get_string('outdir', 'tool_bulk_backupandrestore') . ' : ' . $outdir);

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
    'id' => 'course-table',
    'class' => 'table table-striped',
    'data-outdir' => $outdir,
    'data-session' => $sesskey,
    'data-key' => $key,
    'data-backupusers' => $backup_users,
    'data-backupblocks' => $backup_blocks
  ]
);

echo html_writer::start_tag('thead');

echo html_writer::tag('th', get_string('id', 'tool_bulk_backupandrestore') , ['scope' => 'col']);
echo html_writer::tag('th', get_string('category') , ['scope' => 'col']);
echo html_writer::tag('th', get_string('name', 'tool_bulk_backupandrestore') , ['scope' => 'col']);
echo html_writer::tag('th', get_string('status', 'tool_bulk_backupandrestore') , ['scope' => 'col']);

echo html_writer::end_tag('thead');
$n = 1;
foreach ($courses as $course) {
  echo html_writer::start_tag
    (
      'tr', 
      [
        'id' => 'course-' . $course->id, 
        'class' => 'course-row course-row-' . $n, 
        'data-course' => $course->id 
      ]
    );
  echo html_writer::tag('td', $course->id);
  echo html_writer::tag('td', $course->category_name);
  echo html_writer::tag('td', $course->fullname);
  echo html_writer::tag('td', get_string('ready', 'tool_bulk_backupandrestore'), ['class' => 'status']);
  echo html_writer::end_tag('tr');
  $n++;
}
echo html_writer::end_tag('table');


$PAGE->requires->js_call_amd('tool_bulk_backupandrestore/backup_category', 'init');

echo $OUTPUT->footer();

