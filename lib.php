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

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/csvlib.class.php');


/**
 * Backup category form
 *
 * @copyright  2020 and onwards Erwin Meza Vega <emezav@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_category_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('backupcategory', 'tool_bulk_backupandrestore'));

        $mform->addElement('text', 'outdir', get_string('outdir', 'tool_bulk_backupandrestore'), ['size' => 50]);
        $mform->addHelpButton('outdir', 'outdir', 'tool_bulk_backupandrestore');
        $mform->setType('outdir', PARAM_PATH);

        $mform->addElement('checkbox', 'backupusers', get_string('backupusers', 'tool_bulk_backupandrestore'));
        $mform->addHelpButton('backupusers', 'backupusers', 'tool_bulk_backupandrestore');
        $mform->setType('backupusers', PARAM_INT);

        $mform->addElement('checkbox', 'backupblocks', get_string('backupblocks', 'tool_bulk_backupandrestore'));
        $mform->addHelpButton('backupblocks', 'backupblocks', 'tool_bulk_backupandrestore');
        $mform->setType('backupblocks', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(false, get_string('backupcategory', 'tool_bulk_backupandrestore'));
    }
}

/**
 * Restore courses form
 *
 * @copyright  2020 and onwards Erwin Meza Vega <emezav@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_form extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('restorecourses', 'tool_bulk_backupandrestore'));

        $url = new moodle_url('/admin/tool/bulk_backupandrestore/example.csv');
        $link = html_writer::link($url, 'example.csv');
        $mform->addElement('static', 'examplecsv', get_string('examplecsv', 'tool_bulk_backupandrestore'), $link);
        $mform->addHelpButton('examplecsv', 'examplecsv', 'tool_bulk_backupandrestore');

        $mform->addElement('filepicker', 'restorecsv', get_string('file'), null, ['accepted_types' => '.csv']);
        $mform->addRule('restorecsv', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_bulk_backupandrestore'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_bulk_backupandrestore'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $mform->addElement('checkbox', 'containsheader', get_string('containsheader', 'tool_bulk_backupandrestore'));
        $mform->addHelpButton('containsheader', 'containsheader', 'tool_bulk_backupandrestore');

        
        $this->add_action_buttons(false, get_string('restore', 'tool_bulk_backupandrestore'));
    }
}

class bulk_ajax_helper {
  /* Editable responses */
  static function error($message = null) {
    http_response_code(400);
    if ($message) {
      echo $message;
    }
    die;
  }

  static function response($data = null) {
    http_response_code(200);
    if ($data) {
      echo json_encode($data);
    }
    die;
  }
}

/**
 * Performs a course backup
 * @param stdclass course to backup
 * @param string destination folder or empty to leave on backup file area
 * @param array options e.g. [ 'blocks' => false, 
 *                              'users' => false, 
 *                              'anonymize' => false, 
 *                              'filename' => 'mybackup.mbz' ]
 * @param bool verbose output
 *
 * @return course filename or false if error
 *
 */
function bulk_backup_course($course, $destination='', $backup_options = [], $verbose = false) {

	global $CFG;


	require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

	$admin = get_admin();
	if (!$admin) {
		$verbose && mtrace("Error: No admin account was found");
		return false;
	}

	//Create backup controller
	$bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
		backup::INTERACTIVE_YES, backup::MODE_GENERAL, $admin->id);

	$filename = '';

	foreach ($backup_options as $option => $value) {
		//$verbose && mtrace("Setting $option to $value");
		if ($option == 'filename') {
			$filename = $value;
		}
		$bc->get_plan()->get_setting($option)->set_value($value);
	}


	//Assign default filename if required
	if (!isset($backup_options['filename']) || !$backup_options['filename']) {

		$format = $bc->get_format();

		$type = $bc->get_type();

		$id = $bc->get_id();

		$users = $bc->get_plan()->get_setting('users')->get_value();

		$anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();

		$filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);

		$bc->get_plan()->get_setting('filename')->set_value($filename);
	}

	// Execution.
	$bc->finish_ui();

	$bc->execute_plan();

	//Get results
	$results = $bc->get_results();

	$file = $results['backup_destination']; // May be empty if file already moved to target location.

	$result = false;

	if ($destination) {
		if ($file) {
			//$verbose && mtrace("Writing " . $destination.'/'.$filename);
			if ($file->copy_content_to($destination.'/'.$filename)) {
				$file->delete();
				//$verbose && mtrace("Backup completed.");
				$result = $filename;
			} else {
				$verbose && mtrace("Destination directory does not exist or is not writable. Leaving the backup in the course backup file area.");
				$result =  false;
			}
		}
	}else {
		$verbose && mtrace("Backup saved to course file area");
		$result =  true;
	}
	$bc->destroy();

	return $result;

}


