<?php

/**
 * Code fragment to define the version of the simplecertificate module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @author	   Carlos Alexandre S. da Fonseca
 * @copyright  2013 - Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */


defined('MOODLE_INTERNAL') || die();
//Date: YYYYMMDDXX where XX is moodle version
$plugin->version  = 2015061729;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2015041700;  // Requires this Moodle version (moodle 2.8.x)
$plugin->cron     = 4 * 3600;    // Period for cron to check this module (secs)
$plugin->component = 'mod_simplecertificate';
$plugin->dependencies = array();
$plugin->release  = '2.2.2';       // Human-friendly version name
//MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE
$plugin->maturity = MATURITY_STABLE;
