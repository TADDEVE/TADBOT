<?php
// Evita el acceso directo al archivo.
if (!defined('ABSPATH')) {
    exit;
}

class Telegram_Settings {
   public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp_ajax_configure_telegram_webhook', array($this, 'configure_telegram_webhook_callback'));
    }

   public function add_plugin_page() {
        add_menu_page(
            'Configuraciones del Bot de Telegram',
            'Bot de Telegram',
            'manage_options',
            'telegram_bot_settings_page',
            array($this, 'create_admin_page'),
            'dashicons-admin-comments',
            100
        );
    }

     public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Configuraciones del Bot de Telegram</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('telegram_bot_settings_group');
                do_settings_sections('telegram_bot_settings_page');
                submit_button('Guardar configuración');
                ?>
            </form>
            <button id="configure-webhook">Configurar Webhook de Telegram</button>
            <script type="text/javascript">
                document.getElementById('configure-webhook').addEventListener('click', function() {
                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'configure_telegram_webhook',
                        },
                        success: function(response) {
                            alert(response);
                        }
                    });
                });
            </script>
        </div>
        <?php
    }

    public function page_init() {
        register_setting('telegram_bot_settings_group', 'telegram_api_key');
        register_setting('telegram_bot_settings_group', 'subscription_product_id');
        register_setting('telegram_bot_settings_group', 'telegram_invite_link');
        register_setting('telegram_bot_settings_group', 'telegram_group_id');

        add_settings_section(
            'telegram_bot_main_settings',
            'Configuraciones Principales',
            null,
            'telegram_bot_settings_page'
        );

        add_settings_field(
            'telegram_api_key',
            'Telegram API Key',
            array($this, 'render_telegram_api_key_field'),
            'telegram_bot_settings_page',
            'telegram_bot_main_settings'
        );

        add_settings_field(
            'subscription_product_id',
            'ID del Producto de Suscripción',
            array($this, 'render_subscription_product_id_field'),
            'telegram_bot_settings_page',
            'telegram_bot_main_settings'
        );

        add_settings_field(
            'telegram_invite_link',
            'Enlace de Invitación de Telegram',
            array($this, 'render_telegram_invite_link_field'),
            'telegram_bot_settings_page',
            'telegram_bot_main_settings'
        );

        add_settings_field(
            'telegram_group_id',
            'ID del Grupo de Telegram',
            array($this, 'render_telegram_group_id_field'),
            'telegram_bot_settings_page',
            'telegram_bot_main_settings'
        );
    }

    public function render_telegram_api_key_field() {
        $value = esc_attr(get_option('telegram_api_key'));
        echo "<input type='text' name='telegram_api_key' value='$value' />";
    }

    public function render_subscription_product_id_field() {
        $value = esc_attr(get_option('subscription_product_id'));
        echo "<input type='text' name='subscription_product_id' value='$value' />";
    }

    public function render_telegram_invite_link_field() {
        $value = esc_attr(get_option('telegram_invite_link'));
        echo "<input type='text' name='telegram_invite_link' value='$value' />";
    }

    public function render_telegram_group_id_field() {
        $value = esc_attr(get_option('telegram_group_id'));
        echo "<input type='text' name='telegram_group_id' value='$value' />";
    }

    public function configure_telegram_webhook_callback() {
        $api_key = get_option('telegram_api_key');
        $webhook_url = site_url() . '/wp-json/telegram-bot/v1/webhook';

        $response = wp_remote_post("https://api.telegram.org/bot{$api_key}/setWebhook", [
            'body' => [
                'url' => $webhook_url,
            ],
        ]);

        if (is_wp_error($response)) {
            echo 'Error al configurar el webhook: ' . $response->get_error_message();
        } else {
            echo '¡Webhook configurado correctamente!';
        }
        wp_die();
    }
}

new Telegram_Settings();
?>