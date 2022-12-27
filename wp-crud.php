<?php
/**
* Plugin Name: WP Crud
* Description: This plugin to create custom crud for admin panel
* Version:     2.1.3
* Plugin URI: https://wpcurd.com/
* Author:      Motaleb Hossain
* Author URI:  http://motaleb.info
* License:     GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: wpcrud
* Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( '¡Sin trampas!' );

// metabox 
require plugin_dir_path( __FILE__ ) . 'includes/metabox.php';

// text domain 
function wpcrud_plugin_load_textdomain() {
load_plugin_textdomain( 'wpcrud', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'wpcrud_plugin_load_textdomain' );


global $wpbc_db_version;
$wpbc_db_version = '1.1.0'; 


function wpbc_install()
{
    global $wpdb;
    global $wpbc_db_version;

    $table_name = $wpdb->prefix . 'wpcd'; 


    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      name VARCHAR (50) NOT NULL,
      lastname VARCHAR (100) NOT NULL,
      email VARCHAR(100) NOT NULL,
      phone VARCHAR(15) NULL,

    );";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('wpcrud_db_version', $wpcrud_db_version);

    $installed_ver = get_option('wpcrud_db_version');
    if ($installed_ver != $wpcrud_db_version) {
        $sql = "CREATE TABLE " . $table_name . " (
          id int(11) NOT NULL AUTO_INCREMENT,
          name VARCHAR (50) NOT NULL,
          lastname VARCHAR (100) NOT NULL,
          email VARCHAR(100) NOT NULL,
          phone VARCHAR(15) NULL,
          PRIMARY KEY  (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('wpcrud_db_version', $wpcrud_db_version);
    }
}

register_activation_hook(__FILE__, 'wpbc_install');


function wpbc_install_data()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'wpcd'; 

}

register_activation_hook(__FILE__, 'wpbc_install_data');


function wpbc_update_db_check()
{
    global $wpbc_db_version;
    if (get_site_option('wpbc_db_version') != $wpbc_db_version) {
        wpbc_install();
    }
}

add_action('plugins_loaded', 'wpbc_update_db_check');



if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


class Custom_Table_Example_List_Table extends WP_List_Table
 { 
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'contact',
            'plural'   => 'contacts',
        ));
    }


    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }


    function column_phone($item)
    {
        return '<em>' . $item['phone'] . '</em>';
    }


    function column_name($item)
    {

        $actions = array(
            'edit' => sprintf('<a href="?page=contacts_form&id=%s">%s</a>', $item['id'], __('Edit', 'wpbc')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'wpbc')),
        );

        return sprintf('%s %s',
            $item['name'],
            $this->row_actions($actions)
        );
    }


    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', 
            'name'      => __('Name', 'wpcrud'),
            'lastname'  => __('Last Name', 'wpcrud'),
            'email'     => __('E-Mail', 'wpcrud'),
            'phone'     => __('Phone', 'wpcrud'),
            
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name'      => array('name', true),
            'lastname'  => array('lastname', true),
            'email'     => array('email', true),
            'phone'     => array('phone', true),
            
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpcd'; 

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpcd'; 

        $per_page = 10; 

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
       
        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");


        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'lastname';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';


        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);


        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
}

function wpbc_admin_menu()
{
    add_menu_page(__('Cruds', 'wpcrud'), __('Cruds', 'wpcrud'), 'activate_plugins', 'curds', 'wpbc_contacts_page_handler');
    add_submenu_page('curds', __('Cruds', 'wpcrud'), __('Cruds', 'wpcrud'), 'activate_plugins', 'curds', 'wpbc_contacts_page_handler');
   
    add_submenu_page('curds', __('Add new', 'wpcrud'), __('Add new', 'wpcrud'), 'activate_plugins', 'contacts_form', 'wpbc_contacts_form_page_handler');
}

add_action('admin_menu', 'wpbc_admin_menu');


function wpbc_validate_contact($item)
{
    $messages = array();

    if (empty($item['name'])) $messages[] = __('Name is required', 'wpcrud');
    if (empty($item['lastname'])) $messages[] = __('Last Name is required', 'wpcrud');
    if (!empty($item['email']) && !is_email($item['email'])) $messages[] = __('E-Mail is in wrong format', 'wpcrud');
    if(!empty($item['phone']) && !absint(intval($item['phone'])))  $messages[] = __('Phone ');
    if(!empty($item['phone']) && !preg_match('/[0-9]+/', $item['phone'])) $messages[] = __('Phone');
    

    if (empty($messages)) return true;
    return implode('<br />', $messages);
}


function wpcrud_languages()
{
    load_plugin_textdomain('wpcrud', false, dirname(plugin_basename(__FILE__)));
}

add_action('init', 'wpcrud_languages');