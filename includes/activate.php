<?php

function telegram_bot_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'telegram_users';

    // SQL para crear la tabla
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username tinytext NOT NULL,
        user_id bigint(20) NOT NULL,
        chat_id bigint(20) NOT NULL,
        email varchar(255) DEFAULT '' NOT NULL,   -- Agregamos el campo email
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Verifica si la API key de Telegram ya está guardada en las opciones. Si no, la inicializa vacía.
    if (get_option('telegram_api_key') === false) {
        add_option('telegram_api_key', '');
    }

    // Verifica si el ID del producto de suscripción está guardado. Si no, lo inicializa vacío.
    if (get_option('subscription_product_id') === false) {
        add_option('subscription_product_id', '');
    }

    // Verifica si el enlace de invitación a Telegram está guardado. Si no, lo inicializa vacío.
    if (get_option('telegram_invite_link') === false) {
        add_option('telegram_invite_link', '');
    }
}

// Cambia __FILE__ por la ubicación de tu archivo principal del plugin.
register_activation_hook('telegram-bot-manager/telegram-bot-manager.php', 'telegram_bot_activate');
?>
