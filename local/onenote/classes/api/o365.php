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
 * @package local_o365
 * @author James McQuillan <james.mcquillan@remote-learner.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft Open Technologies, Inc. (http://msopentech.com/)
 */

namespace local_onenote\api;

class o365 extends base {
    /** @var string Base url to access API */
    const API = 'https://www.onenote.com/api/beta/me/notes';

    /**
     * Make an API call.
     *
     * @param string $httpmethod The HTTP method to use. get/post/patch/merge/delete.
     * @param string $apimethod The API endpoint/method to call.
     * @param string $params Additional paramters to include.
     * @param array $options Additional options for the request.
     * @return string The result of the API call.
     */
    public function apicall($httpmethod, $apimethod, $params = '', $options = array()) {
        global $USER;

        $httpmethod = strtolower($httpmethod);

        try {
            $o365onenoteapi = \local_o365\rest\onenote::instance_for_user($USER->id);
            return $o365onenoteapi->apicall($httpmethod, $apimethod, $params);
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get a full URL and include auth token. This is useful for associated resources: attached images, etc.
     *
     * @param string $url A full URL to get.
     * @return string The result of the request.
     */
    public function geturl($url, $options = array()) {
        global $USER;
        $o365onenoteapi = \local_o365\rest\onenote::instance_for_user($USER->id);
        return $o365onenoteapi->geturl($url, $options);
    }

    /**
     * Get the token to authenticate with OneNote.
     *
     * @return string The token to authenticate with OneNote.
     */
    public function get_token() {
        global $USER;
        $httpclient = new \local_o365\httpclient();
        $clientdata = \local_o365\oauth2\clientdata::instance_from_oidc();
        $resource = \local_o365\rest\onenote::get_resource();
        $token = \local_o365\oauth2\token::instance($USER->id, $resource, $clientdata, $httpclient);
        return $token->get_token();
    }

    /**
     * Determine whether the user is connected to OneNote.
     *
     * @return bool True if connected, false otherwise.
     */
    public function is_logged_in() {
        return true;
    }

    /**
     * Get the login url (if applicable).
     *
     * @return string The login URL.
     */
    public function get_login_url() {
        return '';
    }

    /**
     * End the connection to OneNote.
     */
    public function log_out() {
        return true;
    }

    /**
     * Return the HTML for the sign in widget for OneNote.
     * Please refer to the styles.css file for styling this widget.
     *
     * @return string HTML containing the sign in widget.
     */
    public function render_signin_widget() {
        return '';
    }
}