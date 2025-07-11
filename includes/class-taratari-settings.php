<?php
if (!defined('ABSPATH')) {
    exit;
}

class Taratari_Settings {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
    }

    public function init_settings() {
        register_setting(
            'taratari_options_group',
            'taratari_api_key'
        );

        register_setting(
            'taratari_options_group',
            'taratari_secret_key'
        );

        add_settings_section(
            'taratari_settings_section',
            __('Taratari Express Settings', 'taratari-express'),
            array($this, 'settings_section_callback'),
            'taratari_settings'
        );

        add_settings_field(
            'taratari_api_key',
            __('API Key', 'taratari-express'),
            array($this, 'api_key_field_callback'),
            'taratari_settings',
            'taratari_settings_section'
        );

        add_settings_field(
            'taratari_secret_key',
            __('Secret Key', 'taratari-express'),
            array($this, 'secret_key_field_callback'),
            'taratari_settings',
            'taratari_settings_section'
        );
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Taratari Express', 'taratari-express'),
            __('Taratari Express', 'taratari-express'),
            'manage_woocommerce',
            'taratari_settings',
            array($this, 'settings_page')
        );
    }

    public function settings_section_callback() {
        echo '<p>' . __('Configure your Taratari Express API credentials.', 'taratari-express') . '</p>';
    }

    public function api_key_field_callback() {
        $value = get_option('taratari_api_key');
        echo '<input type="text" id="taratari_api_key" name="taratari_api_key" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your Taratari Express API Key', 'taratari-express') . '</p>';
    }

    public function secret_key_field_callback() {
        $value = get_option('taratari_secret_key');
        echo '<input type="password" id="taratari_secret_key" name="taratari_secret_key" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your Taratari Express Secret Key', 'taratari-express') . '</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Taratari Express Settings', 'taratari-express'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('taratari_options_group');
                do_settings_sections('taratari_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}