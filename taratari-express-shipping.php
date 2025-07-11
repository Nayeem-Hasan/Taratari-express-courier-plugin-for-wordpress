<?php
/**
 * Plugin Name: Taratari Express Shipping
 * Description: Integrates Taratari Express shipping service with WooCommerce
 * Version: 1.0.0
 * Author: Mehedi Hasan
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize settings
require_once plugin_dir_path(__FILE__) . 'includes/class-taratari-settings.php';
add_action('plugins_loaded', array('Taratari_Settings', 'get_instance'));

// Add Create Parcel button to orders list
add_filter('manage_edit-shop_order_columns', function($columns) {
    $columns['taratari_parcel'] = __('Taratari Express', 'taratari-express');
    return $columns;
});

add_action('manage_shop_order_posts_custom_column', function($column, $post_id) {
    if ($column === 'taratari_parcel') {
        $tracking_code = get_post_meta($post_id, '_taratari_tracking_code', true);
        if (!$tracking_code) {
            echo '<button class="button create-taratari-parcel" data-order-id="' . esc_attr($post_id) . '">Create Parcel</button>';
        } else {
            echo esc_html($tracking_code);
        }
    }
}, 10, 2);

// AJAX handler for creating parcel
add_action('wp_ajax_create_taratari_parcel', function() {
    check_ajax_referer('create_taratari_parcel', 'nonce');
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_send_json_error('Invalid order');
        return;
    }

    $api_key = get_option('taratari_api_key');
    $secret_key = get_option('taratari_secret_key');
    
    // Get the billing state and convert it to a valid area name
    $state = $order->get_billing_state();
    $area = convert_state_to_area($state);
    
    if (empty($area)) {
        wp_send_json_error('Invalid delivery area. Please check the billing state.');
        return;
    }
    
    $data = array(
        'invoice' => $order->get_order_number(),
        'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'customer_phone' => $order->get_billing_phone(),
        'customer_address' => $order->get_billing_address_1(),
        'area' => $area,
        'cash_collection' => $order->get_total()
    );

    $response = wp_remote_post('https://taratariexpress.com.bd/api/v1/create-parcel', array(
        'headers' => array(
            'Api-Key' => $api_key,
            'Secret-Key' => $secret_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($data)
    ));

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($body['status']) {
        update_post_meta($order_id, '_taratari_tracking_code', $body['parcel']['tracking_code']);
        wp_send_json_success($body['parcel']);
    } else {
        wp_send_json_error($body['message']);
    }
});

/**
 * Convert WooCommerce state to Taratari Express area name
 */
function convert_state_to_area($state) {
    // Map of WooCommerce state codes to Taratari Express area names
    $area_mapping = array(
        'BD-05' => 'Bagerhat',
        'BD-01' => 'Bandarban',
        'BD-02' => 'Barguna',
        'BD-06' => 'Barishal',
        'BD-07' => 'Bhola',
        'BD-03' => 'Bogura',
        'BD-04' => 'Brahmanbaria',
        'BD-09' => 'Chandpur',
        'BD-10' => 'Chattogram',
        'BD-12' => 'Chuadanga',
        'BD-11' => "Cox's Bazar",
        'BD-08' => 'Cumilla',
        'BD-13' => 'Dhaka',
        'BD-14' => 'Dinajpur',
        'BD-15' => 'Faridpur',
        'BD-16' => 'Feni',
        'BD-19' => 'Gaibandha',
        'BD-18' => 'Gazipur',
        'BD-17' => 'Gopalganj',
        'BD-20' => 'Habiganj',
        'BD-21' => 'Jamalpur',
        'BD-22' => 'Jashore',
        'BD-25' => 'Jhalokati',
        'BD-23' => 'Jhenaidah',
        'BD-24' => 'Joypurhat',
        'BD-29' => 'Khagrachhari',
        'BD-27' => 'Khulna',
        'BD-26' => 'Kishoreganj',
        'BD-28' => 'Kurigram',
        'BD-30' => 'Kushtia',
        'BD-31' => 'Lakshmipur',
        'BD-32' => 'Lalmonirhat',
        'BD-36' => 'Madaripur',
        'BD-37' => 'Magura',
        'BD-33' => 'Manikganj',
        'BD-39' => 'Meherpur',
        'BD-38' => 'Moulvibazar',
        'BD-35' => 'Munshiganj',
        'BD-34' => 'Mymensingh',
        'BD-48' => 'Naogaon',
        'BD-43' => 'Narail',
        'BD-40' => 'Narayanganj',
        'BD-42' => 'Narsingdi',
        'BD-44' => 'Natore',
        'BD-45' => 'Nawabganj',
        'BD-41' => 'Netrakona',
        'BD-46' => 'Nilphamari',
        'BD-47' => 'Noakhali',
        'BD-49' => 'Pabna',
        'BD-52' => 'Panchagarh',
        'BD-51' => 'Patuakhali',
        'BD-50' => 'Pirojpur',
        'BD-53' => 'Rajbari',
        'BD-54' => 'Rajshahi',
        'BD-56' => 'Rangamati',
        'BD-55' => 'Rangpur',
        'BD-58' => 'Satkhira',
        'BD-62' => 'Shariatpur',
        'BD-57' => 'Sherpur',
        'BD-59' => 'Sirajganj',
        'BD-61' => 'Sunamganj',
        'BD-60' => 'Sylhet',
        'BD-63' => 'Tangail',
        'BD-64' => 'Thakurgaon'
    );
    
    return isset($area_mapping[$state]) ? $area_mapping[$state] : '';
}

// Add admin scripts
add_action('admin_enqueue_scripts', function($hook) {
    if ('edit.php' !== $hook || !isset($_GET['post_type']) || 'shop_order' !== $_GET['post_type']) {
        return;
    }

    wp_enqueue_script(
        'taratari-admin',
        plugins_url('assets/js/admin.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );

    wp_localize_script('taratari-admin', 'taratariAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('create_taratari_parcel')
    ));
});