<?php
use CRM_Someoneelsepays_ExtensionUtil as E;

/**
 * Sep.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_sep_Create_spec(&$spec) {
  $spec['payer_id'] = array(
    'title' => 'payer_id',
    'description' => 'Contact ID of the contact paying',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['entity_type'] = array(
    'title' => 'entity_type',
    'api.required' => 1,
    'description' => 'The entity that is being paid for (membership or participant)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['entity_id'] = array(
    'title' => 'entity_id',
    'api.required' => 1,
    'description' => 'Either the membership_id or participant_id, depends on entity_type',
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * Sep.Create API
 *
 * @param array $params
 * @return array API result descriptor
 */
function civicrm_api3_sep_Create($params) {
  $sep = new CRM_Someoneelsepays_Sep(strtolower($params['entity_type']));
  $created = $sep->create($params);
  if ($created != FALSE) {
    $result = array(
      'version' => 3,
      'count' => 1,
      'is_error' => 0,
      'values' => $created,
    );
  }
  else {
    $result = array(
      'is_error' => '1',
      'error_message' => ts('Could not create someone else pays, check error logs!'),
    );
  }
  return $result;
}
