<?php
/**
 * Class with utility functions for Someone Else Pays
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 1 Feb 2018
 * @license AGPL-3.0
 */

class CRM_Someoneelsepays_Utils {
  /**
   * Method to get the id from $_REQUEST if it exists
   *
   * @return int|bool
   */
  public static function getIdFromRequest() {
    $requestValues = CRM_Utils_Request::exportValues();
    if (isset($requestValues['id'])) {
      return $requestValues['id'];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Method om dao in array te stoppen en de 'overbodige' data er uit te slopen
   *
   * @param $dao
   * @return array
   */
  public static function moveDaoToArray($dao) {
    $ignores = array('N', 'id', 'entity_id');
    $columns = get_object_vars($dao);
    // first remove all columns starting with _
    foreach ($columns as $key => $value) {
      if (substr($key, 0, 1) == '_') {
        unset($columns[$key]);
      }
      if (in_array($key, $ignores)) {
        unset($columns[$key]);
      }
    }
    return $columns;
  }

}
