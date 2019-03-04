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
 * Test classes for handling embedded media.
 *
 * @package media_html5video
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test script for media embedding.
 *
 * @package media_html5video
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_html5video_testcase extends advanced_testcase {

    /**
     * Pre-test setup. Preserves $CFG.
     */
    public function setUp() {
        parent::setUp();

        // Reset $CFG and $SERVER.
        $this->resetAfterTest();

        // Consistent initial setup: all players disabled.
        \core\plugininfo\media::set_enabled_plugins('html5video');

        // Pretend to be using Firefox browser (must support ogg for tests to work).
        core_useragent::instance(true, 'Mozilla/5.0 (X11; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0 ');
    }

    /**
     * Test that plugin is returned as enabled media plugin.
     */
    public function test_is_installed() {
        $sortorder = \core\plugininfo\media::get_enabled_plugins();
        $this->assertEquals(['html5video' => 'html5video'], $sortorder);
    }

    /**
     * Test method get_supported_extensions()
     */
    public function test_supported_extensions() {
        $nativeextensions = file_get_typegroup('extension', 'html_video');

        // Make sure that the list of extensions from the setting is exactly the same as html_video group.
        $player = new media_html5video_plugin();
        $this->assertEmpty(array_diff($player->get_supported_extensions(), $nativeextensions));
        $this->assertEmpty(array_diff($nativeextensions, $player->get_supported_extensions()));
    }

    /**
     * Test embedding without media filter (for example for displaying file resorce).
     */
    public function test_embed_url() {
        global $CFG;

        $url = new moodle_url('http://example.org/1.webm');

        $manager = core_media_manager::instance();
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        $this->assertTrue($manager->can_embed_url($url, $embedoptions));
        $content = $manager->embed_url($url, 'Test & file', 0, 0, $embedoptions);

        $this->assertRegExp('~mediaplugin_html5video~', $content);
        $this->assertRegExp('~</video>~', $content);
        $this->assertRegExp('~title="Test &amp; file"~', $content);
        $this->assertRegExp('~width="' . $CFG->media_default_width . '"~', $content);
        $this->assertNotRegExp('~height=~', $content); // Allow to set automatic height.

        // Repeat sending the specific size to the manager.
        $content = $manager->embed_url($url, 'New file', 123, 50, $embedoptions);
        $this->assertRegExp('~width="123" height="50"~', $content);
    }

    /**
     * Test that mediaplugin filter replaces a link to the supported file with media tag.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_link() {
        global $CFG;
        $url = new moodle_url('http://example.org/some_filename.mp4');
        $text = html_writer::link($url, 'Watch this one');
        $content = format_text($text, FORMAT_HTML);

        $this->assertRegExp('~mediaplugin_html5video~', $content);
        $this->assertRegExp('~</video>~', $content);
        $this->assertRegExp('~title="Watch this one"~', $content);
        $this->assertNotRegExp('~<track\b~i', $content);
        $this->assertRegExp('~width="' . $CFG->media_default_width . '"~', $content);
    }

    /**
     * Test that mediaplugin filter does not work on <video> tags.
     */
    public function test_embed_media() {
        $url = new moodle_url('http://example.org/some_filename.mp4');
        $trackurl = new moodle_url('http://example.org/some_filename.vtt');
        $text = '<video controls="true"><source src="'.$url.'"/><source src="somethinginvalid"/>' .
            '<track src="'.$trackurl.'">Unsupported text</video>';
        $content = format_text($text, FORMAT_HTML);

        $this->assertNotRegExp('~mediaplugin_html5video~', $content);
        $this->assertEquals(clean_text($text, FORMAT_HTML), $content);
    }
}
