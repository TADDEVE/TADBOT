<?php
// Evita el acceso directo al archivo.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Acción al completar la compra en WooCommerce.
 */
add_action('woocommerce_order_status_completed', 'send_telegram_invite_on_purchase');

function send_telegram_invite_on_purchase($order_id) {
    // Obtén la orden
    $order = wc_get_order($order_id);
    
    // Comprueba si la orden contiene el producto de suscripción deseado
    $items = $order->get_items();
    $subscription_product_id = get_option('subscription_product_id');
    
    foreach ($items as $item) {
        if ($item->get_product_id() == $subscription_product_id) {
            // Si el producto de suscripción está en la orden, envía el enlace de invitación
            $customer_email = $order->get_billing_email();
            $telegram_invite_link = get_option('telegram_invite_link');
            
            $subject = 'Tu enlace de invitación a nuestro grupo de Telegram';
            $message = "Gracias por tu compra. Únete a nuestro grupo exclusivo de Telegram utilizando el siguiente enlace: $telegram_invite_link";
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            wp_mail($customer_email, $subject, $message, $headers);
            
            break; // Sal del bucle una vez que hayas encontrado el producto y enviado el correo.
        }
    }
}
?>