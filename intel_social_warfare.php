<?php
/**
* Google Analytics Intelligence for Social Warfare bootstrap file
*
* This file is read by WordPress to generate the plugin information in the plugin
* admin area. This file also includes all of the dependencies used by the plugin,
* registers the activation and deactivation functions, and defines a function
* that starts the plugin.
*
* @link              getlevelten.com/blog/tom
* @since             1.0.0
* @package           Intelligence
*
* @wordpress-plugin
* Plugin Name:       Google Analytics Intelligence for Social Warfare
* Plugin URI:        https://wordpress.org/plugins/intelligence-social_warfare
* Description:       Automates Google Analytics tracking for Social Warfare.
* Version:           1.0.0
* Author:            LevelTen
* Author URI:        https://intelligencewp.com
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       intel_social_warfare
* Domain Path:       /languages
* GitHub Plugin URI: https://github.com/levelten/wp-intelligence-social_warfare
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

define('INTEL_SOCIAL_WARFARE_VER', '1.0.0');

/**
 * Class Intel_Example
 */
final class Intel_Social_Warfare {

  protected $version = INTEL_SOCIAL_WARFARE_VER;

  /**
   * intel_plugin_info
   *
   * @var array
   */
  public $plugin_info = array();

  /**
   * Plugin unique name
   *
   * @var string
   */
  public $plugin_un = 'intel_social_warfare';

  /**
   * Plugin Directory
   *
   * @since 1.0.0
   * @var string $dir
   */
  public $dir = '';

  /**
   * Plugin URL
   *
   * @since 1.0.0
   * @var string $url
   */
  public $url = '';

  /**
   * @var Intel_Social_Warfare
   * @since 1.0.0
   */
  private static $instance;

  /**
   * Main Plugin Instance
   *
   * Insures that only one instance of a plugin class exists in memory at any one
   * time. Also prevents needing to define globals all over the place.
   *
   * @since 1.0.2
   * @static
   * @static var array $instance
   * @return Intel_Social_Warfare Instance
   */
  public static function instance($options = array()) {
    if (null === static::$instance) {
      static::$instance = new static($options);
    }

    return static::$instance;
  }

  /**
   * Constructor.
   */
  public function __construct() {
    global $wp;

    $this->plugin_info = $this->intel_plugin_info();

    $this->dir = plugin_dir_path(__FILE__);

    $this->url = plugin_dir_url(__FILE__);

    // Register hook_admin_menu()
    add_filter('admin_menu', array( $this, 'intel_admin_menu' ));

    /*
     * Intelligence hooks
     */

    // Register hook_intel_system_info()
    add_filter('intel_system_info', array( $this, 'intel_system_info' ));

    // Registers hook_wp_loaded()
    add_action('wp_loaded', array( $this, 'wp_loaded' ));
    
    // add _intel_intel_script_info to hook_intel_script_info
    add_filter('intel_intel_script_info', array( $this, 'intel_intel_script_info'));
    
    // Register hook_intel_intel_event_info
    add_filter('intel_intel_event_info', array( $this, 'intel_intel_event_info'));

    // Register hook_intel_menu_info()
    add_filter('intel_menu_info', array( $this, 'intel_menu_info' ));

    // Register hook_intel_demo_pages()
    add_filter('intel_demo_posts', array( $this, 'intel_demo_posts' ));

  }
  
  /**
   * Implements hook_admin_menu
   *
   * Adds settings page on Admin > Social Warfare > Intelligence
   */
  public function intel_admin_menu(){
    global $menu;
    // Add Social Warfare subpage with links to intel settings
    $social_warfare_menu_slug = 'social-warfare';
    $page_title = 'Intelligence for Social Warfare Settings';
    $menu_title = 'Intelligence Settings';
    $menu_slug = 'social_warfare_intel_settings';
    $callback = array($this, 'intel_social_warfare_settings_page');
    if(!in_array($social_warfare_menu_slug, $menu)){
      add_submenu_page(
          $social_warfare_menu_slug,
          $page_title,
          $menu_title,
          'manage_options',
          $menu_slug,
          $callback
      );
    }
  }

