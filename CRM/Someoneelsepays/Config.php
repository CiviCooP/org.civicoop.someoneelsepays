<?php
use CRM_Someoneelsepays_ExtensionUtil as E;

/**
 * Class for Someone Else Pays Configuration
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 1 Feb 2018
 * @license AGPL-3.0
 */
class CRM_Someoneelsepays_Config {

  // property for singleton pattern (caching the config)
  static private $_singleton = NULL;

  private $_contributionStatusOptionGroupId = NULL;
  private $_sepSoftCreditTypeId = NULL;
  private $_eventTypeOptionGroupId = NULL;
  private $_roleOptionGroupId = NULL;

  /**
   * CRM_Someoneelsepays_Config constructor.
   */
  public function __construct() {
    try {
      $this->_contributionStatusOptionGroupId = civicrm_api3('OptionGroup', 'getvalue', array(
          'name' => 'contribution_status',
          'return' => 'id',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::createError(E::ts('Could not find an option group with the name contribution_status.'));
    }
    try {
      $this->_sepSoftCreditTypeId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'soft_credit_type',
        'name' => 'sep_default_soft_credit_type',
        'return' => 'value',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::createError(E::ts('Could not find the soft credit type id for someone else pays.'));
    }
    try {
      $result = civicrm_api3('OptionGroup', 'get', [
        'sequential' => 1,
        'return' => ["id", "name"],
        'name' => ['IN' => ["participant_role", "event_type"]],
      ]);
      foreach ($result['values'] as $apiSet) {
        switch ($apiSet['name']) {
          case 'event_type':
            $this->_eventTypeOptionGroupId = $apiSet['id'];
            break;

          case 'participant_role':
            $this->_roleOptionGroupId = $apiSet['id'];
            break;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::createError(E::ts('Could not find an option group for event_type or participant_role'));
    }
  }

  /**
   * Getter for event type option group id
   *
   * @return int|null
   */
  public function getEventTypeOptionGroupId() {
    return $this->_eventTypeOptionGroupId;
  }

  /**
   * Getter for participant role option group id
   *
   * @return int|null
   */
  public function getRoleOptionGroupId() {
    return $this->_roleOptionGroupId;
  }

  /**
   * Getter for sep soft credit type id
   *
   * @return int|null
   */
  public function getSepSoftCreditTypeId() {
    return $this->_sepSoftCreditTypeId;
  }

  /**
   * Getter for contribution status option group id
   *
   * @return array|null
   */
  public function getContributionStatusOptionGroupId() {
    return $this->_contributionStatusOptionGroupId;
  }

  /**
   * Function to return singleton object
   *
   * @return CRM_Someoneelsepays_Config
   * @access public
   * @static
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Someoneelsepays_Config();
    }
    return self::$_singleton;
  }

}
