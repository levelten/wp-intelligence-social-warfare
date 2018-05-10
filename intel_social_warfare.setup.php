<?php

/**
 * Included to assist in initial setup of plugin
 *
 * @since      1.0.0
 *
 * @package    Intelligence
 */

if (!is_callable('intel_setup')) {
	include_once intel_social_warfare()->dir . 'intel_com/intel.setup.php';
}

class Intel_Social_Warfare_Setup extends Intel_Setup {

	public $plugin_un = 'intel_social_warfare';

	/*
	 * Include any methods from Intel_Setup you want to override
	 */

}

function intel_social_warfare_setup() {
	return Intel_Social_Warfare_Setup::instance();
}
intel_social_warfare_setup();
