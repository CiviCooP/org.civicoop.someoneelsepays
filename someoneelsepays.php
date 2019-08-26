<?php

require_once 'someoneelsepays.civix.php';
use CRM_Someoneelsepays_ExtensionUtil as E;


/**
 * Implements post().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post/
 */
function someoneelsepays_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  $validObjects = [
    'LineItem',
    'MembershipPayment',
    ];
  if (in_array($objectName, $validObjects)) {
    CRM_Someoneelsepays_Sep::post($op, $objectName, $objectId, $objectRef);
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 */
function someoneelsepays_civicrm_buildForm($formName, &$form) {
  CRM_Someoneelsepays_Sep::buildForm($formName, $form);
}

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_validateForm/
 */
function someoneelsepays_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  Civi::log()->debug('formName is ' . $formName);
  CRM_Someoneelsepays_Sep::validateForm($formName, $fields, $files, $form, $errors);
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postProcess/
 */
function someoneelsepays_civicrm_postProcess($formName, &$form) {
  CRM_Someoneelsepays_Sep::postProcess($formName, $form);
}

/**
 * Implements hook_civicrm_fieldOptions().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_fieldOptions/
 */
function someoneelsepays_civicrm_fieldOptions($entity, $field, &$options, $params) {
  if (!class_exists(' CRM_Someoneelsepays_Sep')) {
    require_once 'CRM/Someoneelsepays/Sep.php';
  }
  CRM_Someoneelsepays_Sep::fieldOptions($entity, $field, $options, $params);
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function someoneelsepays_civicrm_config(&$config) {
  _someoneelsepays_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function someoneelsepays_civicrm_xmlMenu(&$files) {
  _someoneelsepays_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function someoneelsepays_civicrm_install() {
  _someoneelsepays_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function someoneelsepays_civicrm_postInstall() {
  _someoneelsepays_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function someoneelsepays_civicrm_uninstall() {
  _someoneelsepays_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function someoneelsepays_civicrm_enable() {
  _someoneelsepays_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function someoneelsepays_civicrm_disable() {
  _someoneelsepays_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function someoneelsepays_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _someoneelsepays_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function someoneelsepays_civicrm_managed(&$entities) {
  _someoneelsepays_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function someoneelsepays_civicrm_caseTypes(&$caseTypes) {
  _someoneelsepays_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function someoneelsepays_civicrm_angularModules(&$angularModules) {
  _someoneelsepays_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function someoneelsepays_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _someoneelsepays_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function someoneelsepays_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function someoneelsepays_civicrm_navigationMenu(&$menu) {
  _someoneelsepays_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('The Page'),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _someoneelsepays_civix_navigationMenu($menu);
} // */
