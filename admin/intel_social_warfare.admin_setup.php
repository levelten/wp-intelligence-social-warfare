<?php
/**
 * @file
 * Admin configuration management
 */

include_once INTEL_DIR . 'includes/intel.wizard.php';

include_once INTEL_DIR . 'admin/intel.admin_setup.php';

/**
 * Wizard settings for Intelligence for Social Warfare.
 */
function intel_social_warfare_admin_setup_wizard_info($items = array()) {
  global $intel_social_warfare;

  $info = array(
    'title' => $intel_social_warfare->plugin_info['plugin_title'] . ' ' . __('setup'),
    'un' => 'intel_social_warfare_setup',
    'callback_prefix' => 'intel_social_warfare_admin_setup',
    'steps' => array(),
  );

  $info['steps']['intel_plugin'] = array(
    'title' => Intel_Df::t('Intelligence plugin'),
    'action_img_src' => INTEL_URL . '/images/setup_base_ga_action.png',
  );

  $info['steps']['intel_profile'] = array(
    'title' => Intel_Df::t('Intelligence connect'),
    'action_img_src' => INTEL_URL . '/images/setup_intel_action.png',
  );

  $info['steps']['event_tracking'] = array(
    'title' => __('Tracking configuration', 'intel_social_warfare'),
    'action_img_src' => INTEL_URL . '/images/setup_intel_action.png',
  );


  $info['steps']['finish'] = array(
    'title' => Intel_Df::t('Finish'),
    'submit_button_text' => '',
    'completed' => 1,
  );

  return $info;
}

/**
 * Instantiates Wizard.
 */
function intel_social_warfare_admin_setup_page() {
  $wizard_info = intel_social_warfare_admin_setup_wizard_info();
  $form = Intel_Form::drupal_get_form('intel_wizard_form', $wizard_info);
  return Intel_Df::render($form);
}

/**
 * 
 */
function intel_social_warfare_admin_setup_intel_plugin($form, &$form_state) {
  $f = array();

  include_once intel_social_warfare()->dir . 'intel_social_warfare.setup.php';

  $instructions = intel_social_warfare_setup()->get_intel_install_instructions();

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => $instructions,
  );

  return $f;
}

/**
 * Checks for base Intelligence plug installation.
 */
function intel_social_warfare_admin_setup_intel_plugin_check($form, &$form_state) {
  include_once INTEL_DIR . 'includes/intel.ga.php';

  $status = array();

  if (is_callable('intel')) {
    $status['success'] = 1;
  }
  else {
    $status['error_msg'] = Intel_Df::t('Intelligence plugin has not been activated.');
    $status['error_msg'] .= ' ' . Intel_Df::t('Please install and activate before proceeding.');
  }

  return $status;
}

/**
 * Validates Intelligence for Social Warfare installation.
 */
function intel_social_warfare_admin_setup_intel_plugin_validate($form, &$form_state) {
  if (!empty($status['error_msg'])) {
    Intel_Form::form_set_error('none', $status['error_msg']);
  }
}

/**
 * Lets user configure base Intelligence API Settings.
 */
function intel_social_warfare_admin_setup_intel_profile($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.php';
  $options = array(
    'imapi_property_setup' => array(
      'callback_destination' => 'admin/config/intel/settings/setup/intel_social_warfare',
    ),
  );
  return intel_admin_setup_intel_profile($form, $form_state, $options);
}

/**
 * Checks GA configurations.
 */
function intel_social_warfare_admin_setup_intel_profile_check($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.php';
  return intel_admin_setup_intel_profile_check($form, $form_state);
}

/**
 * Validates base Intelligence profile configuration.
 */
function intel_social_warfare_admin_setup_intel_profile_validate($form, &$form_state, $status) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.php';
  return intel_admin_setup_intel_profile_validate($form, $form_state, $status);
}

/**
 * Locks in profile and prompts for GA Dashboard for WP if not installed.
 */
function intel_social_warfare_admin_setup_intel_profile_submit($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.php';
  return intel_admin_setup_intel_profile_submit($form, $form_state);
}

/**
 * Configures Social Warfare tracking settings.
 */
function intel_social_warfare_admin_setup_event_tracking($form, &$form_state) {
  global $intel_social_warfare;
  return $intel_social_warfare->intel_social_warfare_admin_social_tracking_form($form, $form_state);
}

function intel_social_warfare_admin_setup_event_tracking_check($form, &$form_state) {

  $status = array();

  $wizard_state = $form_state['wizard_state'];
  if (isset($wizard_state['successes']) && in_array('event_tracking', $wizard_state['successes'])) {
    $status['success'] = 1;
  }
  return $status;
}

function intel_social_warfare_admin_setup_event_tracking_validate($form, &$form_state, $status) {

}

function intel_social_warfare_admin_setup_event_tracking_submit($form, &$form_state) {
  global $intel_social_warfare;

  $wizard_state = &$form_state['wizard_state'];
  if (!in_array('event_tracking', $wizard_state['successes'])) {
    $wizard_state['successes'][] = 'event_tracking';
  }

  return $intel_social_warfare->intel_social_warfare_admin_social_tracking_form_submit($form, $form_state);
}

/**
 * Installation success page.
 */
function intel_social_warfare_admin_setup_finish($form, &$form_state) {
  global $intel_social_warfare;

  $f = array();

  $markup = '';
  $markup .= '<div class="row">';
  $markup .= '<div class="col-xs-7">';
  $f['markup_0'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );

  $items = array();

  $items[] = '<div class="text-center">';
  $items[] = '<h3>' . Intel_Df::t('Congratulations') . '</h3>';

  $items[] = '<p>';
  $items[] = $intel_social_warfare->plugin_info['extends_plugin_title'] . ' ' . __(' social interactions are now being tracked!', 'intel_social_warfare');
  $items[] = '</p>';

  $items[] = '<p>';
  $items[] = '<strong>' . Intel_Df::t('Go ahead, give it a try:') . '</strong>';
  $l_options = Intel_Df::l_options_add_class('btn btn-info');
  $items[] = '<br>' . Intel_Df::l( Intel_Df::t('Test it now!'), 'admin/help/demo/' . $intel_social_warfare->plugin_un, $l_options);
  $items[] = '</p>';

  $items[] = '</div>';

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => implode(' ', $items),
  );

  $markup = '';
  $markup .= '</div>';
  $markup .= '<div class="col-xs-5">';
  $markup .= '<image src="' . INTEL_URL . '/images/setup_finish_right.png" class="img-responsive" >';
  $markup .= '</div>';
  $markup .= '</div>';
  $f['markup_1'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );

  // clear gf_setup as active setup wizard
  update_option('intel_setup', array());

  return $f;
}