/** 
 * Performs a course restore
 * @param stdclass category to restore course into
 * @param string path to backup file
 * @param array options e.g. [ 'blocks' => false, 
 *                              'users' => false, 
 *                              'anonymize' => false]
 * @param bool verbose output
 */
function bulk_restore_course($category, $backupfile, $restore_options = [], $verbose = false) {
	global $CFG;
	global $DB;


	require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

	$admins = get_admins();

	if (!$admins or !is_array($admins) or count($admins) == 0) {
		$verbose && mtrace("Error: No admin account was found");
		return false;
	}

  $admin = array_shift($admins);

	//Create temp backup dir
	$backupdir = uniqid();

	//Create path
	$path = $CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . $backupdir;

	/** @var $fp file_packer */
	$fp = get_file_packer('application/vnd.moodle.backup');

	$fp->extract_to_pathname($backupfile, $path);

	$transaction = $DB->start_delegated_transaction();

	// Create new course.
	$courseid = restore_dbops::create_new_course('', '', $category->id);

	try {
		$controller = new restore_controller($backupdir, $courseid,
			backup::INTERACTIVE_NO, backup::MODE_GENERAL, $admin->id,
			backup::TARGET_NEW_COURSE);

		foreach ($restore_options as $option => $value) {
			$verbose && mtrace("Setting $option to $value");
			$controller->get_plan()->get_setting($option)->set_value($value);
		}

		if ($controller->get_status() == backup::STATUS_REQUIRE_CONV) {
			$controller->convert();
		}

		//Execute restore
		$controller->execute_precheck();

		$controller->execute_plan();

		$transaction->allow_commit();

		$controller->destroy();

		fulldelete($path);

		return $courseid;
	}catch (exception $ex) {

		if (is_dir($path)) {
			fulldelete($path);
		}

		$transaction->rollback($ex);
		$verbose && mtrace($ex->getMessage());
		return false;
	} 
}

if (!function_exists('seems_utf8')) {
  function seems_utf8($str) {
    mbstring_binary_safe_encoding();
    $length = strlen($str);
    reset_mbstring_encoding();
    for ($i=0; $i < $length; $i++) {
      $c = ord($str[$i]);
      if ($c < 0x80) $n = 0; # 0bbbbbbb
      elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
      elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
      elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
      elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
      elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
      else return false; # Does not match any model
      for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
        if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
          return false;
      }
    }
    return true;
  }

}

/**
 * Converts all accent characters to ASCII characters.
 *
 * If there are no accent characters, then the string given is just returned.
 *
 * Taken from Wordpress
 *
 * @param string $string Text that might have accent characters
 * @return string Filtered string with replaced "nice" characters.
 */