  static function intel_social_warfare_admin_social_tracking_form($form, &$form_state) {
    $events_un = array_keys(self::$instance->intel_intel_event_info());
    $goals = intel_goal_load(NULL, array('index_by' => 'ga_id'));
    foreach($events_un as $event_un){
      $event = intel_get_intel_event_info($event_un);
      $eventgoal_options = intel_social_warfare_get_intel_event_eventgoal_options($event_un);
      $l_options = Intel_Df::l_options_add_destination(Intel_Df::current_path());
      $form[$event_un] = array(
        '#type' => 'fieldset',
        '#title' => Intel_Df::t($event['title']),
        '#collapsible' => FALSE,
        //'#collapsed' => TRUE,
        //'#description' => $event['description'],
        '#tree' => TRUE,
      );
      $form[$event_un]['inline_wrapper_1'] = array(
        '#type' => 'markup',
        '#markup' => '<div class="pull-left">',
      );
      $default = $event_un;
      if ($event['mode'] == '') {
        $default .= '-';
      }
      elseif ($event['mode'] == 'goal' && !empty($goals[$event['ga_id']])) {
        $default .= '__' . $goals[$event['ga_id']]['un'];
      }
      $form[$event_un]['intel_social_warfare_event'] = array(
        '#type' => 'select',
        '#title' => Intel_Df::t($event['category']. ' event/goal'),
        '#options' => $eventgoal_options,
        '#default_value' => $default,
        '#description' => Intel_Df::t('Select the goal or event you would like to trigger to be tracked in analytics for this action.'),
        '#suffix' => '<div class="add-goal-link text-right" style="margin-top: -12px;">' . Intel_Df::l(Intel_Df::t('Add Goal'), 'admin/config/intel/settings/goal/add', $l_options) . '</div>',
      );
      $form[$event_un]['inline_wrapper_2'] = array(
        '#type' => 'markup',
        '#markup' => '</div><div class="clearfix"></div>',
      );
      $l_options = Intel_Df::l_options_add_target('intel_admin_config_scoring');
      $desc = Intel_Df::t('Each goal has a default site wide value in the !scoring_admin, but you can override that value per form.', array(
        '!scoring_admin' => Intel_Df::l( Intel_Df::t('Intelligence scoring admin'), 'admin/config/intel/settings/scoring', $l_options ),
      ));
      $desc .= ' ' . Intel_Df::t('If you would like to use a custom goal/event value, enter it here otherwise leave the field blank to use the defaults.');
      $form[$event_un]['intel_social_warfare_value'] = array(
        '#type' => 'textfield',
        '#title' => Intel_Df::t($event['category'] . ' value'),
        '#default_value' => $event['value'],
        '#description' => $desc,
        '#size' => 8,
      );
      $desc = Intel_Df::t('Enable tracking as a social interaction as well as an event.');
      $form[$event_un]['intel_social_warfare_social_interaction'] = array(
        '#type' => 'checkbox',
        '#title' => Intel_Df::t('Track as social interaction'),
        '#default_value' => ($event['social_action']== '') ? FALSE : TRUE,
        '#description' => $desc,
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => Intel_Df::t('Save'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => Intel_Df::t('Cancel'),
      '#href' => !empty($_GET['destination']) ? $_GET['destination'] : 'wp-admin/admin.php?page=social_warfare_intel_settings',
    );

    return $form;
  }

  static function intel_social_warfare_admin_social_tracking_form_submit(&$form, &$form_state) {
    $values = $form_state['values'];

    $events_info = self::$instance->intel_intel_event_info();
    $events_custom = get_option('intel_intel_events_custom', array());

    $intel_goals = intel_goal_load(null, array('index_by' => 'name'));

    foreach ($events_info as $key => $event_info) {
      if(empty($values[$key]['intel_social_warfare_event'])){
        continue;
      }

      $event = !empty($events_custom[$key]) ? $events_custom[$key] : array();


      $goal = explode('__',$values[$key]['intel_social_warfare_event']);
      //goal valued blank for std
      if(count($goal) > 1){
        $event['mode'] = 'goal';
        $event['ga_id'] = $intel_goals[$goal[1]]['ga_id'];
      }
      else {
        $event['mode'] = (substr($goal[0], -1) == '-') ? '' : 'valued';
      }
      $event['value'] = floatval($values[$key]['intel_social_warfare_value']);
      $event['key'] = $key;
      // if social interaction is set to false, add property with empty string
      // to custom settings to disable tracking
      // If social interactions enabled, remove custom override so coded property
      // value is used.
      if (empty($values[$key]['intel_social_warfare_social_interaction'])) {
        $event['social_action'] = '';
      }
      elseif (isset($event['social_action'])) {
        unset($event['social_action']);
      }

      intel_intel_event_save($event);
    }

    $msg = Intel_Df::t('Social Warfare events have been updated.', array());
    // Doesn't show up on redirect
    Intel_Df::drupal_set_message($msg);
    return;
  }

  
  /*
   * Settings page for Admin > Social Warfare > Intelligence
   */
  public function intel_social_warfare_settings_page() {
    $items = array();

    $items[] = '<div class="wrap">';
    $items[] = '<h1>' . esc_html__( 'Intelligence Settings', $this->plugin_un ) . '</h1>';
    $items[] = '</div>';


    if($this->is_intel_installed()) {
      $connect_desc = __('Connected');
    }
    else { //TODO check this
      $connect_desc = __('Not connected.', $this->plugin_un);
      $connect_desc .= ' ' . sprintf(
          __( ' %sSetup Intelligence%s', $this->plugin_un ),
          '<a href="' . Intel_Setup()->get_plugin_setup_url() . '" class="button">', '</a>'
        );
    }

    $items[] = '<table class="form-table">';
    $items[] = '<tbody>';
    $items[] = '<tr>';
    $items[] = '<th>' . esc_html__( 'Intelligence API', $this->plugin_un ) . '</th>';
    $items[] = '<td>' . $connect_desc . '</td>';
    $items[] = '</tr>';
    
    if ($this->is_intel_installed()) {
      // events defined in intel_intel_event_info
      $event_uns = array_keys($this->intel_intel_event_info());
      foreach ($event_uns as $event_un) {
        $event = intel_get_intel_event_info($event_un);
        // check if editable?
        //$eventgoal_options = intel_get_intel_events_overridable_fields($event);
        // Translate from machine name.
        $event_types = array(
          '_' => Intel_Df::t('(Not Set)'),
          '' => Intel_Df::t('Standard event'),
          'valued' => Intel_Df::t('Valued event'),
          'goal' => Intel_Df::t('Goal event'),
        );
        $value = $event_types[$event['mode']];
        // Translate from machine name.
        $goals = get_option('intel_goals', array());
        $goal_types = array();
        foreach ($goals AS $key => $goal) {
          if (empty($goal['context']['general'])) {
            continue;
          }
          $goal_types[$goal['ga_id']] = $goal['title'];
        }
        // "event type : goal [change button]"
        if (isset($event['ga_id']) && !empty($goal_types[$event['ga_id']])) {
          $value .= ': '. $goal_types[$event['ga_id']];
        }
        $l_options = Intel_Df::l_options_add_destination('wp-admin/admin.php?page=social_warfare_intel_settings');
        $l_options['attributes'] = array(
          'class' => array('button'),
        );
        $change = Intel_Df::l(esc_html__('Change', $this->plugin_un), 'admin/config/intel/settings/intel_event/'.$this->plugin_un, $l_options);
        $items[] = '<tr>';
        $items[] = '<th>' . esc_html__( $event['title'], $this->plugin_un ) . '</th>';
        $items[] = '<td>' . $value . '</td>';
        $items[] = '<td>' . $change . '</td>';
        $items[] = '</tr>';
      }
    }
    $items[] = '</tbody>';
    $items[] = '</table>';

    $output = implode("\n", $items);
    echo $output;
  }

  /**
   * Implements hook_wp_loaded()
   *
   * Used to check if Intel is not loaded and include setup process if needed.
   * Alternatively this check can be done in hook_admin_menu() if the plugin
   * implements hook_admin_menu()
   */
  public function wp_loaded() {
    // check if Intel is installed, add setup processing if not
    //TODO move to intel_admin_menu
    if (!$this->is_intel_installed()) {
      require_once( $this->dir . $this->plugin_un . '.setup.php' );
    }
  }

  /**
   * Returns if Intelligence plugin is installed
   * @param string $level
   * @return mixed
   */
  public function is_intel_installed($level = 'min') {
    static $flags = array();
    if (!isset($flags[$level])) {
      $flags[$level] = (is_callable('intel_is_installed')) ? intel_is_installed($level) : FALSE;
    }
    return $flags[$level];
  }

  /**
   * Provides plugin data for hook_intel_system_info
   * @param array $info
   * @return array
   */
  function intel_plugin_info($info = array()) {
    $info = array(
      // The unique name for this plugin
      'plugin_un' => $this->plugin_un,
      // The unique name for this plugin
      'plugin_version' => $this->version,
      // Title of the plugin
      'plugin_title' => __('Google Analytics Intelligence for Social Warfare', $this->plugin_un),
      // Shorter version of title used when reduced characters are desired
      'plugin_title_short' => __('GA Intelligence for Social Warfare', $this->plugin_un),
      // Plugin slug - name of directory containing plugin
      'plugin_slug' => 'intel-social_warfare',
      // Main plugin file
      'plugin_file' => 'intel_social_warfare.php',
      // The server path to the plugin files directory
      'plugin_dir' => $this->dir,
      // The browser path to the plugin files directory
      'plugin_url' => $this->url,
      // The install file for the plugin if different than [plugin_un].install
      // Used to auto discover database updates
      'update_file' => 'intel_social_warfare.install.php', // default [plugin_un].install
      // If this plugin extends a plugin other than Intelligience, include that
      // plugin's info in 'extends_' properties
      // The extended plugin's unique name
      'extends_plugin_un' => 'social-warfare',
      // the extended plugin's title
      'extends_plugin_title' => __('Social Warfare', 'social-warfare'),
    );
    return $info;
  }

  /**
   * Implements hook_intel_system_info()
   *
   * Registers plugin with intel_system
   *
   * @param array $info
   * @return array
   */
  function intel_system_info($info = array()) {
    // array of plugin info indexed by plugin_un
    $info[$this->plugin_un] = $this->intel_plugin_info();
    return $info;
  }

  /**
   * Implements hook_intel_menu_info()
   *
   * @param array $items
   * @return array
   */
  public function intel_menu_info($items = array()) {
    // route for Admin > Intelligence > Settings > Setup > Social Warfare
    $items['admin/config/intel/settings/setup/' . $this->plugin_un] = array(
      'title' => 'Setup',
      'description' => Intel_Df::t('Google Analytics Intelligence for Social Warfare initial setup'),
      'page callback' => $this->plugin_un . '_admin_setup_page',
      'access callback' => 'user_access',
      'access arguments' => array('admin intel'),
      'type' => Intel_Df::MENU_LOCAL_TASK,
      'file' => 'admin/' . $this->plugin_un . '.admin_setup.php',
      'file path' => $this->dir,
    );
    // route for Admin > Intelligence > Help > Demo > Social Warfare
    $items['admin/help/demo/' . $this->plugin_un] = array(
      'title' => $this->plugin_info['extends_plugin_title'],
      'page callback' => array($this, 'intel_admin_help_demo_page'),
      'access callback' => 'user_access',
      'access arguments' => array('admin intel'),
      'intel_install_access' => 'min',
      'type' => Intel_Df::MENU_LOCAL_TASK,
      'weight' => 10,
    );
      // route for Admin > Intelligence > Settings > Event > Social Warfare
      $items['admin/config/intel/settings/intel_event/' . $this->plugin_un] = array(
        'title' => 'Social Warfare Social Tracking',
        'description' => Intel_Df::t('Event and goal configuration.'),
        'page callback' => 'drupal_get_form',
        'page arguments' => array('Intel_Social_Warfare::intel_social_warfare_admin_social_tracking_form'),
        'access callback' => 'user_access',
        'access arguments' => array('admin intel'),
        'type' => Intel_Df::MENU_LOCAL_TASK,
        'file' => 'intel_social_warfare.php',
        'file path' => $this->dir,
        'weight' => 5,
      );
    return $items;
  }
  
  
  /**
   * Implements hook_intel_intel_script_info()
   * 
   * Adds Social Warfare tracking script to the site.
   */
  function intel_intel_script_info($info = array()) {
    $info['social_warfare'] = array(
      'title' => Intel_Df::t('Social Warfare'),
      'description' => Intel_Df::t('Tracks Social Warfare shares and clickbacks.'),
      'path' => $this->url . 'js/l10intel_social_warfare.js',
      'enabled' => 1,
      'selectable' => 0,
    );

    return $info;
  }
  
  /**
   * Implements hook_intel_intel_event_info
   *
   * Adds configurable events attached to Social Warfare interactions. 
   */
  function intel_intel_event_info($event = array()) {
    $networks = apply_filters( 'swp_button_options', array());
    $network_map = array();
    foreach($networks['content'] as $network_id => $info){
      $network_title = $info['content'];
      $network_map[$network_id] = $network_title;
    }
    // Give map to js to get name of social network for Event push.
    intel()->add_js(array('intel_social_warfare' => array('map' =>$network_map)), 'setting');
    
    $event['intel_social_warfare_share_click'] = array(
      'title' => Intel_Df::t('Social Warfare share click'),
      'category' => Intel_Df::t('Social share click'),
      'description' => Intel_Df::t('Click on Social Warfare share button'),
      'mode' => 'valued',
      //'valued_event' => 1,
      'value' => 10,
      //'selector' => '.social_warfare-smartlayers',
      'on_event' => 'none',
      'enable' => 1,
      'overridable' => array(
      ),
      'social_action' => 'share',
      'plugin_un' => $this->plugin_un,
      //'js_setting' => 1,
    );
    
    // $event['intel_social_warfare_follow_click'] = array(
    //   'title' => Intel_Df::t('Social Warfare follow click'),
    //   'category' => Intel_Df::t('Social follow click'),
    //   'description' => Intel_Df::t('Click on Social Warfare follow button'),
    //   'mode' => 'valued',
    //   //'valued_event' => 1,
    //   'value' => 10,
    //   //'selector' => '.io-social-share-track',
    //   'on_event' => 'none',
    //   'enable' => 1,
    //   'overridable' => array(
    //   ),
    //   'social_action' => 'follow',
    //   'plugin_un' => $this->plugin_un,
    //   //'js_setting' => 1,
    // );
    return $event;
  }

  /*
   * Provides an Intelligence > Help > Demo > Social Warfare Example page
   */
  public function intel_admin_help_demo_page() {
    $output = '';

    $demo_mode = get_option('intel_demo_mode', 0);

    /*
    if (empty($demo_mode)) {
      $msg = Intel_Df::t('Demo is currently disabled for non logged in users. Go to demo settings to enable.');
      Intel_Df::drupal_set_message($msg, 'warning');
    }
    */

    $output .= '<div class="card">';
    $output .= '<div class="card-block clearfix">';

    $output .= '<p class="lead">';
    $output .= Intel_Df::t('Try out Social Warfare tracking!');
    //$output .= ' ' . Intel_Df::t('This tutorial will walk you through the essentials of extending Google Analytics using Intelligence to create results oriented analytics.');
    $output .= '</p>';

    /*
    $l_options = Intel_Df::l_options_add_class('btn btn-info');
    $l_options = Intel_Df::l_options_add_destination(Intel_Df::current_path(), $l_options);
    $output .= Intel_Df::l( Intel_Df::t('Demo settings'), 'admin/config/intel/settings/general/demo', $l_options) . '<br><br>';
    */

    $output .= '<div class="row">';
    $output .= '<div class="col-md-6">';
    $output .= '<p>';
    $output .= '<h3>' . Intel_Df::t('First') . '</h3>';
    $output .= __('Launch Google Analytics to see conversions in real-time:', $this->plugin_un);
    $output .= '</p>';

    $output .= '<div>';

    $l_options = Intel_Df::l_options_add_target('ga');
    $l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
    $url = 	$url = intel_get_ga_report_url('rt_event');
    $output .= Intel_Df::l( Intel_Df::t('View real-time events'), $url, $l_options);

    $output .= '<br>';

    $l_options = Intel_Df::l_options_add_target('ga');
    $l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
    $url = 	$url = intel_get_ga_report_url('rt_goal');
    $output .= Intel_Df::l( Intel_Df::t('View real-time conversion goals'), $url, $l_options);

    $output .= '</div>';
    $output .= '</div>'; // end col-x-6

    $output .= '<div class="col-md-6">';

    $output .= '<p>';
    $output .= '<h3>' . Intel_Df::t('Next') . '</h3>';
    $output .= __('Test out your different social media buttons.', $this->plugin_un);
    $output .= '</p>';

    $l_options = Intel_Df::l_options_add_target('example_demo');
    $l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
    $l_options['query'] = array();
    $output .= Intel_Df::l( __('Try It Now', $this->plugin_un), 'intelligence/demo/' . $this->plugin_un, $l_options);

    $output .= '</div>'; // end col-x-6
    $output .= '</div>'; // end row

    $output .= '</div>'; // end card-block
    $output .= '</div>'; // end card

    return $output;
  }

  /**
   * Implements hook_intel_demo_pages()
   *
   * Adds a demo page to test tracking for this plugin.
   *
   * @param array $posts
   * @return array
   */
  public function intel_demo_posts($posts = array()) {
    $id = -1 * (count($posts) + 1);

    $content = '';
    $content .= __('Google Analytics Intelligence for Social Warfare Demo', $this->plugin_un);

    $content .= '<p>';
    $content .= __('Configure Social Warfare widget', $this->plugin_un);
    $content .= ' ' . Intel_Df::l(__('here', $this->plugin_un), 'admin.php?page=social-warfare') . '.';
    $content .= ' ' . __('Then any clicks to social icons will be tracked in Google Analytics!', $this->plugin_un);
    $content .= '</p>';

    $posts["$id"] = array(
      'ID' => $id,
      'post_type' => 'page',
      'post_title' => 'Social Warfare Demo',
      'post_content' => $content,
      'intel_demo' => array(
        'url' => 'intelligence/demo/' . $this->plugin_un,
        // don't let user override page content
        'overridable' => 0,
      ),
    );

    return $posts;
  }
}

/**
 * Takes in an event or even un and returns 
 */
function intel_social_warfare_get_intel_event_eventgoal_options($event_un, $options = array()){
  
  if(!is_array($event_un)){
    $event = intel_get_intel_event_info($event_un);
  }
  else {
    $event = $event_un;
  }

  $field_options = array();

  // only thing different per addon
  $field_options[$event_un . '-'] =  Intel_Df::t( 'Event:') . ' ' . $event['category'];
  $field_options[$event_un] =  Intel_Df::t( 'Valued event:') . ' ' . $event['category'];
  
  $goals = intel_goal_load();
  foreach ($goals AS $key => $goal) {
    if (empty($goal['context']['general'])) {
      continue;
    }
    $field_options[$event_un . '__' . $key] = Intel_Df::t( 'Goal: ') . $goal['title'];
  }

  return $field_options;
}

function intel_social_warfare() {
  return Intel_social_warfare::instance();
}
global $intel_social_warfare;
$intel_social_warfare = intel_social_warfare();

/*
 * Implements hook_register_activation_hook()
 *
 * The code that runs during plugin activation.
 *
 * Initializes Intel's database schema update system
 */
function intel_social_warfare_activation_hook() {
  // plugin specific installation code.
  // initializes data for plugin when first installed
  require_once plugin_dir_path( __FILE__ ) . 'intel_social_warfare.install.php';
  intel_social_warfare_install();

  // check if Intel is active
  if (is_callable('intel_activate_plugin')) {
    // initializes Intel's database update management system
    intel_activate_plugin('intel_social_warfare');
  }
}
register_activation_hook( __FILE__, 'intel_social_warfare_activation_hook' );

/**
 * Implements hook_register_deactivation_hook()
 *
 * The code that runs during plugin deactivation.
 */
function intel_social_warfare_deactivate_hook() {

}
register_deactivation_hook( __FILE__, 'intel_social_warfare_deactivate_hook' );

/*
 * Implements hook_register_uninstall_hook()
 *
 * Runs when plugin is Deleted (uninstalled)
 */
function intel_social_warfare_uninstall_hook() {
  // plugin specific installation code.
  // remove plugin data from database before plugin is uninstalled
  require_once plugin_dir_path( __FILE__ ) . 'intel_social_warfare.install.php';
  intel_social_warfare_uninstall();

  // check if Intel is active
  if (is_callable('intel_uninstall_plugin')) {
    // cleans up intel plugin data
    intel_uninstall_plugin('intel_social_warfare');
  }
}
register_uninstall_hook( __FILE__, 'intel_social_warfare_uninstall_hook' );
