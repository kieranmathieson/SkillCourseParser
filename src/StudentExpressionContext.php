<?php
/**
 * A class for the context of an ExpressionLanguage evaluation,
 * offering data on the currently logged in student/user.
 *
 * User: kieran
 * Date: 4/2/18
 * Time: 5:23 PM
 */

namespace Drupal\hello;


class StudentExpressionContext extends ExpressionContextBase {

  /**
   * Is the current user a student?
   *
   * @return bool True if a student, false for anon and other users.
   */
  public function loggedIn() {
    return true;
  }

  /**
   * Has the logged in student submitted the given exercise?
   * Returns false for anon or non-student users, or if exercise
   * does not exist.
   *
   * @param string $exerciseName Exercise internal name.
   * @return bool Result.
   */
  public function exerciseSubmitted(string $exerciseName) {
    return false;
  }

  /**
   * Has the logged in student completed the given exercise?
   * Returns false for anon or non-student users, or if exercise
   * does not exist.
   *
   * @param string $exerciseName Exercise internal name.
   * @return bool Result.
   */
  public function exerciseCompleted(string $exerciseName) {
    return false;
  }

  /**
   * Test whether user has the given role in a class.
   *
   * @param string $role Role to look for.
   * @return bool True if the user has the given role in any classes.
   */
  public function hasClassRole(string $role) {
    return false;
  }


}