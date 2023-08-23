<?php
/**
 * Webhook handler para Telegram Bot.
 */

function handle_telegram_webhook(WP_REST_Request $request) {
    error_log("[DATE/TIME UTC] Webhook function started");

    $data = $request->get_params();

    if (!isset($data['message'])) {
        return new WP_REST_Response('No message detected.');
    }

    error_log("[DATE/TIME UTC] New message detected");

    $chat_id = $data['message']['chat']['id'];
    $user_id = $data['message']['from']['id'];
    $username = isset($data['message']['from']['username']) ? $data['message']['from']['username'] : "No username";
    $text = isset($data['message']['text']) ? $data['message']['text'] : "";
    

    error_log("Chat ID: {$chat_id}, User ID: {$user_id}, Username: {$username}, Text: {$text}");

    if ($text === "/start") {
        sendMessage($chat_id, "Por favor, proporciona la dirección de correo con la que realizaste la compra de la suscripción.");
    } else {
        if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
    if (is_email_registered_and_has_active_subscription($text)) {
        // Guarda la información del usuario antes de enviarle el enlace de invitación
        $is_stored = store_user_info($username, $user_id, $chat_id, $text);
        if ($is_stored) {
            addMemberToTelegramGroup($chat_id);
        }
    } else {
        sendMessage($chat_id, "Lo siento, tu dirección de correo electrónico no está registrada en nuestra plataforma o no tienes una suscripción activa. Por favor, regístrate o verifica tu suscripción.");
    }
} else {
    sendMessage($chat_id, "Por favor, proporciona la dirección de correo con la que realizaste la compra de la suscripción.");
}


  if (isset($data['message']['new_chat_members'])) {
    foreach ($data['message']['new_chat_members'] as $new_member) {
        $new_member_id = $new_member['id'];
        $new_member_username = isset($new_member['username']) ? $new_member['username'] : "No username";
        
        error_log("Verificando el nuevo miembro con ID: {$new_member_id}");
        
        if (!is_member_valid($new_member_id)) {
            error_log("El miembro {$new_member_id} no es válido. Expulsando del grupo...");
            kickMember($new_member_id, $chat_id);
        } else {
            error_log("El miembro {$new_member_id} es válido.");
            store_user_info($new_member_username, $new_member_id, $chat_id, $text);
        }
    }

}
}

    return new WP_REST_Response('Processed.');
}

function store_user_info($username, $user_id, $chat_id, $email) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'telegram_users';

    // Verificar si el correo electrónico ya ha sido utilizado por otro usuario
    $existing_email = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE email = %s AND user_id != %d", $email, $user_id));

    if ($existing_email) {
        error_log("Error: El correo electrónico {$email} ya ha sido utilizado por otro usuario de Telegram.");
        sendMessage($chat_id, "Este correo electrónico ya ha sido utilizado por otro usuario. Si crees que esto es un error, por favor contacta con el soporte.");
         kickMember($user_id);
        return false; // Retorna false para indicar que el correo electrónico ya ha sido utilizado
    }

    // Verificar si el usuario ya está registrado
    $existing_user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    if ($existing_user) {
        // Actualizar datos
        $wpdb->update($table_name, ['email' => $email, 'is_in_group' => 1], ['user_id' => $user_id]);
    } else {
        // Insertar datos
        $wpdb->insert($table_name, [
            'username' => $username,
            'user_id' => $user_id,
            'chat_id' => $chat_id,
            'email' => $email,
            'is_in_group' => 1
        ]);
    }

    // Registro de errores de la base de datos
    if ($wpdb->last_error) {
        error_log("Error al guardar datos en la tabla: " . $wpdb->last_error);
        return false; // Retorna false si hay un error al guardar los datos
    } else {
        error_log("Información de usuario guardada exitosamente: User ID {$user_id}, Email {$email}");
        return true; // Retorna true si todo salió bien
    }
}


