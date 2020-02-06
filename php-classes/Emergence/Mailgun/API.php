<?php

namespace Emergence\Mailgun;

use RuntimeException;

class API
{
    public static $apiKey;
    public static $domain;

    public static $baseUrl = 'https://api.mailgun.net/v3/';

    public static function request($path, array $options = [])
    {
        $url = static::$baseUrl . static::$domain . $path;

        if (!empty($options['get'])) {
            $url .= '?' . http_build_query(array_map(function($value) {
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }
                return $value;
            }, $options['get']));
        }

        // configure curl
        $ch = curl_init($url);

        // configure encoding
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');

        // configure auth
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . static::$apiKey);

        // configure output
        if (!empty($options['outputPath'])) {
            $fp = fopen($options['outputPath'], 'w');
            curl_setopt($ch, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }

        // configure method and body
        if (!empty($options['post'])) {
            if (empty($options['method']) || $options['method'] == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options['method']);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['post']);
        }

        // execute request
        $responseString = curl_exec($ch);

        if (isset($fp)) {
            fclose($fp);
        } else {
            $responseData = json_decode($responseString, true);
        }

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus !== 200) {
            $message = !empty($responseData) && !empty($responseData['message'])
                ? $responseData['message']
                : $responseString;

            \Emergence\Logger::general_error('MaligunMailer Delivery Error', [
                'exceptionClass' => API::class,
                'exceptionMessage' => $message,
                'exceptionCode' => $httpStatus
            ]);

            throw new RuntimeException("Mailgun API call failed: {$message}", $httpStatus);
        }

        return isset($responseData) ? $responseData : $responseString;
    }
}
