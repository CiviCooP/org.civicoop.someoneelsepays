<?php
/**
 * Class to work with Membership or Participant Payment
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 31 Jan 2018
 * @license AGPL-3.0
 */

class CRM_Someoneelsepays_Sep {
  private $_entityType = NULL;
  private $_entityTable = NULL;
  private $_baseTable = NULL;
  private $_entityIdColumn = NULL;
  private $_validEntityTypes = array();

  /**
   * CRM_Someoneelsepays_Sep constructor.
   *
   * @param null $entityType
   */
  public function __construct($entityType = NULL) {
    $this->_validEntityTypes = array('membership', 'participant');
    if (!empty($entityType)) {
      if (!in_array(strtolower($entityType), $this->_validEntityTypes)) {
        CRM_Core_Error::createError('Trying to access ' . __CLASS__ . ' with invalid entity type (extension org.civicoop.someoneelsepays)');
      }
      else {
        $this->setDaoStuffWithType($entityType);
      }
    }
  }

  /**
   * Method to create a someone else pays situation
   *
   * @param $params
   * @return bool|array
   */
  public function create($params) {
    // payer_id, beneficiary_id, entity_type and entity_id are required
    $requiredParams = array('payer_id', 'entity_type', 'entity_id');
    foreach ($requiredParams as $requiredParam) {
      if (!isset($params[$requiredParam]) || empty($params[$requiredParam])) {
        CRM_Core_Error::createError(ts('Required parameter ') . $requiredParam .
          ts(' not found or empty in ') . __METHOD__ . ' (extension org.civicoop.someoneelsepays)');
        return FALSE;
      }
    }
    $params['entity_type'] = strtolower($params['entity_type']);
    // error if invalid entity type
    if (!in_array($params['entity_type'], $this->_validEntityTypes)) {
      CRM_Core_Error::createError(ts('Invalid entity type ') . $params['entity_type'] . ' in ' . __METHOD__
        . ' (extension org.civicoop.someoneelsepays)');
      return FALSE;
    }
    $this->setDaoStuffWithType($params['entity_type']);
    // move contribution to payer if necessary
    $this->moveContribution($params['entity_id'], $params['payer_id']);
    return civicrm_api3('Sep', 'getsingle', $params);
  }

  /**
   * Method to move contribution to payer if required
   *
   * @param int $entityId
   * @param int $payerId
   */
  private function moveContribution($entityId, $payerId) {
    // get entity payment contribution_id
    $entityQuery = 'SELECT contribution_id FROM ' . $this->_entityTable . ' WHERE ' . $this->_entityIdColumn . ' = %1';
    $contributionId = (int) CRM_Core_DAO::singleValueQuery($entityQuery, array(
      1 => array($entityId, 'Integer'),
    ));
    // move contribution
    $update = "UPDATE civicrm_contribution SET contact_id = %1 WHERE id = %2 AND contact_id != %1";
    CRM_Core_DAO::executeQuery($update, array(
      1 => array($payerId, 'Integer'),
      2 => array($contributionId, 'Integer'),
    ));
  }

  /**
   * Method to determine if the params coming from the API Get are valid
   *
   * @param $params
   * @return array
   */
  public function validApiGetParams($params) {
    // invalid if no entity_type or contribution_id
    if (!isset($params['entity_type']) && !isset($params['contribution_id'])) {
      return array(
        'is_valid' => FALSE,
        'error_message' => ts('entity_type or contribution_id are required'),
      );
    }

    // invalid if there is an entity_id but no entity_type
    if (isset($params['entity_id']) && !isset($params['entity_type'])) {
      return array(
        'is_valid' => FALSE,
        'error_message' => ts('found entity_id but did not find entity_type, either both or none are valid'),
      );
    }
    // invalid if entity type but invalid type
    if (isset($params['entity_type'])) {
      if (!in_array($params['entity_type'], $this->_validEntityTypes)) {
        return array(
          'is_valid' => FALSE,
          'error_message' => ts('entity_type ' . $params['entity_type'] . ' is not valid'),
        );
      }
    }
    // invalid if parameters is not an array
    if (!is_array($params)) {
      return array(
        'is_valid' => FALSE,
        'error_message' => ts('expecting array of parameters, not found'),
      );
    }
    // invalid if there are no parameters at all
    if (empty($params)) {
      return array(
        'is_valid' => FALSE,
        'error_message' => ts('no parameters found, getting all sep records will impact performance'),
      );
    }
    return array(
      'is_valid' => TRUE,
    );
  }

