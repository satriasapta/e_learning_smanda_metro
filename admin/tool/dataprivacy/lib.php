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
 * Data privacy plugin library
 * @package   tool_dataprivacy
 * @copyright 2018 onwards Jun Pataleta
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_user\output\myprofile\tree;

defined('MOODLE_INTERNAL') || die();

/**
 * Add nodes to myprofile page.
 *
 * @param tree $tree Tree object
 * @param stdClass $user User object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function tool_dataprivacy_myprofile_navigation(tree $tree, $user, $iscurrentuser, $course)
{
    global $PAGE, $USER;

    // Get the Privacy and policies category.
    if (!array_key_exists('privacyandpolicies', $tree->__get('categories'))) {
        // Create the category.
        $categoryname = get_string('privacyandpolicies', 'admin');
        $category = new core_user\output\myprofile\category('privacyandpolicies', $categoryname, 'contact');
        $tree->add_category($category);
    } else {
        // Get the existing category.
        $category = $tree->__get('categories')['privacyandpolicies'];
    }

    // Contact data protection officer link.
    if (\tool_dataprivacy\api::can_contact_dpo() && $iscurrentuser) {
        $renderer = $PAGE->get_renderer('tool_dataprivacy');
        $content = $renderer->render_contact_dpo_link();
        $node = new core_user\output\myprofile\node('privacyandpolicies', 'contactdpo', null, null, null, $content);
        $category->add_node($node);

        // Require our Javascript module to handle contact DPO interaction.
        $PAGE->requires->js_call_amd('tool_dataprivacy/contactdpo', 'init');

        $url = new moodle_url('/admin/tool/dataprivacy/mydatarequests.php');
        $node = new core_user\output\myprofile\node(
            'privacyandpolicies',
            'datarequests',
            get_string('datarequests', 'tool_dataprivacy'),
            null,
            $url
        );
        $category->add_node($node);

        // Check if the user has an ongoing data export request.
        $hasexportrequest = \tool_dataprivacy\api::has_ongoing_request($user->id, \tool_dataprivacy\api::DATAREQUEST_TYPE_EXPORT);
        // Show data export link only if the user doesn't have an ongoing data export request and has permission
        // to download own data.
        if (!$hasexportrequest && \tool_dataprivacy\api::can_create_data_download_request_for_self()) {
            $exportparams = ['type' => \tool_dataprivacy\api::DATAREQUEST_TYPE_EXPORT];
            $exporturl = new moodle_url('/admin/tool/dataprivacy/createdatarequest.php', $exportparams);
            $exportnode = new core_user\output\myprofile\node(
                'privacyandpolicies',
                'requestdataexport',
                get_string('requesttypeexport', 'tool_dataprivacy'),
                null,
                $exporturl
            );
            $category->add_node($exportnode);
        }

        // Check if the user has an ongoing data deletion request.
        $hasdeleterequest = \tool_dataprivacy\api::has_ongoing_request($user->id, \tool_dataprivacy\api::DATAREQUEST_TYPE_DELETE);
        // Show data deletion link only if the user doesn't have an ongoing data deletion request and has permission
        // to create data deletion request.
        if (!$hasdeleterequest && \tool_dataprivacy\api::can_create_data_deletion_request_for_self()) {
            $deleteparams = ['type' => \tool_dataprivacy\api::DATAREQUEST_TYPE_DELETE];
            $deleteurl = new moodle_url('/admin/tool/dataprivacy/createdatarequest.php', $deleteparams);
            $deletenode = new core_user\output\myprofile\node(
                'privacyandpolicies',
                'requestdatadeletion',
                get_string('deletemyaccount', 'tool_dataprivacy'),
                null,
                $deleteurl
            );
            $category->add_node($deletenode);
        }
    }

    // A returned 0 means that the setting was set and disabled, false means that there is no value for the provided setting.
    $showsummary = get_config('tool_dataprivacy', 'showdataretentionsummary');
    if ($showsummary === false) {
        // This means that no value is stored in db. We use the default value in this case.
        $showsummary = true;
    }

    if ($showsummary && $iscurrentuser) {
        $summaryurl = new moodle_url('https://drive.google.com/file/d/17-lRmML6D40cSYBHVKkPpRYuQIZxyPf6/view?usp=sharing');
        $summarynode = new core_user\output\myprofile\node(
            'privacyandpolicies',
            'retentionsummary',
            get_string('dataretentionsummary', 'tool_dataprivacy'),
            null,
            $summaryurl
        );
        $category->add_node($summarynode);
    }

    // Add the Privacy category to the tree if it's not empty and it doesn't exist.
    $nodes = $category->nodes;
    if (!empty($nodes)) {
        if (!array_key_exists('privacyandpolicies', $tree->__get('categories'))) {
            $tree->add_category($category);
        }
        return true;
    }

    return false;
}

/**
 * Callback to add footer elements.
 *
 * @return string HTML footer content
 */
