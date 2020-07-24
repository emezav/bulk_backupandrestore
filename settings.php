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
 * Bulk backup and restore courses
 *
 * @package    tool
 * @subpackage  bulk_backupandrestore
 * @copyright  2020 and onwards Erwin Meza Vega <emezav@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.5
 */

defined('MOODLE_INTERNAL') || die();


if ($hassiteconfig) {
  $pluginname = get_string('pluginname', 'tool_bulk_backupandrestore');

  $ADMIN->add(
    'courses', 
    new admin_externalpage
    (
      'tool_bulk_backupandrestore', //Key
      $pluginname, //String
      new moodle_url('/admin/tool/bulk_backupandrestore/index.php'), //Url
      'tool/bulk_backupandrestore:admin' //Capability
    )
  );
}
