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
 * This displays block region settings for the dashboard page.
 *
 * @package    theme_adaptable
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @copyright 2015 Jeremy Hopkins (Coventry University)
 * @copyright 2015 Fernando Acedo (3-bits.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

// Frontpage Block Regions Section.
$temp = new admin_settingpage('theme_adaptable_dash_block_regions',
        get_string('dashboardblockregionsettings', 'theme_adaptable'));
if ($ADMIN->fulltree) {
    $temp->add(new admin_setting_heading('theme_adaptable_heading', get_string('dashblocklayoutbuilder', 'theme_adaptable'),
        format_text(get_string('dashblocklayoutbuilderdesc', 'theme_adaptable'), FORMAT_MARKDOWN)));

    $name = 'theme_adaptable/dashblocksenabled';
    $title = get_string('dashblocksenabled', 'theme_adaptable');
    $description = get_string('dashblocksenableddesc', 'theme_adaptable');
    $setting = new admin_setting_configcheckbox($name, $title, $description, false);
    $temp->add($setting);

    $name = 'theme_adaptable/dashblocksposition';
    $title = get_string('dashblocksposition', 'theme_adaptable');
    $description = get_string('dashblockspositiondesc', 'theme_adaptable');
    $default = $dashboardblockregionposition['abovecontent'];
    $choices = $dashboardblockregionposition;
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $temp->add($setting);

    // Dashboard block region builder.
    $noregions = 20; // Number of block regions defined in config.php.
    list('imgblder' => $imgblder, 'totalblocks' => $totalblocks) = \theme_adaptable\toolbox::admin_settings_layout_builder(
        $temp, 'dashblocklayoutlayoutrow', $bootstrap12defaults, $bootstrap12);

    $temp->add(new admin_setting_heading('theme_adaptable_blocklayoutcheck', get_string('layoutcheck', 'theme_adaptable'),
        format_text(get_string('layoutcheckdesc', 'theme_adaptable'), FORMAT_MARKDOWN)));

    $checkcountcolor = '#00695C';
    if ($totalblocks > $noregions) {
        $mktcountcolor = '#D7542A';
    }
    $mktcountmsg = '<span style="color: ' . $checkcountcolor . '">';
    $mktcountmsg .= get_string('layoutcount1', 'theme_adaptable') . '<strong>' . $noregions . '</strong>';
    $mktcountmsg .= get_string('layoutcount2', 'theme_adaptable') . '<strong>' . $totalblocks . '/' . $noregions . '</strong>.';

    $temp->add(new admin_setting_heading('theme_adaptable_dashlayoutblockscount', '', $mktcountmsg));

    $temp->add(new admin_setting_heading('theme_adaptable_dashlayoutbuilder', '', $imgblder));
}
$ADMIN->add('theme_adaptable', $temp);
