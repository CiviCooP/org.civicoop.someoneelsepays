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
    $this->_validEntityTypes = ['membership', 'participant'];
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
    $contributionId = (int) CRM_Core_DAO::singleValueQuery($entityQuery, [
      1 => [$entityId, 'Integer'],
    ]);
    // move contribution
    $update = "UPDATE civicrm_contribution SET contact_id = %1 WHERE id = %2 AND contact_id != %1";
    CRM_Core_DAO::executeQuery($update, [
      1 => [$payerId, 'Integer'],
      2 => [$contributionId, 'Integer'],
    ]);
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
      return [
        'is_valid' => FALSE,
        'error_message' => ts('entity_type or contribution_id are required'),
      ];
    }

    // invalid if there is an entity_id but no entity_type
    if (isset($params['entity_id']) && !isset($params['entity_type'])) {
      return [
        'is_valid' => FALSE,
        'error_message' => ts('found entity_id but did not find entity_type, either both or none are valid'),
      ];
    }
    // invalid if entity type but invalid type
    if (isset($params['entity_type'])) {
      if (!in_array($params['entity_type'], $this->_validEntityTypes)) {
        return [
          'is_valid' => FALSE,
          'error_message' => ts('entity_type ' . $params['entity_type'] . ' is not valid'),
        ];
      }
    }
    // invalid if parameters is not an array
    if (!is_array($params)) {
      return [
        'is_valid' => FALSE,
        'error_message' => ts('expecting array of parameters, not found'),
      ];
    }
    // invalid if there are no parameters at all
    if (empty($params)) {
      return [
        'is_valid' => FALSE,
        'error_message' => ts('no parameters found, getting all sep records will impact performance'),
      ];
    }
    return ['is_valid' => TRUE];
  }

  /**
   * Method to get sep data based on the params set in the api sep get
   *
   * @param $params
   * @return array
   */
  public function apiGet($params) {
    $result = [];
    $queryArray = [];
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
    $count = CRM_Core_DAO::singleValueQuery($query, [
       1 => [$contributionId, 'Integer'],
    ]);
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
   * Method to process the civicrm fieldOptions hook
   *
   * @param $entity
   * @param $field
   * @param $options
   * @param $params
   */
  public static function fieldOptions($entity, $field, &$options, &$params) {
    if ($entity == 'ContributionSoft' && $field == 'soft_credit_type_id') {
      if ($params['entity'] == 'contribution_soft') {
        foreach ($options as $optionValue => $optionLabel) {
          if ($optionValue == CRM_Someoneelsepays_Config::singleton()->getSepSoftCreditTypeId()) {
            unset($options[$optionValue]);
          }
        }
      }
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
            $membershipId = $form->getVar('_id');
            $sep = new CRM_Someoneelsepays_Sep('membership');
            $submitValues = $form->getVar('_submitValues');
            $sep->updateContributionContact($membershipId, $submitValues['sep_payer_id']);
          }
        break;
    }
  }

  /**
   * Method to process new membership payments or participant payment
   * @param $op
   * @param $objectName
   * @param $objectId
   * @param $objectRef
   */
  public static function post($op, $objectName, $objectId, $objectRef) {
    switch ($objectName) {
      case 'MembershipPayment':
        if ($op == 'create') {
          $sep = new CRM_Someoneelsepays_Sep('membership');
          if ($sep->isSepPayment($objectId)) {
            $softCredit = $sep->getSepSoftCreditForContribution($objectRef->contribution_id);
            if (!empty($softCredit)) {
              $sep->updateContributionContact($objectRef->membership_id, $softCredit['contact_id']);
              $delete = 'DELETE FROM civicrm_contribution_soft WHERE contribution_id = %1 AND soft_credit_type_id = %2';
              CRM_Core_DAO::executeQuery($delete, [
                1 => [$objectRef->contribution_id, 'Integer'],
                2 => [CRM_Someoneelsepays_Config::singleton()->getSepSoftCreditTypeId(), 'Integer'],
              ]);
              $sep->updateLineItemLabel($objectRef->contribution_id);
              $sep->updateContributionSource($objectRef->contribution_id);
            }
            else {
              CRM_Core_Error::debug_log_message(ts('Did not find an SEP soft credit unexpectedly in ' . __METHOD__));
            }
          }
        }
        break;
    }
    //todo update line item in post -> when SEP
  }

  /**
   * Method to get the SEP soft credit for the contribution
   *
   * @param $contributionId
   * @return array
   */
  private function getSepSoftCreditForContribution($contributionId) {
    $result = [];
    $query = 'SELECT * FROM civicrm_contribution_soft WHERE contribution_id = %1 AND soft_credit_type_id = %2';
    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$contributionId, 'Integer'],
      2 => [CRM_Someoneelsepays_Config::singleton()->getSepSoftCreditTypeId(), 'Integer'],
    ]);
    if ($dao->fetch()) {
      $result = CRM_Someoneelsepays_Utils::moveDaoToArray($dao);
    }
    return $result;
  }

  /**
   * Method to determine if membership or participant payment is a SEP case
   * (based on soft credit)
   *
   * @param $entityPaymentId
   * @return bool
   */
  public function isSepPayment($entityPaymentId) {
    $query = 'SELECT COUNT(*)
      FROM ' . $this->_entityTable . ' AS pay 
      JOIN civicrm_contribution_soft AS soft ON pay.contribution_id = soft.contribution_id AND soft.soft_credit_type_id = %1
      WHERE pay.id = %2';
    $count = CRM_Core_DAO::singleValueQuery($query, [
      1 => [CRM_Someoneelsepays_Config::singleton()->getSepSoftCreditTypeId(), 'Integer'],
      2 => [$entityPaymentId, 'Integer']
    ]);
    if ($count > 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to update contribution source showing the on behalf of
   *
   * @param $contributionId
   */
  private function updateContributionSource($contributionId) {
    $contributionQuery = "SELECT source FROM civicrm_contribution WHERE id = %1";
    $currentSource = CRM_Core_DAO::singleValueQuery($contributionQuery, [1 => [$contributionId, 'Integer']]);
    // keep the part before the : which holds the membership type or the event title and replace the second part
    $sourceParts = explode(':', $currentSource);
    $nameQuery = "SELECT contact.display_name 
    FROM " . $this->_entityTable . " ent
    JOIN " . $this->_baseTable . " base ON ent. " . $this->_entityIdColumn . " = base.id 
    JOIN civicrm_contact contact ON base.contact_id = contact.id
    WHERE ent.contribution_id = %1";
    $displayName = CRM_Core_DAO::singleValueQuery($nameQuery, [1 => [$contributionId, 'Integer']]);
    if ($displayName) {
      $newSource = $sourceParts[0] . ' (on behalf of ' . $displayName . ')';
      $update = "UPDATE civicrm_contribution SET source = %1 WHERE id = %2";
      CRM_Core_DAO::executeQuery($update, [
        1 => [$newSource, 'String'],
        2 => [$contributionId, 'Integer']
      ]);
    }
  }

  /**
   * Method to update the label of the line item
   *
   * @param $contributionId
   */
  private function updateLineItemLabel($contributionId) {
    $lineItemQuery = "SELECT label FROM civicrm_line_item WHERE contribution_id = %1";
    $currentLabel = CRM_Core_DAO::singleValueQuery($lineItemQuery, [1 => [$contributionId, 'Integer']]);
    $nameQuery = "SELECT contact.display_name 
    FROM " . $this->_entityTable . " ent
    JOIN " . $this->_baseTable . " base ON ent. " . $this->_entityIdColumn . " = base.id 
    JOIN civicrm_contact contact ON base.contact_id = contact.id
    WHERE ent.contribution_id = %1";
    $displayName = CRM_Core_DAO::singleValueQuery($nameQuery, [1 => [$contributionId, 'Integer']]);
    $currentLabel .= ts(' (on behalf of ') . $displayName . ')';
    $update = "UPDATE civicrm_line_item SET label = %1 WHERE contribution_id = %2";
    CRM_Core_DAO::executeQuery($update, [
      1 => [$currentLabel, 'String'],
      2 => [$contributionId, 'Integer'],
    ]);
  }

  /**
   * Method to update the contribution contact
   * Don't ask....contribution is attached to different contact when coming from UI or from
   * online membership page. So figuring out where it is coming from based on request values
   *
   * @param int $entityId
   * @param int $payerId
   */
  private function updateContributionContact($entityId, $payerId) {
    // when coming from UI, contribution is already on payer so only update if from online membership page
    $requestValues = CRM_Utils_Request::exportValues();
    if (isset($requestValues['honor'])) {
      // replace with entity contact if payer is empty
      if (empty($payerId)) {
        try {
          $payerId = civicrm_api3(ucfirst($this->_entityType), 'getvalue', [
            'id' => $entityId,
            'return' => 'contact_id',
          ]);
        } catch (CiviCRM_API3_Exception $ex) {
          CRM_Core_Error::debug_log_message(ts('Could not find contact_id for ' . $this->_entityType
              . 'with id ') . $entityId . ts(' with API ' . ucfirst($this->_entityType) . ' getvalue in ')
            . __METHOD__ . ' (extension org.civicoop.someoneelsepays)');
        }
      }
      if (!empty($payerId)) {
        $this->moveContribution($entityId, $payerId);
      }
    }
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
          $form->addEntityRef('sep_payer_id', ts('Select Contact to Change Payer'), [
            'api' => ['params' => ['is_deceased' => 0]],
          ]);
          $form->setDefaults(['sep_payer_id' => $sepData['payer_id']]);
          CRM_Core_Region::instance('page-body')->add([
            'template' => 'SepEdit.tpl']);
        }
        break;

      case CRM_Core_Action::ADD:
        $form->setDefaults(['soft_credit_type_id' => CRM_Someoneelsepays_Config::singleton()->getSepSoftCreditTypeId()]);
        CRM_Core_Region::instance('page-body')->add(['template' => 'SepAdd.tpl']);
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
        . $sepData['contribution_id'] . '&cid=' . $sepData['payer_id'] . '&action=view&context=contribution', TRUE);
      $editUrl = CRM_Utils_System::url('civicrm/contact/view/contribution', 'reset=1&action=update&id='
        . $sepData['contribution_id'] . '&cid=' . $sepData['payer_id'] . '&context=contribution', TRUE);
      $sepActionLinks[] = '<a class="action-item crm-hover-button" title="View Contribution" href="' . $viewUrl . '">' . ts("View") . '</a>';
      $sepActionLinks[] = '<a class="action-item crm-hover-button" title="Edit Contribution" href="' . $editUrl . '">' . ts("Edit") . '</a>';
      $form->assign('sep_data', $sepData);
      $form->assign('sep_action_links', $sepActionLinks);
      CRM_Core_Region::instance('page-body')->add(['template' => 'SepView.tpl']);
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
    $result = [];
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
    $queryParams = [1 => [$contributionId, 'Integer']];
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
    $result = [];
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
    $queryParams = [
      1 => [$contributionId, 'Integer'],
      2 => [$entityId, 'Integer'],
    ];
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
    $queryParams = [1 => [$contributionId, 'Integer']];
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
    $result = [];
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
    $queryParams = [1 => [$entityId, 'Integer']];
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
