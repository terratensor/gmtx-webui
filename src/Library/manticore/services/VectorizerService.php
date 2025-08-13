<?php

declare(strict_types=1);

namespace App\Library\manticore\services;

use RuntimeException;
use JsonException;

class VectorizerService
{
    private string $apiUrl;
    private string $apiKey;

    // Конструктор для инициализации параметров
    public function __construct(string $apiUrl, string $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }

    /**
     * Векторизация текста через внешний API.
     *
     * @param string $text Исходный текст
     * @param string $model Модель векторизации glove | e5-small
     * @return array Вектор чисел (float)
     * @throws RuntimeException При ошибках сети или API
     */
    public function vectorize(string $text, string $model = 'glove'): array
    {
        // Формируем данные для запроса
        $payload = json_encode(['text' => $text, 'model' => $model], JSON_THROW_ON_ERROR);
        
        // Инициализация cURL
        $ch = curl_init("{$this->apiUrl}/vectorize");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "X-API-Key: {$this->apiKey}",
                "Content-Type: application/json",
                "Content-Length: " . strlen($payload)
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_FAILONERROR => false, // Самостоятельно обрабатываем коды 4xx/5xx
            CURLOPT_TIMEOUT => 5, // Максимум 5 секунд
            CURLOPT_CONNECTTIMEOUT => 2, // Таймаут соединения
        ]);

        // Выполнение запроса
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Обработка ошибок сети
        if ($response === false) {
            throw new RuntimeException("cURL error: " . $error);
        }

        // Обработка HTTP-ошибок
        if ($httpCode !== 200) {
            throw new RuntimeException("API error. HTTP code: {$httpCode}. Response: " . $response);
        }

        // Декодирование JSON
        try {
            $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("JSON decode error: " . $e->getMessage());
        }

        // Проверка наличия вектора в ответе
        if (!isset($result['vector']) || !is_array($result['vector'])) {
            throw new RuntimeException("Invalid API response: 'vector' field missing");
        }

        return $result['vector'];
    }
}