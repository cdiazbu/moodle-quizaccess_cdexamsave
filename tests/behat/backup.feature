@quizaccess @quizaccess_cdexamsave
Feature: Backup and restore of quizzes if quizaccess_cdexamsave is installed
  In order to reuse my quizzes
  As a teacher
  I need to be able to back them up and restore them.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And I log in as "admin"

  @javascript
  Scenario: Duplicate a quiz with two questions
    Given the following "activities" exist:
      | activity   | name   | intro              | course | idnumber |
      | quiz       | Quiz 1 | For testing backup | C1     | quiz1    |
    When I am on "Course 1" course homepage with editing mode on
    And I duplicate "Quiz 1" activity editing the new copy with:
      | Name | Quiz 2 |
    And I am on "Course 1" course homepage
    Then I should see "Quiz 1"
    And I should see "Quiz 2"

  @javascript
  Scenario: Backup and restore a course containing a quiz.
    Given the following "activities" exist:
      | activity   | name   | intro              | course | idnumber |
      | quiz       | Quiz 1 | For testing backup | C1     | quiz1    |
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Restored course |
    Then I should see "Restored course"