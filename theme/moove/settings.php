<?php
// This file is part of Ranking block for Moodle - http://moodle.org/
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
 * Theme Moove block settings file
 *
 * @package    theme_moove
 * @copyright  2017 Willian Mano http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {

    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs('themesettingmoove', get_string('configtitle', 'theme_moove'));

    /*
    * ----------------------
    * General settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_moove_general', get_string('generalsettings', 'theme_moove'));

    // Logo file setting.
    $name = 'theme_moove/logo';
    $title = get_string('logo', 'theme_moove');
    $description = get_string('logodesc', 'theme_moove');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'), 'maxfiles' => 1);
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
    $page->add($setting);

    // Favicon setting.
    $name = 'theme_moove/favicon';
    $title = get_string('favicon', 'theme_moove');
    $description = get_string('favicondesc', 'theme_moove');
    $opts = array('accepted_types' => array('.ico'), 'maxfiles' => 1);
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
    $page->add($setting);

    // Preset.
    $name = 'theme_moove/preset';
    $title = get_string('preset', 'theme_moove');
    $description = get_string('preset_desc', 'theme_moove');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_moove', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_moove/presetfiles';
    $title = get_string('presetfiles', 'theme_moove');
    $description = get_string('presetfiles_desc', 'theme_moove');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        array('maxfiles' => 10, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Login page background image.
    $name = 'theme_moove/loginbgimg';
    $title = get_string('loginbgimg', 'theme_moove');
    $description = get_string('loginbgimg_desc', 'theme_moove');
    $opts = array('accepted_types' => array('.png', '.jpg', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbgimg', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $brand-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_moove/brandcolor';
    $title = get_string('brandcolor', 'theme_moove');
    $description = get_string('brandcolor_desc', 'theme_moove');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#0f47ad');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $navbar-header-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_moove/secondarymenucolor';
    $title = get_string('secondarymenucolor', 'theme_moove');
    $description = get_string('secondarymenucolor_desc', 'theme_moove');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#0f47ad');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $fontsarr = [
        'Roboto' => 'Roboto',
        'Poppins' => 'Poppins',
        'Montserrat' => 'Montserrat',
        'Open Sans' => 'Open Sans',
        'Lato' => 'Lato',
        'Raleway' => 'Raleway',
        'Inter' => 'Inter',
        'Nunito' => 'Nunito',
        'Encode Sans' => 'Encode Sans',
        'Work Sans' => 'Work Sans',
        'Oxygen' => 'Oxygen',
        'Manrope' => 'Manrope',
        'Sora' => 'Sora',
        'Epilogue' => 'Epilogue'
    ];

    $name = 'theme_moove/fontsite';
    $title = get_string('fontsite', 'theme_moove');
    $description = get_string('fontsite_desc', 'theme_moove');
    $setting = new admin_setting_configselect($name, $title, $description, 'Roboto', $fontsarr);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_moove/enablecourseindex';
    $title = get_string('enablecourseindex', 'theme_moove');
    $description = get_string('enablecourseindex_desc', 'theme_moove');
    $default = 1;
    $choices = array(0 => get_string('no'), 1 => get_string('yes'));
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);

    /*
    * ----------------------
    * Advanced settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_moove_advanced', get_string('advancedsettings', 'theme_moove'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode('theme_moove/scsspre',
        get_string('rawscsspre', 'theme_moove'), get_string('rawscsspre_desc', 'theme_moove'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode('theme_moove/scss', get_string('rawscss', 'theme_moove'),
        get_string('rawscss_desc', 'theme_moove'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Google analytics block.
    $name = 'theme_moove/googleanalytics';
    $title = get_string('googleanalytics', 'theme_moove');
    $description = get_string('googleanalyticsdesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    /*
    * -----------------------
    * Frontpage settings tab
    * -----------------------
    */
    $page = new admin_settingpage('theme_moove_frontpage', get_string('frontpagesettings', 'theme_moove'));

    // Disable teachers from cards.
    $name = 'theme_moove/disableteacherspic';
    $title = get_string('disableteacherspic', 'theme_moove');
    $description = get_string('disableteacherspicdesc', 'theme_moove');
    $default = 1;
    $choices = array(0 => get_string('no'), 1 => get_string('yes'));
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    // Slideshow.
    $name = 'theme_moove/slidercount';
    $title = get_string('slidercount', 'theme_moove');
    $description = get_string('slidercountdesc', 'theme_moove');
    $default = 0;
    $options = array();
    for ($i = 0; $i < 13; $i++) {
        $options[$i] = $i;
    }
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // If we don't have an slide yet, default to the preset.
    $slidercount = get_config('theme_moove', 'slidercount');

    if (!$slidercount) {
        $slidercount = $default;
    }

    if ($slidercount) {
        for ($sliderindex = 1; $sliderindex <= $slidercount; $sliderindex++) {
            $fileid = 'sliderimage' . $sliderindex;
            $name = 'theme_moove/sliderimage' . $sliderindex;
            $title = get_string('sliderimage', 'theme_moove');
            $description = get_string('sliderimagedesc', 'theme_moove');
            $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'), 'maxfiles' => 1);
            $setting = new admin_setting_configstoredfile($name, $title, $description, $fileid, 0, $opts);
            $page->add($setting);

            $name = 'theme_moove/slidertitle' . $sliderindex;
            $title = get_string('slidertitle', 'theme_moove');
            $description = get_string('slidertitledesc', 'theme_moove');
            $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
            $page->add($setting);

            $name = 'theme_moove/slidercap' . $sliderindex;
            $title = get_string('slidercaption', 'theme_moove');
            $description = get_string('slidercaptiondesc', 'theme_moove');
            $default = '';
            $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
            $page->add($setting);
        }
    }

    $setting = new admin_setting_heading('slidercountseparator', '', '<hr>');
    $page->add($setting);
    $settings->add($page);

    
    /*
    * --------------------
    * Footer settings tab
    * --------------------
    */
    $page = new admin_settingpage('theme_moove_footer', get_string('footersettings', 'theme_moove'));

    // Website.
    $name = 'theme_moove/website';
    $title = get_string('website', 'theme_moove');
    $description = get_string('websitedesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Mobile.
    $name = 'theme_moove/mobile';
    $title = get_string('mobile', 'theme_moove');
    $description = get_string('mobiledesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Mail.
    $name = 'theme_moove/mail';
    $title = get_string('mail', 'theme_moove');
    $description = get_string('maildesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Facebook url setting.
    $name = 'theme_moove/facebook';
    $title = get_string('facebook', 'theme_moove');
    $description = get_string('facebookdesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Twitter url setting.
    $name = 'theme_moove/twitter';
    $title = get_string('twitter', 'theme_moove');
    $description = get_string('twitterdesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Linkdin url setting.
    $name = 'theme_moove/linkedin';
    $title = get_string('linkedin', 'theme_moove');
    $description = get_string('linkedindesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Youtube url setting.
    $name = 'theme_moove/youtube';
    $title = get_string('youtube', 'theme_moove');
    $description = get_string('youtubedesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Instagram url setting.
    $name = 'theme_moove/instagram';
    $title = get_string('instagram', 'theme_moove');
    $description = get_string('instagramdesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Whatsapp url setting.
    $name = 'theme_moove/whatsapp';
    $title = get_string('whatsapp', 'theme_moove');
    $description = get_string('whatsappdesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Telegram url setting.
    $name = 'theme_moove/telegram';
    $title = get_string('telegram', 'theme_moove');
    $description = get_string('telegramdesc', 'theme_moove');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    $settings->add($page);
}
