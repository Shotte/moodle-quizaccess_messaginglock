@quizaccess @quizaccess_messaginglock
Feature: Lock Moodle messaging during a quiz
  In order to prevent students from sending answers to each other during an exam
  As a teacher
  I need a single quiz setting that locks Moodle messaging from the start of an attempt until the exam time has ended

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
      | student3 | Student   | Three    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "activities" exist:
      | activity | name        | course | idnumber | timelimit | messaginglockenabled |
      | quiz     | Exam        | C1     | exam     | 3600      | 1                    |
      | quiz     | Old without | C1     | old0     | 0         | 0                    |
      | quiz     | Old with    | C1     | old1     | 0         | 1                    |
    And the following "activities" exist:
      | activity | name    | course | idnumber |
      | qbank    | Qbank 1 | C1     | qbank1   |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | qbank1    | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | First question |
    And quiz "Exam" contains the following questions:
      | question | page |
      | TF1      | 1    |

  Scenario: A new quiz offers the setting as a Yes/No select that defaults to Yes
    Given I log in as "teacher1"
    When I add a quiz activity to course "Course 1" section "1"
    Then the field "Lock Moodle messaging during the exam" matches value "Yes"
    And I set the following fields to these values:
      | Name | New exam |
    And I press "Save and return to course"
    And the quiz "New exam" should have the messaging lock enabled

  Scenario: Existing quizzes keep their stored setting when edited
    When I am on the "Old without" "quiz activity editing" page logged in as "teacher1"
    Then the field "Lock Moodle messaging during the exam" matches value "No"
    And I am on the "Old with" "quiz activity editing" page
    And the field "Lock Moodle messaging during the exam" matches value "Yes"
    And I set the field "Lock Moodle messaging during the exam" to "No"
    And I press "Save and return to course"
    And the quiz "Old with" should have the messaging lock disabled

  Scenario: The lock starts with the attempt, survives an early submission and ends with the exam time
    Given "student1" should be able to send Moodle messages
    When user "student1" has started an attempt at quiz "Exam"
    And user "student2" has started an attempt at quiz "Exam"
    Then "student1" should not be able to send Moodle messages
    And "student2" should not be able to send Moodle messages
    And "student3" should be able to send Moodle messages
    And "teacher1" should be able to send Moodle messages
    And I am on the "Exam" "mod_quiz > View" page logged in as "student3"
    And I should see "From the start of your attempt you cannot send Moodle messages"
    When user "student1" has finished an attempt at quiz "Exam"
    And I run the scheduled task "\quizaccess_messaginglock\task\release_locks"
    Then "student1" should not be able to send Moodle messages
    When the exam time of quiz "Exam" has passed
    And I run the scheduled task "\quizaccess_messaginglock\task\release_locks"
    Then "student1" should be able to send Moodle messages
    And "student2" should be able to send Moodle messages