  /**
   * Method to get sep data based on the params set in the api sep get
   *
   * @param $params
   * @return array
   */
  public function apiGet($params) {
    $result = array();
    $queryArray = array();
    if (!isset($params['entity_type'])) {
      $params['entity_type'] = $this->findEntityTypeForContribution($params['contribution_id']);
    }
    $this->setDaoStuffWithType($params['entity_type']);
    // use contribution_id if set
    if (isset($params['contribution_id'])) {
      $queryArray = $this->generateQueryContributionId($params);
    }
    else {
      if (isset($params['entity_type'])) {
        $queryArray = $this->generateQueryEntityType($params);
      }
    }
    if (!empty($queryArray)) {
      $dao = CRM_Core_DAO::executeQuery($queryArray['query'], $queryArray['params']);
      while ($dao->fetch()) {
        $result[] = CRM_Someoneelsepays_Utils::moveDaoToArray($dao);
      }
    }
    return $result;
  }

  /**
   * Method to find entity_type for contribution
   *
   * @param $contributionId
   * @return string
   */
  private function findEntityTypeForContribution($contributionId) {
    $query = 'SELECT COUNT(*) FROM civicrm_membership_payment WHERE contribution_id = %1';
    $count = CRM_Core_DAO::singleValueQuery($query, array(
       1 => array($contributionId, 'Integer'),
    ));
    if ($count > 0) {
      return 'membership';
    }
    else {
      return 'participant';
    }
  }

  /**
   * Method to generate the get query if entity_type is set
   *
   * @param $params
   * @return array
   */
  private function generateQueryEntityType($params) {
    $result = array();
    $index = 0;
    $where = 'WHERE cont.contact_id != base.contact_id';
    if (isset($params['entity_id'])) {
      $index++;
      $where .= ' AND ' . $this->_entityIdColumn . ' = %' . $index;
      $result['params'][$index] = array($params['entity_id'], 'Integer');
    }
    if (isset($params['payer_id'])) {
      $index++;
      $where .= ' AND cont.contact_id = %' . $index;
      $result['params'][$index] = array($params['payer_id'], 'Integer');
    }
    if (isset($params['beneficiary_id'])) {
      $index++;
      $where .= ' AND base.contact_id = %' . $index;
      $result['params'][$index] = array($params['beneficiary_id'], 'Integer');
    }

    $result['query'] = 'SELECT cont.contact_id AS payer_id, pay.' . $this->_entityIdColumn . ' AS entity_id, "' .
        $this->_entityType . '" AS entity_type, cont.id AS contribution_id, base.contact_id AS beneficiary_id
        FROM ' . $this->_entityTable . ' pay
        JOIN civicrm_contribution cont ON cont.id = pay.contribution_id
        JOIN ' . $this->_baseTable . ' base ON pay.' . $this->_entityIdColumn . ' = base.id ' . $where;
    return $result;
  }

