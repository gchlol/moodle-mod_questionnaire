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
 * PHPUnit questionnaire generator tests
 *
 * @package    mod_questionnaire
 * @copyright  2015 Mike Churchward (mike@churchward.ca)
 * @author     Mike Churchward
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/questionnaire/locallib.php');
require_once($CFG->dirroot.'/mod/questionnaire/questiontypes/questiontypes.class.php');

/**
 * Unit tests for {@link questionnaire_questiontypes_testcase}.
 * @group mod_questionnaire
 */
class mod_questionnaire_questiontypes_testcase extends advanced_testcase {
    public function test_create_question_checkbox() {
        $this->create_test_question_with_choices(QUESCHECK, 'questionnaire_question_check', array('content' => 'Check one'));
    }

    public function test_create_question_date() {
        $this->create_test_question(QUESDATE, 'questionnaire_question_date', array('content' => 'Enter a date'));
    }

    public function test_create_question_dropdown() {
        $this->create_test_question_with_choices(QUESDROP, 'questionnaire_question_drop', array('content' => 'Select one'));
    }

    public function test_create_question_essay() {
        $questiondata = array(
            'content' => 'Enter an essay',
            'length' => 0,
            'precise' => 5);
        $this->create_test_question(QUESESSAY, 'questionnaire_question_essay', $questiondata);
    }

    public function test_create_question_sectiontext() {
        $this->create_test_question(QUESSECTIONTEXT, 'questionnaire_question_sectiontext',
            array('name' => null, 'content' => 'This a section label.'));
    }

    public function test_create_question_numeric() {
        $questiondata = array(
            'content' => 'Enter a number',
            'length' => 10,
            'precise' => 0);
        $this->create_test_question(QUESNUMERIC, 'questionnaire_question_numeric', $questiondata);
    }

    public function test_create_question_radiobuttons() {
        $this->create_test_question_with_choices(QUESRADIO, 'questionnaire_question_radio', array('content' => 'Choose one'));
    }

    public function test_create_question_ratescale() {
        $this->create_test_question_with_choices(QUESRATE, 'questionnaire_question_rate', array('content' => 'Rate these'));
    }

    public function test_create_question_textbox() {
        $questiondata = array(
            'content' => 'Enter some text',
            'length' => 20,
            'precise' => 25);
        $this->create_test_question(QUESTEXT, 'questionnaire_question_text', $questiondata);
    }

    public function test_create_question_yesno() {
        $this->create_test_question(QUESYESNO, 'questionnaire_question_yesno', array('content' => 'Enter yes or no'));
    }


// General tests to call from specific tests above.

    private function create_test_question($qtype, $questionclass, $questiondata = array(), $choicedata = null) {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_questionnaire');
        $questionnaire = $generator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_instance('questionnaire', $questionnaire->id);

        $questiondata['survey_id'] = $questionnaire->sid;
        $questiondata['name'] = isset($questiondata['name']) ? $questiondata['name'] : 'Q1';
        $questiondata['content'] = isset($questiondata['content']) ? $questiondata['content'] : 'Test content';
        $question = $generator->create_question($qtype, $questiondata, $choicedata);
        $this->assertInstanceOf($questionclass, $question);
        $this->assertTrue($question->qid > 0);

        // Question object retrieved from the database should have correct data.
        $question = new $questionclass($question->qid);
        $this->assertEquals($question->type_id, $qtype);
        foreach ($questiondata as $property => $value) {
            $this->assertEquals($question->$property, $value);
        }
        if ($question->has_choices()) {
            $this->assertEquals('array', gettype($question->choices));
            $this->assertEquals(count($choicedata), count($question->choices));
            $choicedatum = reset($choicedata);
            foreach ($question->choices as $cid => $choice) {
                $this->assertTrue($DB->record_exists('questionnaire_quest_choice', array('id' => $cid)));
                $this->assertEquals($choice->content, $choicedatum->content);
                $this->assertEquals($choice->value, $choicedatum->value);
                $choicedatum = next($choicedata);
            }
        }

        // Questionnaire object should now have question record(s).
        $questionnaire = new questionnaire($questionnaire->id, null, $course, $cm, true);
        $this->assertTrue($DB->record_exists('questionnaire_question', array('id' => $question->id)));
        $this->assertEquals('array', gettype($questionnaire->questions));
        $this->assertTrue(array_key_exists($question->id, $questionnaire->questions));
        $this->assertEquals(1, count($questionnaire->questions));
        if ($questionnaire->questions[$question->id]->has_choices()) {
            $this->assertEquals(count($choicedata), count($questionnaire->questions[$question->id]->choices));
        }
    }

    private function create_test_question_with_choices($qtype, $questionclass, $questiondata = array(), $choicedata = null) {
        if (is_null($choicedata)) {
            $choicedata = array(
                (object)array('content' => 'One', 'value' => 1),
                (object)array('content' => 'Two', 'value' => 2),
                (object)array('content' => 'Three', 'value' => 3));
        }
        $this->create_test_question($qtype, $questionclass, $questiondata, $choicedata);
    }
}