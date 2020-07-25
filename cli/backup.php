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
 * Bulk backups courses
 *
 * @package    tool
 * @subpackage bulk_backupandrestore
 * @copyright  2020 Erwin Meza emezav at gmail dot com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

//Assume we are on WWROOT/admin/tool/bulk_backupandrestore/cli
require(dirname(__FILE__) . '/../../../../config.php');

require_once($CFG->libdir . '/clilib.php');

require(dirname(__FILE__)  . '/../lib.php');

// Now get cli options.
list($options, $unrecognized) = 
  cli_get_params(
    [
      'all' => false,
      'destination' => '',
      'parent' => 0,
      'recursive' => true,
      'users' => false,
      'blocks' => false,
      'verbose' => false,
      'help' => false,
    ], 
    [
      'a' => 'all',
      'd' => 'destination',
      'p' => 'parent',
      'r' => 'recursive',
      'u' => 'users',
      'b' => 'blocks',
      'v' => 'verbose',
      'h' => 'help',
    ]);

if ($unrecognized) {
  $unrecognized = implode("\n  ", $unrecognized);
  cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = <<<EOL
Perform backup of a category or site

Options:
-d,--destination=STRING        Path where to store backup files.
-p,--parent=ID                 ID of root category to backup, omit for site backup
-r,--recursive=true|false      Recursive backup (optional).
-u,--users=true|false          Include users on course backups (optional).
-b,--blocks=true|false         Include blocks on course backups (optional).
-v,--verbose=true|false        Verbose backup (optional).
-h, --help                     Print out this help.

Example:
\$cd MOODLE_INSTALL_DIR
#backup all courses con category 1, without users, without blocks, recursive follow categories
\$sudo -u apache /usr/bin/php admin/tool/bulk_backupandrestore/cli/backup.php --destination=/moodle/backup/ -p=1 -u=false -b=false -r=true\n
EOL;


if ($options['help']) {
  echo $help;
  die;
}

$verbose = $options['verbose'];
if (preg_match('/t|true/i', $verbose)) {
  $verbose = true;
}else {
  $verbose = false;
}

$recursive = $options['recursive'];
if (preg_match('/t|true/i', $recursive)) {
  $recursive = true;
}else {
  $recursive = false;
}

$include_all = $options['all'];
if (preg_match('/t|true/i', $include_all)) {
  $include_all = true;
}else {
  $include_all = false;
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

// Do we need to store backup somewhere else?
$destination = rtrim($options['destination'], DIRECTORY_SEPARATOR);

if (!$destination) {
  echo $help;
  die;
}

if (!empty($destination)) {
  //Attempt to create destination folder
  if (!is_dir($destination)) {
    @mkdir($destination);
  }
  if (!is_dir($destination) || !is_writable($destination)) {
    mtrace("Destination directory does not exists or not writable.");
    die;
  }
}

if ($verbose) {
  ini_set('error_reporting', E_ALL);
  ini_set('display_errors', 'On');
  ini_set('display_startup_errors', 'On');
}

$parentid = $options['parent'];
$parent = false;

if ($parentid) {
 $parent = $DB->get_record('course_categories', ['id' => $parentid]);
 if (!$parent) {
   mtrace("Invalid parent category id $parentid");
   die;
 }
}

$args = ['parent' => $parentid];
if (!$include_all){
  $args['visible'] = true;
}

$jobs = [];

if ($parent) {
  //Enqueue parent category
    $job = new stdClass;
    $job->category = $parent;
    $job->dir = $destination;

    $jobs[] = $job;
  
}else {

  //Full site backup, enqueue root categories
  $records = $DB->get_records('course_categories', $args);
  foreach ($records as $category) {

    $job = new stdClass;
    $job->category = $category;

    $dir = $destination . DIRECTORY_SEPARATOR . $category->id . '_' . rtrim(remove_accents(shorten_text($category->name, 20, false, '')));

    if (is_dir($dir)) {
      $verbose && mtrace("Warning! directory $dir already exists");
      continue;
    }

    if (!mkdir($dir, 0755)) {
      $verbose && mtrace("Could not create category directory $dir");
      continue;
    }

    $job->dir = $dir;

    $jobs[] = $job;
  }
}

while (!empty($jobs)) {
  $job = array_shift($jobs);


  $category = $job->category;
  $categoryid =  $job->category->id;

  
  $dir = rtrim($job->dir, DIRECTORY_SEPARATOR);

  //Save category info
  $info = new stdclass;
  $info->name = $category->name;

  $fp = fopen($dir . DIRECTORY_SEPARATOR . 'info.json', 'w');
  fwrite($fp, json_encode($info));
  fclose($fp);

  $verbose && mtrace("$category->id $category->name:");

  /* Backup courses */
  $args = ['category' => $categoryid];

  if (!$include_all) {
    $args['visible'] = true;
  }

  $courses = $DB->get_records('course', $args);
  foreach ($courses as $course) {

	  $filename = $course->id . '_' .  
      rtrim(
        remove_accents(
          trim(
            shorten_text(
              $course->shortname, 20, false, ''
            )
          )
        )
      ) . '.mbz';


    //Perform backup
    $result = bulk_backup_course(
      $course, 
      $dir, 
      [
      'blocks' => $include_blocks,
      'users' => $include_users, 
      'filename' => $filename
      ], $verbose
    );

    if (!$result) {
      $verbose && mtrace("Error performing backup on ({$course->id}) $course->fullname)");
      if (file_exists("$dir/$filename")) {
        @unlink("$dir/$filename");
      }
    }else {
      $verbose && mtrace(" +  ($course->id) $course->fullname => $dir/$filename");
    }
  }

  if (!$recursive) {
    //Skip if not recursive backup
    continue;
  }

  /* Enqueue child categories */
  $args = ['parent' => $category->id];
  if (!$include_all){
    $args['visible'] = true;
  }
  $childs =  $DB->get_records('course_categories', $args);
  foreach ($childs as $child) {

	  $child_dir = $dir . 
		  DIRECTORY_SEPARATOR . 
		  $child->id . '_' . 
		  remove_accents(
        trim(
          shorten_text(
            $child->name, 20, false, ''
          )
        )
      );

    if (!mkdir($child_dir, 0755)) {
      $verbose && mtrace("Could not create category directory $child_dir");
      continue;
    }

    $job = new stdClass;
    $job->category = $child;
    $job->dir = $child_dir;

    $jobs[] = $job;
  }
}

exit(0);