  /**
   * Method to generate the get query if contribution_id is set
   *
   * @param $params
   * @return array
   */
  private function generateQueryContributionId($params) {
    $result = array();
    $index = 1;
    $where = 'WHERE cont.id = %1 AND cont.contact_id != base.contact_id';
    $result['params'][1] = array($params['contribution_id'], 'Integer');
    if (isset($params['entity_id'])) {
      $index++;
      $where .= ' AND ' . $this->_entityIdColumn . ' = %' . $index;
      $result['params'][$index] = array($params['entity_id'], 'Integer');
    }
    if (isset($params['payer_id'])) {
      $index++;
      $where .= ' AND cont.contact_id = %' . $index;
      $result['params'][$index] = array($params['payer_id'], 'Integer');
    }
    if (isset($params['beneficiary_id'])) {
      $index++;
      $where .= ' AND base.contact_id = %' . $index;
      $result['params'][$index] = array($params['beneficiary_id'], 'Integer');
    }

    $result['query'] = 'SELECT cont.contact_id AS payer_id, pay.' . $this->_entityIdColumn .  ' AS entity_id, "'
        . $this->_entityType . '" AS entity_type, cont.id AS contribution_id, base.contact_id AS beneficiary_id
        FROM civicrm_contribution cont 
        JOIN ' . $this->_entityTable . ' pay ON cont.id = pay.contribution_id
        JOIN ' . $this->_baseTable . ' base ON pay.' . $this->_entityIdColumn . ' = base.id ' . $where;
    return $result;
  }

  /**
   * Method to process the civicrm buildForm hook
   *
   * @param $formName
   * @param $form
   */
  public static function buildForm($formName, &$form) {
    switch ($formName) {
      case 'CRM_Member_Form_MembershipView':
        self::addToMembershipView($form);
        break;

      case 'CRM_Member_Form_Membership':
        self::addToMembership($form);
        break;

    }
  }

  /**
   * Method to process the civicrm postProcess hook
   *
   * @param $formName
   * @param $form
   */
  public static function postProcess($formName, &$form) {
    switch ($formName) {
      case 'CRM_Member_Form_Membership':
        $formAction = $form->getVar('_action');
        // if edit, update contribution contact_id if required
        if ($formAction == CRM_Core_Action::UPDATE) {
          $submitValues = $form->getVar('_submitValues');
          $sep = new CRM_Someoneelsepays_Sep('membership');
          $sep->updateContributionContact($submitValues);
        }
        break;

    }
  }
  private function updateContributionContact($params) {
    CRM_Core_Error::debug('params', $params);
    exit();
  }

  /**
   * Method to add sep details to membership form if required
   *
   * @param $form
   */
  private static function addToMembership(&$form) {
    $formAction = $form->getVar('_action');
    switch ($formAction) {
      case CRM_Core_Action::UPDATE:
        $membershipId = $form->getVar('_id');
        $sep = new CRM_Someoneelsepays_Sep('membership');
        $sepData = $sep->getSepDetailsWithEntity($membershipId, 'membership');
        if ($sepData) {
          $form->assign('sep_data', $sepData);
          $form->addEntityRef('sep_payer_id', ts('Select Contact to Change Payer'), array(
            'api' => array(
              'params' => array('is_deceased' => 0),
            ),
          ));
          $form->setDefaults(array('sep_payer_id' => $sepData['payer_id']));
          CRM_Core_Region::instance('page-body')->add(array(
            'template' => 'SepEdit.tpl'));
        }
        break;
    }
  }

  /**
   * Method to add sep details to membership view form if required
   *
   * @param $form
   */
  private static function addToMembershipView(&$form) {
    $membershipId = CRM_Someoneelsepays_Utils::getIdFromRequest();
    $sep = new CRM_Someoneelsepays_Sep('membership');
    $sepData = $sep->getSepDetailsWithEntity($membershipId, 'membership');
    if ($sepData) {
      $viewUrl = CRM_Utils_System::url('civicrm/contact/view/contribution', 'reset=1&id='
        . $sepData['contribution_id'] . '&cid=' . $sepData['payer_id'] . '&action=view&context=membership', TRUE);
      $editUrl = CRM_Utils_System::url('civicrm/contact/view/contribution', 'reset=1&action=update&id='
        . $sepData['contribution_id'] . '&cid=' . $sepData['payer_id'] . '&context=membership', TRUE);
      $sepActionLinks[] = '<a class="action-item crm-hover-button" title="View Contribution" href="' . $viewUrl . '">' . ts("View") . '</a>';
      $sepActionLinks[] = '<a class="action-item crm-hover-button" title="Edit Contribution" href="' . $editUrl . '">' . ts("Edit") . '</a>';
      $form->assign('sep_data', $sepData);
      $form->assign('sep_action_links', $sepActionLinks);
      CRM_Core_Region::instance('page-body')->add(array(
        'template' => 'SepView.tpl'));
    }
  }

