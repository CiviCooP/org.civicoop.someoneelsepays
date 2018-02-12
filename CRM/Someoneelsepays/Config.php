<?php

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

    /**
     * CRM_Someoneelsepays_Config constructor.
     */
    function __construct() {
        try {
            $this->_contributionStatusOptionGroupId = civicrm_api3('OptionGroup', 'getvalue', array(
                'name' => 'contribution_status',
                'return' => 'id',
            ));
        }
        catch (CiviCRM_API3_Exception $ex) {
            CRM_Core_Error::createError(ts('Could not find an option group with the name contribution_status in extension org.civicoop.someoneelsepays. Contact your system administrator!'));
        }
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