function tool_dataprivacy_standard_footer_html()
{
    $output = '';

    // A returned 0 means that the setting was set and disabled, false means that there is no value for the provided setting.
    $showsummary = get_config('tool_dataprivacy', 'showdataretentionsummary');
    if ($showsummary === false) {
        // This means that no value is stored in db. We use the default value in this case.
        $showsummary = true;
    }

    if ($showsummary) {
        $url = new moodle_url('https://drive.google.com/file/d/17-lRmML6D40cSYBHVKkPpRYuQIZxyPf6/view?usp=sharing');
        $output = html_writer::link($url, get_string('dataretentionsummary', 'tool_dataprivacy'));
        $output = html_writer::div($output, 'tool_dataprivacy');
    }
    return $output;
}

/**
 * Fragment to add a new purpose.
 *
 * @param array $args The fragment arguments.
 * @return string The rendered mform fragment.
 */
function tool_dataprivacy_output_fragment_addpurpose_form($args)
{

    $formdata = [];
    if (!empty($args['jsonformdata'])) {
        $serialiseddata = json_decode($args['jsonformdata']);
        parse_str($serialiseddata, $formdata);
    }

    $persistent = new \tool_dataprivacy\purpose();
    $mform = new \tool_dataprivacy\form\purpose(
        null,
        ['persistent' => $persistent],
        'post',
        '',
        null,
        true,
        $formdata
    );

    if (!empty($args['jsonformdata'])) {
        // Show errors if data was received.
        $mform->is_validated();
    }

    return $mform->render();
}

/**
 * Fragment to add a new category.
 *
 * @param array $args The fragment arguments.
 * @return string The rendered mform fragment.
 */
function tool_dataprivacy_output_fragment_addcategory_form($args)
{

    $formdata = [];
    if (!empty($args['jsonformdata'])) {
        $serialiseddata = json_decode($args['jsonformdata']);
        parse_str($serialiseddata, $formdata);
    }

    $persistent = new \tool_dataprivacy\category();
    $mform = new \tool_dataprivacy\form\category(
        null,
        ['persistent' => $persistent],
        'post',
        '',
        null,
        true,
        $formdata
    );

    if (!empty($args['jsonformdata'])) {
        // Show errors if data was received.
        $mform->is_validated();
    }

    return $mform->render();
}

/**
 * Fragment to edit a context purpose and category.
 *
 * @param array $args The fragment arguments.
 * @return string The rendered mform fragment.
 */
function tool_dataprivacy_output_fragment_context_form($args)
{
    global $PAGE;

    $contextid = $args[0];

    $context = \context_helper::instance_by_id($contextid);
    $customdata = \tool_dataprivacy\form\context_instance::get_context_instance_customdata($context);

    if (!empty($customdata['purposeretentionperiods'])) {
        $PAGE->requires->js_call_amd(
            'tool_dataprivacy/effective_retention_period',
            'init',
            [$customdata['purposeretentionperiods']]
        );
    }
    $mform = new \tool_dataprivacy\form\context_instance(null, $customdata);
    return $mform->render();
}

/**
 * Fragment to edit a contextlevel purpose and category.
 *
 * @param array $args The fragment arguments.
 * @return string The rendered mform fragment.
 */
function tool_dataprivacy_output_fragment_contextlevel_form($args)
{
    global $PAGE;

    $contextlevel = $args[0];
    $customdata = \tool_dataprivacy\form\contextlevel::get_contextlevel_customdata($contextlevel);

    if (!empty($customdata['purposeretentionperiods'])) {
        $PAGE->requires->js_call_amd(
            'tool_dataprivacy/effective_retention_period',
            'init',
            [$customdata['purposeretentionperiods']]
        );
    }

    $mform = new \tool_dataprivacy\form\contextlevel(null, $customdata);
    return $mform->render();
}

/**
 * Serves any files associated with the data privacy settings.
 *
 * @param stdClass $course Course object
 * @param stdClass $cm Course module object
 * @param context $context Context
 * @param string $filearea File area for data privacy
 * @param array $args Arguments
 * @param bool $forcedownload If we are forcing the download
 * @param array $options More options
 * @return bool Returns false if we don't find a file.
 */
function tool_dataprivacy_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{
    if ($context->contextlevel == CONTEXT_USER) {
        // Make sure the user is logged in.
        require_login(null, false);

        // Get the data request ID. This should be the first element of the $args array.
        $itemid = $args[0];
        // Fetch the data request object. An invalid ID will throw an exception.
        $datarequest = new \tool_dataprivacy\data_request($itemid);

        // Check if user is allowed to download it.
        if (!\tool_dataprivacy\api::can_download_data_request_for_user($context->instanceid, $datarequest->get('requestedby'))) {
            return false;
        }

        // Make the file unavailable if it has expired.
        if (\tool_dataprivacy\data_request::is_expired($datarequest)) {
            send_file_not_found();
        }

        // All good. Serve the exported data.
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/tool_dataprivacy/$filearea/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 0, 0, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}