function is_member_valid($telegram_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'telegram_users';
    
    // Buscar usuario en la tabla telegram_users por ID de Telegram
    $user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $telegram_id));

    if (!$user_data || $user_data->is_in_group == 0) {
        error_log("El ID {$telegram_id} no corresponde a ningún usuario de Telegram en nuestra base de datos o el usuario no está en el grupo.");
        return false;
    }

    $email = $user_data->email;

    if (is_email_registered_and_has_active_subscription($email)) {
        error_log("El usuario con email {$email} tiene una suscripción activa.");
        return true;
    }

    error_log("El usuario con email {$email} NO tiene una suscripción activa.");
    return false;
}



function kickMember($user_id) {
    $api_key = get_option('telegram_api_key');
    $group_chat_id = get_option('telegram_group_id'); 

    error_log("API Key: " . $api_key);
    error_log("Group Chat ID: " . $group_chat_id);
    error_log("User ID a expulsar: " . $user_id);

    $ban_url = "https://api.telegram.org/bot{$api_key}/banChatMember";
    $unban_url = "https://api.telegram.org/bot{$api_key}/unbanChatMember";

    $data = [
        'chat_id' => $group_chat_id,
        'user_id' => $user_id,
        'revoke_messages' => true
    ];

    // Ban user
    error_log("Datos enviados a la API de Telegram para banear: " . json_encode($data));
    $response = wp_remote_post($ban_url, [
        'body' => $data,
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
    ]);

    if (is_wp_error($response)) {
        error_log('Error al expulsar el miembro del grupo: ' . $response->get_error_message());
        return;
    } else {
        $body_response = wp_remote_retrieve_body($response);
        error_log("Respuesta de la API de Telegram al banear: " . $body_response);
    }

    // Unban user
    error_log("Datos enviados a la API de Telegram para desbanear: " . json_encode($data));
    $response = wp_remote_post($unban_url, [
        'body' => $data,
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
    ]);

    if (is_wp_error($response)) {
        error_log('Error al desbanear el miembro del grupo: ' . $response->get_error_message());
        return;
    } else {
        $body_response = wp_remote_retrieve_body($response);
        error_log("Respuesta de la API de Telegram al desbanear: " . $body_response);
    }

    // Check user status after operations
    $status_url = "https://api.telegram.org/bot{$api_key}/getChatMember";
    error_log("Datos enviados a la API de Telegram para consultar estado: " . json_encode($data));
    $response = wp_remote_post($status_url, [
        'body' => $data,
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
    ]);

    if (is_wp_error($response)) {
        error_log('Error al consultar el estado del miembro del grupo: ' . $response->get_error_message());
    } else {
        $body_response = json_decode(wp_remote_retrieve_body($response), true);
        if ($body_response['ok']) {
            $user_status = $body_response['result']['status'];
            error_log("Estado actual del usuario: " . $user_status);
        } else {
            error_log("Error al obtener el estado del usuario: " . $body_response['description']);
        }
    }
}


function sendMessage($chat_id, $message) {
    $api_key = get_option('telegram_api_key');
    $url = "https://api.telegram.org/bot{$api_key}/sendMessage";

    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
    ];

    $response = wp_remote_post($url, [
        'body' => $data,
    ]);

    if (is_wp_error($response)) {
        error_log('Error al enviar el mensaje: ' . $response->get_error_message());
    }
}
function is_email_registered_and_has_active_subscription($email) {
    $user = get_user_by('email', $email);
    
    if (!$user) {
        error_log("Error: No se encontró un usuario registrado con el correo electrónico {$email}.");
        return false;
    }

    $subscription_product_id = get_option('subscription_product_id');
    if (empty($subscription_product_id)) {
        error_log('Error: ID de producto de suscripción no encontrado en la configuración.');
        return false;
    }

    $subscriptions = wcs_get_users_subscriptions($user->ID);
    foreach ($subscriptions as $subscription) {
        if ($subscription->has_product($subscription_product_id) && $subscription->has_status('active')) {
            error_log("Éxito: El usuario con el correo electrónico {$email} tiene una suscripción activa.");
            return true;
        }
    }

    error_log("Error: El usuario con el correo electrónico {$email} está registrado pero no tiene una suscripción activa.");
    return false;
}