if (!function_exists('remove_accents')) {
  function remove_accents($string) {
    if ( !preg_match('/[\x80-\xff]/', $string) )
      return $string;

    if (seems_utf8($string)) {
      $chars = array(
        // Decompositions for Latin-1 Supplement
        chr(194).chr(170) => 'a', chr(194).chr(186) => 'o',
        chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
        chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
        chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
        chr(195).chr(134) => 'AE',chr(195).chr(135) => 'C',
        chr(195).chr(136) => 'E', chr(195).chr(137) => 'E',
        chr(195).chr(138) => 'E', chr(195).chr(139) => 'E',
        chr(195).chr(140) => 'I', chr(195).chr(141) => 'I',
        chr(195).chr(142) => 'I', chr(195).chr(143) => 'I',
        chr(195).chr(144) => 'D', chr(195).chr(145) => 'N',
        chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
        chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
        chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
        chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
        chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
        chr(195).chr(158) => 'TH',chr(195).chr(159) => 's',
        chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
        chr(195).chr(162) => 'a', chr(195).chr(163) => 'a',
        chr(195).chr(164) => 'a', chr(195).chr(165) => 'a',
        chr(195).chr(166) => 'ae',chr(195).chr(167) => 'c',
        chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
        chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
        chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
        chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
        chr(195).chr(176) => 'd', chr(195).chr(177) => 'n',
        chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
        chr(195).chr(180) => 'o', chr(195).chr(181) => 'o',
        chr(195).chr(182) => 'o', chr(195).chr(184) => 'o',
        chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
        chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
        chr(195).chr(189) => 'y', chr(195).chr(190) => 'th',
        chr(195).chr(191) => 'y', chr(195).chr(152) => 'O',
        // Decompositions for Latin Extended-A
        chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
        chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
        chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
        chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
        chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
        chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
        chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
        chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
        chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
        chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
        chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
        chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
        chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
        chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
        chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
        chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
        chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
        chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
        chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
        chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
        chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
        chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
        chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
        chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
        chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
        chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
        chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
        chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
        chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
        chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
        chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
        chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
        chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
        chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
        chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
        chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
        chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
        chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
        chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
        chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
        chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
        chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
        chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
        chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
        chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
        chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
        chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
        chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
        chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
        chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
        chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
        chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
        chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
        chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
        chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
        chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
        chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
        chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
        chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
        chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
        chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
        chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
        chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
        chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
        // Decompositions for Latin Extended-B
        chr(200).chr(152) => 'S', chr(200).chr(153) => 's',
        chr(200).chr(154) => 'T', chr(200).chr(155) => 't',
        // Euro Sign
        chr(226).chr(130).chr(172) => 'E',
        // GBP (Pound) Sign
        chr(194).chr(163) => '',
        // Vowels with diacritic (Vietnamese)
        // unmarked
        chr(198).chr(160) => 'O', chr(198).chr(161) => 'o',
        chr(198).chr(175) => 'U', chr(198).chr(176) => 'u',
        // grave accent
        chr(225).chr(186).chr(166) => 'A', chr(225).chr(186).chr(167) => 'a',
        chr(225).chr(186).chr(176) => 'A', chr(225).chr(186).chr(177) => 'a',
        chr(225).chr(187).chr(128) => 'E', chr(225).chr(187).chr(129) => 'e',
        chr(225).chr(187).chr(146) => 'O', chr(225).chr(187).chr(147) => 'o',
        chr(225).chr(187).chr(156) => 'O', chr(225).chr(187).chr(157) => 'o',
        chr(225).chr(187).chr(170) => 'U', chr(225).chr(187).chr(171) => 'u',
        chr(225).chr(187).chr(178) => 'Y', chr(225).chr(187).chr(179) => 'y',
        // hook
        chr(225).chr(186).chr(162) => 'A', chr(225).chr(186).chr(163) => 'a',
        chr(225).chr(186).chr(168) => 'A', chr(225).chr(186).chr(169) => 'a',
        chr(225).chr(186).chr(178) => 'A', chr(225).chr(186).chr(179) => 'a',
        chr(225).chr(186).chr(186) => 'E', chr(225).chr(186).chr(187) => 'e',
        chr(225).chr(187).chr(130) => 'E', chr(225).chr(187).chr(131) => 'e',
        chr(225).chr(187).chr(136) => 'I', chr(225).chr(187).chr(137) => 'i',
        chr(225).chr(187).chr(142) => 'O', chr(225).chr(187).chr(143) => 'o',
        chr(225).chr(187).chr(148) => 'O', chr(225).chr(187).chr(149) => 'o',
        chr(225).chr(187).chr(158) => 'O', chr(225).chr(187).chr(159) => 'o',
        chr(225).chr(187).chr(166) => 'U', chr(225).chr(187).chr(167) => 'u',
        chr(225).chr(187).chr(172) => 'U', chr(225).chr(187).chr(173) => 'u',
        chr(225).chr(187).chr(182) => 'Y', chr(225).chr(187).chr(183) => 'y',
        // tilde
        chr(225).chr(186).chr(170) => 'A', chr(225).chr(186).chr(171) => 'a',
        chr(225).chr(186).chr(180) => 'A', chr(225).chr(186).chr(181) => 'a',
        chr(225).chr(186).chr(188) => 'E', chr(225).chr(186).chr(189) => 'e',
        chr(225).chr(187).chr(132) => 'E', chr(225).chr(187).chr(133) => 'e',
        chr(225).chr(187).chr(150) => 'O', chr(225).chr(187).chr(151) => 'o',
        chr(225).chr(187).chr(160) => 'O', chr(225).chr(187).chr(161) => 'o',
        chr(225).chr(187).chr(174) => 'U', chr(225).chr(187).chr(175) => 'u',
        chr(225).chr(187).chr(184) => 'Y', chr(225).chr(187).chr(185) => 'y',
        // acute accent
        chr(225).chr(186).chr(164) => 'A', chr(225).chr(186).chr(165) => 'a',
        chr(225).chr(186).chr(174) => 'A', chr(225).chr(186).chr(175) => 'a',
        chr(225).chr(186).chr(190) => 'E', chr(225).chr(186).chr(191) => 'e',
        chr(225).chr(187).chr(144) => 'O', chr(225).chr(187).chr(145) => 'o',
        chr(225).chr(187).chr(154) => 'O', chr(225).chr(187).chr(155) => 'o',
        chr(225).chr(187).chr(168) => 'U', chr(225).chr(187).chr(169) => 'u',
        // dot below
        chr(225).chr(186).chr(160) => 'A', chr(225).chr(186).chr(161) => 'a',
        chr(225).chr(186).chr(172) => 'A', chr(225).chr(186).chr(173) => 'a',
        chr(225).chr(186).chr(182) => 'A', chr(225).chr(186).chr(183) => 'a',
        chr(225).chr(186).chr(184) => 'E', chr(225).chr(186).chr(185) => 'e',
        chr(225).chr(187).chr(134) => 'E', chr(225).chr(187).chr(135) => 'e',
        chr(225).chr(187).chr(138) => 'I', chr(225).chr(187).chr(139) => 'i',
        chr(225).chr(187).chr(140) => 'O', chr(225).chr(187).chr(141) => 'o',
        chr(225).chr(187).chr(152) => 'O', chr(225).chr(187).chr(153) => 'o',
        chr(225).chr(187).chr(162) => 'O', chr(225).chr(187).chr(163) => 'o',
        chr(225).chr(187).chr(164) => 'U', chr(225).chr(187).chr(165) => 'u',
        chr(225).chr(187).chr(176) => 'U', chr(225).chr(187).chr(177) => 'u',
        chr(225).chr(187).chr(180) => 'Y', chr(225).chr(187).chr(181) => 'y',
        // Vowels with diacritic (Chinese, Hanyu Pinyin)
        chr(201).chr(145) => 'a',
        // macron
        chr(199).chr(149) => 'U', chr(199).chr(150) => 'u',
        // acute accent
        chr(199).chr(151) => 'U', chr(199).chr(152) => 'u',
        // caron
        chr(199).chr(141) => 'A', chr(199).chr(142) => 'a',
        chr(199).chr(143) => 'I', chr(199).chr(144) => 'i',
        chr(199).chr(145) => 'O', chr(199).chr(146) => 'o',
        chr(199).chr(147) => 'U', chr(199).chr(148) => 'u',
        chr(199).chr(153) => 'U', chr(199).chr(154) => 'u',
        // grave accent
        chr(199).chr(155) => 'U', chr(199).chr(156) => 'u',
      );

      // Used for locale-specific rules
      //Locale de_DE
      $chars[ chr(195).chr(132) ] = 'Ae';
      $chars[ chr(195).chr(164) ] = 'ae';
      $chars[ chr(195).chr(150) ] = 'Oe';
      $chars[ chr(195).chr(182) ] = 'oe';
      $chars[ chr(195).chr(156) ] = 'Ue';
      $chars[ chr(195).chr(188) ] = 'ue';
      $chars[ chr(195).chr(159) ] = 'ss';
      //Locale da_DK
      $chars[ chr(195).chr(134) ] = 'Ae';
      $chars[ chr(195).chr(166) ] = 'ae';
      $chars[ chr(195).chr(152) ] = 'Oe';
      $chars[ chr(195).chr(184) ] = 'oe';
      $chars[ chr(195).chr(133) ] = 'Aa';
      $chars[ chr(195).chr(165) ] = 'aa';
      $string = strtr($string, $chars);
    } else {
      // Assume ISO-8859-1 if not UTF-8
      $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
        .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
        .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
        .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
        .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
        .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
        .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
        .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
        .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
        .chr(252).chr(253).chr(255);

      $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

      $string = strtr($string, $chars['in'], $chars['out']);
      $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
      $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
      $string = str_replace($double_chars['in'], $double_chars['out'], $string);
    }

    return $string;
  }

}

