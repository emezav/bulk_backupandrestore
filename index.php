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

admin_externalpage_setup('tool_bulk_backupandrestore', '', null);

$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'tool_bulk_backupandrestore'));

raise_memory_limit(MEMORY_HUGE);
set_time_limit(300);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_bulk_backupandrestore'));
echo html_writer::tag
  (
    'p', 
    html_writer::link
    (
      new moodle_url
      (
        '/admin/tool/bulk_backupandrestore/backup.php'
      ),
      get_string('backupcategory', 'tool_bulk_backupandrestore')
    )

  );

echo html_writer::tag
  (
    'p', 
    html_writer::link
    (
      new moodle_url
      (
        '/admin/tool/bulk_backupandrestore/restore.php'
      ),
      get_string('restorecourses', 'tool_bulk_backupandrestore')
    )

  );

echo $OUTPUT->footer();