function addMemberToTelegramGroup($chat_id) {
    $invite_link = get_option('telegram_invite_link');
    
    if ($invite_link) {
        // Enviar enlace de invitación al usuario
        sendMessage($chat_id, 'Haga clic en el siguiente enlace para unirse a nuestro grupo: ' . $invite_link);
    } else {
        sendMessage($chat_id, 'Hubo un error al intentar enviarte el enlace de invitación. Por favor, contacta con el soporte.');
        error_log('Error: Enlace de invitación de Telegram no encontrado en la configuración.');
    }
}



function handle_subscription_status_change($subscription_id, $old_status, $new_status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'telegram_users';

    // Obtener la suscripción
    $subscription = wcs_get_subscription($subscription_id);

    // Obtener el ID del producto que te interesa desde las opciones de WP
    $subscription_product_id = get_option('subscription_product_id');

    // Si la suscripción no contiene el producto que te interesa, salimos
    if (!$subscription->has_product($subscription_product_id)) {
        error_log("La suscripción con ID {$subscription_id} no contiene el producto de interés.");
        return;
    }

    $user_email = $subscription->get_billing_email();
    $user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE email = %s", $user_email));

    if (!$user_data) {
        error_log("No se encontró al usuario de Telegram asociado al email {$user_email}.");
        return;
    }

    switch ($new_status) {
        case 'active':
            error_log("La suscripción con ID {$subscription_id} ha cambiado a estado activo.");
            // No haces nada en este caso, ya que el usuario ya está en el grupo.
             // Actualizar el campo is_in_group a 1
            $wpdb->update($table_name, ['is_in_group' => 1], ['email' => $user_email]);
            break;
        case 'on-hold':
        case 'expired':
            error_log("La suscripción con ID {$subscription_id} ha cambiado a estado {$new_status}. Procediendo a remover al usuario del grupo.");
             // Actualizar el campo is_in_group a 0
            $wpdb->update($table_name, ['is_in_group' => 0], ['email' => $user_email]);
            kickMember($user_data->user_id);
            sendMessage($user_data->chat_id, "Tu suscripción ha sido cancelada. Has sido eliminado del grupo.");
            break;
        case 'cancelled':
            error_log("La suscripción con ID {$subscription_id} ha cambiado a estado {$new_status}. Procediendo a remover al usuario del grupo.");
             // Actualizar el campo is_in_group a 0
            $wpdb->update($table_name, ['is_in_group' => 0], ['email' => $user_email]);
            kickMember($user_data->user_id);
            sendMessage($user_data->chat_id, "Tu suscripción ha sido cancelada. Has sido eliminado del grupo.");
            break;
        case 'pending-cancel':
            error_log("La suscripción con ID {$subscription_id} ha cambiado a estado {$new_status}. Procediendo a remover al usuario del grupo.");
             // Actualizar el campo is_in_group a 0
            $wpdb->update($table_name, ['is_in_group' => 0], ['email' => $user_email]);
            kickMember($user_data->user_id);
            sendMessage($user_data->chat_id, "Tu suscripción ha caducado, está en espera, ha sido cancelada o está pendiente de cancelación. Has sido removido del grupo.");
            break;
        default:
            error_log("La suscripción con ID {$subscription_id} ha cambiado a un estado no manejado: {$new_status}.");
            break;
    }
}

add_action('woocommerce_subscription_status_changed', 'handle_subscription_status_change', 10, 3);



add_action('rest_api_init', function () {
    register_rest_route('telegram-bot/v1', 'webhook', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'handle_telegram_webhook',
    ]);
});