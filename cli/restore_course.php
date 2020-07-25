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
 * Bulk restore courses
 *
 * @package    tool
 * @subpackage bulk_backupandrestore
 * @copyright  2020 Erwin Meza Vega emezav at gmail dot com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('CLI_SCRIPT', 1);

//Assume we are on WWROOT/admin/tool/bulk_backupandrestore/cli
//
require(realpath(__DIR__.'/../../../../config.php'));
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require(realpath(__DIR__.'/../lib.php'));

// Now get cli options.
list($options, $unrecognized) = cli_get_params([
  'categoryid' =>false,
  'file' => '',
  'name' => '',
  'shortname' => '',
  'idnumber' => '',
  'users' => false,
  'blocks' => false,
  'help' => false,
  'verbose' => false,
], 
[
  'h' => 'help',
  'c' => 'categoryid',
  'f' => 'file',
  'n' => 'name',
  's' => 'shortname',
  'i' => 'idnumber',
  'u' => 'users',
  'b' => 'blocks',
  'v' => 'verbose'
]);

if ($unrecognized) {
  $unrecognized = implode("\n  ", $unrecognized);
  cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = <<<EOL
Perform a course restore from a MBZ file.

Options:
-c,--categoryid=INTEGER              Category ID to restore the course
-f,--file=PATH_TO_BACKUPFILE         Path to mbz file
-n,--name=STRING                 New course full name (optional)
-s,--shortname=STRING                New course short name (optional)
-i,--idnumber=STRING                 New course ID number (optional)
-u,--users=true|false                Include users on restore (optional)
-b,--blocks=true|false               Include blocks on restore (optional)
-v,--verbose=true|false              Verbose restore (optional)
-h, --help                           Print out this help.

Example:
sudo -u apache /usr/bin/php PATH_TO_THIS_FILE -c=2 --f=PATH_TO_BACKUPFILE [--name='FULLNAME'] [--shortname='SHORTNAME']/\n
\$cd MOODLE_INSTALL_DIR
\$sudo -u apache /usr/bin/php admin/tool/bulk_backupandrestore/cli/restore_course.php -c=1 -f=/tmp/backup.mbz -n="Course fullname" -s="CourseFN" -u=false -b=false\n
EOL;

$categoryid = $options['categoryid'];
$backupfile = $options['file'];
$fullname = $options['name'];
$shortname = $options['shortname'];
$idnumber = $options['idnumber'];

if ($options['help'] || !$categoryid || !$backupfile) {
  echo $help;
  die;
}

if (!is_readable($backupfile)) {
  echo "$backupfile not readable, aborting.";
  echo $help;
  die;
}

$category = $DB->get_record('course_categories', ['id' => $categoryid]);

if (!$category) {
  echo "Category $categoryid not found.";
  echo $help;
  die;
}

$include_users = $options['users'];
if (preg_match('/t|true/i', $include_users)) {
  $include_users = true;
}else {
  $include_users = false;
}

$include_blocks = $options['blocks'];
if (preg_match('/t|true/i', $include_blocks)) {
  $include_blocks = true;
}else {
  $include_blocks = false;
}


$verbose = $options['verbose'];

if (preg_match('/t|true/i', $verbose)) {
  $verbose = true;
}else {
  $verbose = false;
}

//Enable debugging if verbose is enabled
if ($verbose) {
  ini_set('error_reporting', E_ALL);
  ini_set('display_errors', 'On');
  ini_set('display_startup_errors', 'On');
}


$verbose && cli_heading('Performing restore...');

//Create timer
$timer = new exec_timer();


$options = [
      'blocks' => $include_blocks,
      'users' => $include_users
      ];

$result = bulk_restore_course($category, $backupfile, $options, $verbose);

if ($result) {
  mtrace("New course id: $result");
  //Update fullname shortname or idnumber if required
  if ($fullname or $shortname or $idnumber) {
    $course = $DB->get_record('course', ['id' => $result]);
    if (!$course) {
      mtrace("New course ID $result not found!");
      die;
    }

    //Validate existing idnumber
    if ($idnumber) {
      $c = $DB->get_record('course', ['idnumber' => $idnumber]);
      if ($c and $c->id != $course->id) {
        mtrace("Warning! course {$c->id} has ID number {$idnumber}, ID number for restore course {$course->id} not set.");
      }else {
        $course->idnumber = $idnumber;
      }
    }

    if ($fullname) {
      $course->fullname = $fullname;
    }

    if ($shortname) {
      $course->shortname = $shortname;
    }

    $DB->update_record('course', $course);
  }
}

$verbose && mtrace('Restore took ' . $timer->elapsed());

exit(0);
