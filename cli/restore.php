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
require(dirname(__FILE__) . '/../../../../config.php');

require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/coursecatlib.php');

require(dirname(__FILE__) . '/../lib.php');

// Now get cli options.
list($options, $unrecognized) = 
  cli_get_params(
    [
      'source' => '',
      'parent' => 0,
      'users' => false,
      'blocks' => false,
      'verbose' => false,
      'help' => false,
    ], 
    [
      's' => 'source',
      'p' => 'parent',
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
Perform bulk course restore from CLI category backup

Options:
-s,--source=STRING             Path of stored bulk backup
-p,--parent=ID                 ID of root category to restore
-u,--users=ture|false          Include users on restore (optional)
-b,--blocks=ture|false         Include blocks on restore (optional)
-v,--verbose=true|false        Verbose backup (optional)
-h, --help                     Print out this help.

Example:
\$cd MOODLE_INSTALL_DIR
\$sudo -u apache /usr/bin/php admin/cli/bulk_backup_and_restore/restore.php --source=/moodle/backup/ -p=1\n
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

// Do we need to store backup somewhere else?
$sourcedir = rtrim($options['source'], DIRECTORY_SEPARATOR);

if (!$sourcedir or !is_dir($sourcedir)) {
  echo $help;
  die;
}

if ($verbose) {
  ini_set('error_reporting', E_ALL);
  ini_set('display_errors', 'On');
  ini_set('display_startup_errors', 'On');
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


$parentid = $options['parent'];
$parent = false;

if ($parentid) {
 $parent = $DB->get_record('course_categories', ['id' => $parentid]);
 if (!$parent) {
   mtrace("Invalid parent category id $parentid");
   die;
 }
}

$options = [
      'blocks' => $include_blocks,
      'users' => $include_users
      ];

restore_on_dir($sourcedir, $parentid, $options, $verbose);

exit(0);

function restore_on_dir($dir, $parentid, $options, $verbose) {
  global $DB;

  $entries = scandir($dir);

  if (($key = array_search('.', $entries)) !== false) {
        unset($entries[$key]);
  }
  if (($key = array_search('..', $entries)) !== false) {
        unset($entries[$key]);
  }

  $category = false;
  if (($key = array_search('info.json', $entries)) !== false) {
    //Read info.json and attempt to create category
    $obj = json_decode(file_get_contents($dir . DIRECTORY_SEPARATOR . 'info.json'));

    //Remove from entries 
    unset($entries[$key]);

    $category = $DB->get_record('course_categories', 
      [
        'name' => $obj->name,
        'parent' => $parentid
      ]
    );

    if (!$category) {
      //Crete new category
      $obj->parent = $parentid;
      $category = coursecat::create($obj);

      if (!$category) {
        $verbose && mtrace("Could not create category $obj->name with parent $parentid");
        return;
      }
    }

    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.mbz') as $backupfile) {
      //$verbose && mtrace("Restore $backupfile into $category->name ($category->id)");
      if ($courseid = bulk_restore_course($category, $backupfile, $options, $verbose)) {
        $verbose && mtrace("Restored $backupfile with id $courseid");
      }
    }
  }

  foreach ($entries as $entry) {
    $path = $dir . DIRECTORY_SEPARATOR . $entry;
    if (is_dir($path)) {
      if ($category) {
        restore_on_dir($path, $category->id, $options, $verbose);
      }else {
        restore_on_dir($path, $parentid, $options, $verbose);
      }
    }
  }
}