if (!class_exists('exec_timer')) {
class exec_timer {
  private $marks = [];
  private $default_mark;
  function __construct() {
    //create default mark
    $this->default_mark = uniqid();
    $this->marks[$this->default_mark] = microtime(true);
  }

  public function mark($label) {
    $this->marks[$label] = microtime(true);
  }

  public function elapsed($label = '') {
    if (!$label or !isset($this->marks[$label])) {
      return (microtime(true) - $this->marks[$this->default_mark]);
    }
    return (microtime(true) - $this->marks[$label]);
  }
}

}


if (!function_exists('mbstring_binary_safe_encoding')) {
  
  function mbstring_binary_safe_encoding( $reset = false ) {
    static $encodings = array();
    static $overloaded = null;
  
    if ( is_null( $overloaded ) )
      $overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );
  
    if ( false === $overloaded )
      return;
  
    if ( ! $reset ) {
      $encoding = mb_internal_encoding();
      array_push( $encodings, $encoding );
      mb_internal_encoding( 'ISO-8859-1' );
    }
  
    if ( $reset && $encodings ) {
      $encoding = array_pop( $encodings );
      mb_internal_encoding( $encoding );
    }
  }
}

if (!function_exists('reset_mbstring_encoding')) {

  function reset_mbstring_encoding() {
    mbstring_binary_safe_encoding( true );
  }
}
