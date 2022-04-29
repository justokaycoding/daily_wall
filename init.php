<?php
/*
Plugin Name: Daily Wall
Version: 0.3
Description: Post inspiration text to a website.
Author: John Hines
Author URI: https://justokaycoding.com/
*/

/*
Daily Wall is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Daily Wall is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Daily Wall. If not, see https://justokaycoding.com/.
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class dailyWall {
  public $dw;

  public function __construct() {
    add_action('wp_enqueue_scripts', array( $this,'dw_scripts_client' ));
    add_action('admin_enqueue_scripts', array( $this,'dw_scripts_admin'));
    add_action( 'page_template', array( $this,'dw_template'));
    add_action( 'init', array( $this,'dw_template_delete'));
    add_action( 'admin_menu', array( $this, 'dw_template_Plugin_Page' ) );
    add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'my_plugin_action_links' ) );

    add_action( 'wp_ajax_js_callback_action', array( $this, 'js_callback' ) );
    add_action( 'wp_ajax_nopriv_js_callback_action', array( $this, 'js_callback' ) );

    register_activation_hook( __FILE__, array($this, 'plugin_activated' ));
    register_deactivation_hook( __FILE__, array($this, 'plugin_deactivated' ));

    require_once( plugin_dir_path(__FILE__) . 'includes/sql.php');
    $this->dw = new dw_sql();
  }

  function dw_scripts_client () {
    // add logical add css on daily wall page
    $options = unserialize(get_option( 'dw_settingsPage' ));
    if ( is_page( $options['dw_page'] ) ) {
      wp_enqueue_style( 'dw-client-css', plugin_dir_url( __FILE__ ) . '/css/style.css' );
      wp_enqueue_script( 'dw-ajax-script', plugin_dir_url( __FILE__ )  . '/js/ajax.js', array('jquery'));
      wp_localize_script( 'dw-ajax-script', 'ajax_url', admin_url( 'admin-ajax.php' ));
    }
  }

  function dw_scripts_admin() {
    // add css for admin
    wp_enqueue_style( 'dailyWall-admin', plugin_dir_url( __FILE__ ) . '/css/admin.css' );
  }

  public function dw_template_Plugin_Page(){
    //create menu item under settings
    add_options_page(
        'Daily Wall Options',
        'Daily Wall Settings',
        'manage_options',
        'daily-wall-options',
        array( $this, 'create_dw_template' )
    );
  }

  public function create_dw_template(){
      // creates the markup for the options page
      if( (isset($_POST) && !empty($_POST)) && isset($_POST['dw_deleteIN'])){
        $data = array();
        $data['dw_deleteIN'] = $_POST['dw_deleteIN'];
        $data['dw_page'] = $_POST['dw_page'];
        $data = serialize($data);
        update_option( 'dw_settingsPage', $data );
      }

      $options = unserialize(get_option( 'dw_settingsPage' ));
      $pages = get_pages();

      ?>
        <div class="wrap dw-markUp">
          <h1>Daily Wall Settings</h1>
          <form method="post">
            <table class="form-table" role="presentation">
              <tbody>
                <tr>
                  <td><label for="dw_deleteIN">Number of Days Before Table data will be dropped</label></td>
                  <td><input id="dw_deleteIN" type="number" name="dw_deleteIN" value="<?php echo $options["dw_deleteIN"] ?>"></td>
                </tr>
                <tr>
                  <td><label for="dw_page">Page To Display Daily Wall On</label></td>
                  <td>
                    <select name="dw_page" id="dw_page">
                    <?php
                    foreach ( $pages as $page ) {
                      $selected = $options["dw_page"] == $page->ID ? ' Selected' : '';
                      $option = '<option value="' . $page->ID . '" '.$selected.'>';
                        $option .= $page->post_title;
                      $option .= '</option>';
                      echo $option;
                    }?>
                    </select>
                  </td>
                </tr>
                <tr>
                  <td><input type="submit" name="publish" id="publish" class="button button-primary button-large" value="Save"></td>
                </tr>
              </tbody>
            </table>
          </form>
        <?php echo $this->optionsTable()?>
        </div>
      <?php
  }

  public function js_callback(){
    // ajax handler for adding text to the database and building
    // the markup
  	if( isset($_POST['text']) ){
      $text = $_POST['text'];
      $this->dw->addToTable($text);
      $text = buildCloud($this->dw->getData());
  	}

  	die($text);
  }

  public function optionsTable(){
    //show the current words stored in the Database
    // on the options page
    $data = $this->dw->getData('wCount', 'DESC');
      $output .= '<h2>Currently in the Database</h2>';
      $output .= '<div class="tableHeight">';
      $output .= '<table class="dailyWallAdmin">';
      $output .= '<thead>';
      $output .='<tr>';
      $output .='<th>Word</th>';
      $output .='<th>Count</th>';
      $output .='<th>Date Entered</th>';
      $output .='</tr>';
      $output .='</thead>';
      $output .='<tbody>';
      foreach($data as $single){
        $output .='<tr>';
        $output .='<td>'.$single->word.'</td>';
        $output .='<td>'.$single->wCount.'</td>';
        $output .='<td>'.$single->wDate.'</td>';
        $output .='</tr>';
      }
      $output .='</tbody>';
      $output .='</table>';
      $output .='</div>';
      return $output;
  }

  public function dw_template() {
    //redirects the page to use single.php in plug-in if optional vaule is
    // equal to page id of current veiwing page
    $options = unserialize(get_option( 'dw_settingsPage' ));
    if ( is_page( $options['dw_page'] ) ) {
      $page_template = dirname( __FILE__ ) . '/single.php';
    }
    return $page_template;
  }

  public function dw_template_delete(){
    //emptys tables based on option vaule on settings page
    $this->dw->deleteData();
  }

  public function plugin_activated(){
    // add tables on plug activated
    $this->dw->createTable();
  }

  public function plugin_deactivated(){
    // drops tables on plug activated
    $this->dw->dropTable();
  }

  function my_plugin_action_links( $links ) {
  	$links = array_merge( array(
  		'<a href="' . esc_url( admin_url( '/options-general.php?page=daily-wall-options' ) ) . '">' . 'Settings' . '</a>'
  	), $links );

  	return $links;

  }

}
new dailyWall();

function buildCloud($data){
  //basic markup for the front end of the site_url
  $output .= '<ul class="cloud" role="navigation" aria-label="Webdev tag cloud">';
  foreach($data as $single){
    if($single->word == 'john was here' ){
      $single->wCount = 4;
    }
    $output .= '<li data-weight="'.$single->wCount.'" style="font-size:'.$single->wCount.'rem;">'.$single->word.'</li>';
  }
  $output .= '</ul>';

  return $output;
}

?>