  /**
   * Method to get the someone else pays details with a contribution
   *
   * @param int $contributionId
   * @return array
   */
  public function getSepDetailsWithContribution($contributionId) {
    $this->setEntityTypeWithContribution($contributionId);
    $result = array();
    $query = 'SELECT cont.contact_id as payer_id, payer.display_name AS payer_display_name, base.contact_id AS beneficiary_id, 
      bene.display_name AS beneficiary_display_name, ' . $this->_entityType . ' AS entity_type, pay.'
      . $this->_entityIdColumn . ' AS entity_id, cont.id AS contribution_id, fin.name AS financial_type, 
      ov.label AS contribution_status, cont.total_amount, cont.currency, cont.receive_date, cont.invoice_id, 
      contr.creditnote_id 
      FROM civicrm_contribution cont
      JOIN ' . $this->_entityTable . ' pay ON cont.id = pay.contribution_id
      JOIN ' . $this->_baseTable . ' base ON pay.membership_id = base.id
      JOIN civicrm_contact payer ON cont.contact_id = payer.id
      JOIN civicrm_contact bene ON base.contact_id = bene.id
      JOIN civicrm_financial_type fin ON cont.financial_type_id = fin.id
      JOIN civicrm_option_value ov ON cont.contribution_status_id = ov.value AND ov.option_group_id = '
      . CRM_Someoneelsepays_Config::singleton()->getContributionStatusOptionGroupId() . '
      WHERE cont.id = %1 AND cont.contact_id != base.contact_id';
    $queryParams = array(
      1 => array($contributionId, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    if ($dao->fetch()) {
      $result = CRM_Someoneelsepays_Utils::moveDaoToArray($dao);
    }
    return $result;
  }

  /**
   * Method to get the someone else pays details with a contribution and an entity
   *
   * @param int $contributionId
   * @param int $entityId
   * @param string $entityType
   * @return array
   */
  public function getSepDetailsWithEntityAndContribution($contributionId, $entityId, $entityType) {
    $entityType = strtolower($entityType);
    if (!in_array(strtolower($entityType), $this->_validEntityTypes)) {
      CRM_Core_Error::createError('Trying to access ' . __CLASS__ . ' with invalid entity type (extension org.civicoop.someoneelsepays)');
    }
    else {
      $this->setDaoStuffWithType($entityType);
    }
    $result = array();
    $query = 'SELECT cont.contact_id as payer_id, payer.display_name AS payer_display_name, base.contact_id AS beneficiary_id, 
      bene.display_name AS beneficiary_display_name, ' . $this->_entityType . ' AS entity_type, pay.'
      . $this->_entityIdColumn . ' AS entity_id, cont.id AS contribution_id, fin.name AS financial_type, 
      ov.label AS contribution_status, cont.total_amount, cont.currency, cont.receive_date, cont.invoice_id, 
      contr.creditnote_id 
      FROM civicrm_contribution cont
      JOIN ' . $this->_entityTable . ' pay ON cont.id = pay.contribution_id
      JOIN ' . $this->_baseTable . ' base ON pay.membership_id = base.id
      JOIN civicrm_contact payer ON cont.contact_id = payer.id
      JOIN civicrm_contact bene ON base.contact_id = bene.id
      JOIN civicrm_financial_type fin ON cont.financial_type_id = fin.id
      JOIN civicrm_option_value ov ON cont.contribution_status_id = ov.value AND ov.option_group_id = '
      . CRM_Someoneelsepays_Config::singleton()->getContributionStatusOptionGroupId() . '
      WHERE cont.id = %1 AND ' . $this->_entityIdColumn . ' = %2 AND cont.contact_id != base.contact_id';
    $queryParams = array(
      1 => array($contributionId, 'Integer'),
      2 => array($entityId, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    if ($dao->fetch()) {
      $result = CRM_Someoneelsepays_Utils::moveDaoToArray($dao);
    }
    return $result;
  }

  /**
   * Method to set the required properties based on a contribution
   *
   * @param $contributionId
   */
  private function setEntityTypeWithContribution($contributionId) {
    $queryParams = array(
      1 => array($contributionId, 'Integer'),
    );
    $query = 'SELECT COUNT(*) FROM civicrm_membership_payment WHERE contribution_id = %1';
    $countMembership = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($countMembership > 0) {
      $this->setDaoStuffWithType('membership');
    }
    else {
      $query = 'SELECT COUNT(*) FROM civicrm_participant_payment WHERE contribution_id = %1';
      $countParticipant = CRM_Core_DAO::singleValueQuery($query, $queryParams);
      if ($countParticipant > 0) {
        $this->setDaoStuffWithType('participant');
      }
    }
  }

  /**
   * Method to get data set about someone else pays with entity_id (membership or participant)
   *
   * @param $entityId
   * @param $entityType
   * @return array
   */
  public function getSepDetailsWithEntity($entityId, $entityType) {
    $entityType = strtolower($entityType);
    if (!in_array(strtolower($entityType), $this->_validEntityTypes)) {
      CRM_Core_Error::createError('Trying to access ' . __CLASS__ . ' with invalid entity type (extension org.civicoop.someoneelsepays)');
    }
    else {
      $this->setDaoStuffWithType($entityType);
    }
    $result = array();
    $query = 'SELECT cont.contact_id as payer_id, payer.display_name AS payer_display_name, 
      base.contact_id AS beneficiary_id, bene.display_name AS beneficiary_display_name,
      "' . $entityType . '" AS entity_type, pay.' . $this->_entityIdColumn . ' AS entity_id, cont.id AS contribution_id,
      fin.name AS financial_type, ov.label AS contribution_status, cont.total_amount, cont.currency, cont.receive_date,
      cont.invoice_id, cont.creditnote_id
      FROM ' . $this->_entityTable . ' pay JOIN ' . $this->_baseTable . ' base ON pay.' . $this->_entityIdColumn . ' = base.id
      JOIN civicrm_contribution cont ON pay.contribution_id = cont.id
      JOIN civicrm_contact payer ON cont.contact_id = payer.id
      JOIN civicrm_contact bene ON base.contact_id = bene.id
      JOIN civicrm_financial_type fin ON cont.financial_type_id = fin.id
      JOIN civicrm_option_value ov ON cont.contribution_status_id = ov.value AND ov.option_group_id = '
      . CRM_Someoneelsepays_Config::singleton()->getContributionStatusOptionGroupId() . '
      WHERE pay.' . $this->_entityIdColumn . ' = %1 AND cont.contact_id != base.contact_id';
    $queryParams = array(
      1 => array($entityId, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    if ($dao->fetch()) {
      $result = CRM_Someoneelsepays_Utils::moveDaoToArray($dao);
    }
    return $result;
  }

  /**
   * Method to set the class properties to be used by the DAO with entity type
   *
   * @param $entityType
   */
  private function setDaoStuffWithType($entityType) {
    $this->_entityType = strtolower($entityType);
    $this->_entityTable = 'civicrm_' . strtolower($entityType) . '_payment';
    $this->_baseTable = 'civicrm_' . strtolower($entityType);
    $this->_entityIdColumn = strtolower($entityType) . '_id';
  }

}
