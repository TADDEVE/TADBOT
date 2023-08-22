<?php

function telegram_bot_deactivate() {
    // Aquí podrías añadir cualquier tarea de limpieza necesaria durante la desactivación.
}

// Cambia __FILE__ por la ubicación de tu archivo principal del plugin.
register_deactivation_hook(__FILE__, 'telegram_bot_deactivate');
?>