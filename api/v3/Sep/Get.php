<?php
use CRM_Someoneelsepays_ExtensionUtil as E;

/**
 * Sep.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sep_Get_spec(&$spec) {
  $spec['payer_id'] = array(
    'title' => 'payer_id',
    'description' => 'Contact ID of the contact paying',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['beneficiary_id'] = array(
    'title' => 'beneficiary_id',
    'description' => 'Contact ID of the contact that is paid for by the payer',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['entity_type'] = array(
    'title' => 'entity_type',
    'description' => 'The entity that is being paid for (membership or participant)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['entity_id'] = array(
    'title' => 'entity_id',
    'description' => 'Either the membership_id or participant_id, depends on entity_type',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['contribution_id'] = array(
    'title' => 'contribution_id',
    'description' => 'The ID of the contribution',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['contact_id'] = array(
    'title' => 'contact_id',
    'description' => 'Get all payments where contact either pays or benefits',
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * Sep.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_sep_Get($params) {
  if (isset($params['entity_type'])) {
    $sep = new CRM_Someoneelsepays_Sep($params['entity_type']);
  }
  else {
    $sep = new CRM_Someoneelsepays_Sep();
  }
  $valid = $sep->validApiGetParams($params);
  if ($valid == TRUE) {
    return civicrm_api3_create_success($sep->apiGet($params), $params, 'Sep', 'Get');
  }
  else {
    throw new API_Exception(ts('Invalid parameters for Sep Get: ' . $valid), 1010, array(
      'domain' => 'org.civicoop.someoneelsepays',
      ));
  }
}
