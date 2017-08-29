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

require_once($CFG->dirroot . '/theme/bootstrapbase/renderers.php');

/**
 * truenorthlogic_default core renderers.
 *
 * @package    theme_truenorthlogic_default
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_truenorthlogic_default_core_renderer extends theme_bootstrapbase_core_renderer {

    /**
     * Either returns the parent version of the header bar, or a version with the logo replacing the header.
     *
     * @since Moodle 2.9
     * @param array $headerinfo An array of header information, dependant on what type of header is being displayed. The following
     *                          array example is user specific.
     *                          heading => Override the page heading.
     *                          user => User object.
     *                          usercontext => user context.
     * @param int $headinglevel What level the 'h' tag will be.
     * @return string HTML for the header bar.
     */
    public function context_header($headerinfo = null, $headinglevel = 1) {

        if ($this->should_render_logo($headinglevel)) {
            return html_writer::tag('div', '', array('class' => 'logo'));
        }
        return parent::context_header($headerinfo, $headinglevel);
    }
    
   /*Adding Footer Activity Links originally from NCDP*/
    public function footer() {
		return $this->x_get_footernav().parent::footer();
	}
   /*end Footer Activity Links*/


    /**
     * Determines if we should render the logo.
     *
     * @param int $headinglevel What level the 'h' tag will be.
     * @return bool Should the logo be rendered.
     */
    protected function should_render_logo($headinglevel = 1) {
        global $PAGE;

        // Only render the logo if we're on the front page or login page
        // and the theme has a logo.
        if ($headinglevel == 1 && !empty($this->page->theme->settings->logo)) {
            if ($PAGE->pagelayout == 'frontpage' || $PAGE->pagelayout == 'login') {
                return true;
            }
        }

        return false;
    }
    
   /*Footer Activity Links originally from NCDP*/
        protected function x_get_footernav() {
    	global $CFG, $USER, $THEME;
    	
    	if ($this->page->context->contextlevel == 70){// && $this->page->course->format == "topics") {
	    	$content = '<div id="footernav">';
	    	$modinfo = get_fast_modinfo($this->page->course);
	    	$mods = $modinfo->get_cms();
	    	$currentmodid = $this->page->cm->id;
	    	$orderedmods = array(); //Build this array of modules in the course order
	    	$totalmods = 0; //Increment when adding a mod to above array
	    	foreach ($mods as $key => $value) {
	    		$module = array('name'=>$value->name, 'modname'=>$value->modname, 'id'=>$key);
	    		if ($value->visible == 1 && $value->modname !== 'label') {
		    		$orderedmods[] = $module;
		    		if ($currentmodid == $key) { //This is the current mod
		    			$currentmodindex = $totalmods;
		    		}
		    		$totalmods ++;
	    		}
	    	}
	    	
			if ($currentmodindex > 0) {//Not first item
	    		$mod = (object)$orderedmods[$currentmodindex-1];
	    		$content .= '<div id="footernav_prev"><a class="footernavlink" title="'.$mod->name.'" href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'
	    					 .'&lt; Previous: <span class="modname">'.$this->truncate_string($mod->name,24).'</span></a></div>';
	    	}
	    	if ($currentmodindex < ($totalmods - 1)) { //Not Last Item
	    		$mod = (object)$orderedmods[$currentmodindex+1];
	    		$content .= '<div id="footernav_next"><a class="footernavlink" title="'.$mod->name.'" href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'
	    				 .'Next: <span class="modname">'.$this->truncate_string($mod->name,24).'</span> &gt;</a></div>';
	    	}
	    	$content .= '</div>';
	    	return $content;
    	} else {
    		return null;
    	}
    }
    
    /**
     * Truncates a string
     * @param string $text string to truncate
     * @param integer $nbrChar number of characters allowed
     * @param string $append (optional) add string to the end
     */
    
    /*Footer Activity Links originally from NCDP*/
	private function truncate_string($text, $nbrChar, $append='...') {
	     if(strlen($text) > $nbrChar) {
	          $text = substr($text, 0, $nbrChar);
	          $text .= $append;
	     }
	     return $text;
	} 
    /** End Foot Activity Links **/
    
    
}
