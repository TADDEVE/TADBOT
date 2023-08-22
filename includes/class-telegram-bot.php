<?php

class TelegramBot {
    private $api_key;
    private $api_url;

    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->api_url = "https://api.telegram.org/bot{$this->api_key}/";
    }

    public function sendMessage($chat_id, $text) {
        $data = [
            'chat_id' => $chat_id,
            'text' => $text
        ];
        return $this->callAPI('sendMessage', $data);
    }

    private function callAPI($method, $data) {
        $url = $this->api_url . $method;
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        // Manejo básico de errores
        if ($result === false) {
            error_log("Error al llamar a la API de Telegram");
            return false;
        }

        return json_decode($result);
    }
}
?>