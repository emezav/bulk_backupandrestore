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

//Page parameters
$id = optional_param('id', 0, PARAM_INT);
$backup = optional_param('backup', 0, PARAM_INT);

$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'tool_bulk_backupandrestore'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_bulk_backupandrestore'));

if (!$backup) {
  list_categories($id);
}

$PAGE->requires->js_call_amd('tool_bulk_backupandrestore/backup', 'init');

echo $OUTPUT->footer();


function list_categories($parentid) {
  global $DB;
  global $OUTPUT;
  $args = ['parent' => $parentid];
  $args['visible'] = 1;

  $parent_record = $DB->get_record('course_categories', ['id' => $parentid]);

  //Get categories from this parent
  $categories = $DB->get_records('course_categories', $args);

  if ($parent_record) {
  echo html_writer::tag('p', 
    html_writer::link
    (
      new moodle_url('/admin/tool/bulk_backupandrestore/backup.php', ['id' => $parent_record->parent]),
      get_string('back', 'tool_bulk_backupandrestore')
    )
  );

    echo $OUTPUT->heading($parent_record->name, 3);

    echo html_writer::tag(
      'p',
      html_writer::link
      (
        '#backup_form',
        get_string('backupthiscategory', 'tool_bulk_backupandrestore'),
        [
          'class' => 'btn btn-primary',
          'id' => 'backup_category',
          'data-toggle' => 'collapse',
          'role' => 'button',
          'aria-expanded' => 'false',
          'aria-controls' => 'backup_form'
         ]
      )
    );


    //Backup var is defined
    $form = new backup_category_form(
      new moodle_url('/admin/tool/bulk_backupandrestore/backup_category.php')
    );

    //Default values
    $default_data = [
      'id' => $parent_record->id
    ];

    $form->set_data($default_data);

    echo html_writer::start_tag('div', ['id' => 'backup_form', 'class' => 'collapse']);
    $form->display();
    echo html_writer::end_tag('div');

  }

  //List courses on this category
  if ($parent_record) {
    list_courses($parent_record);
  }
  if (count($categories) > 0) {
    if ($parent_record) {
      echo html_writer::tag
        (
          'p',
          get_string('categoriesof', 'tool_bulk_backupandrestore', $parent_record->name)
        );
    }else {
      echo html_writer::tag
        (
          'p',
          get_string('categories', 'tool_bulk_backupandrestore')
        );
    }
    echo html_writer::start_tag('ul', ['class' => 'list-group']);
    foreach ($categories as $category) {
      echo html_writer::tag('li', 
        html_writer::link
        (
          new moodle_url('/admin/tool/bulk_backupandrestore/backup.php', ['id' => $category->id]),
          $category->name
        ),
        ['class' => 'list-group-item']
      );
    }
    echo html_writer::end_tag('ul');

  }

}

function list_courses($category, $course_limit = 5) {
  global $DB;

  $categoryid = $category->id;

  //Select ONLY visible courses
  $sql = 'select count(*) from {course} where category = :category and visible = 1';
  $args = ['category' => $categoryid];
  $course_count = $DB->get_field_sql($sql, $args);

  $first = get_string('firstncourses', 'tool_bulk_backupandrestore', $course_limit);
  $total = get_string('total', 'tool_bulk_backupandrestore');

  if ($course_count > 0) {
    echo html_writer::tag
      (
        'p',
        "$first {$category->name} ($course_count $total)"
      );
    echo html_writer::start_tag('ul', ['class' => 'list-group']); //start course list
    $courses = $DB->get_records('course', $args, 'timecreated DESC', '*', 0, $course_limit);
    foreach ($courses as $course) {
      echo html_writer::tag('li', $course->fullname, ['class' => 'list-group-item']);
    }
    echo html_writer::end_tag('ul'); //end course list
  }


}
