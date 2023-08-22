<?php
/**
 * Plugin Name: Telegram Bot Manager
 * Description: Un plugin para gestionar la interacción con un bot de Telegram.
 * Version: 1.0
 * Author: Tu Nombre
 * License: GPL2
 */

// Evita el acceso directo al archivo.
if (!defined('ABSPATH')) {
    exit;
}

// Incluye los archivos necesarios utilizando rutas absolutas.
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-telegram-bot.php';
require_once __DIR__ . '/includes/handlers.php';
require_once __DIR__ . '/includes/activate.php';
require_once __DIR__ . '/includes/deactivate.php';
require_once __DIR__ . '/includes/webhook-handler.php';

// Aquí puedes añadir más funciones o ganchos si es necesario.
?>