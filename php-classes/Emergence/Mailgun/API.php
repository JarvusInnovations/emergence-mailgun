<?php

namespace Emergence\Mailgun;

class API
{
    public static $apiKey = '';
    public static $domain = '';

    public static $baseUrl = 'https://api.mailgun.net/v3/';

    public static function request($path, array $options = [])
    {
        // init get params
        if (empty($options['get'])) {
            $options['get'] = [];
        }
        // init post params
        if (empty($options['post'])) {
            $options['post'] = [];
        }
        // init headers
        if (empty($options['headers'])) {
            $options['headers'] = [];
        }

        $options['headers'][] = 'User-Agent: emergence';

        // init url
        if (preg_match('/^https?:\/\//', $path)) {
            $url = $path;
        } else {
            $url = static::$baseUrl;
            $url .= static::$domain . $path;
        }

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
        $encoding = !empty($options['encoding']) ? $options['encoding'] : 'UTF-8';
        curl_setopt($ch, CURLOPT_ENCODING, $encoding);

        // configure auth
        if (empty($options['skipAuth'])) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, 'api:' . static::$apiKey);
        }

        // configure output
        if (!empty($options['outputPath'])) {
            $fp = fopen($options['outputPath'], 'w');
            curl_setopt($ch, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }

        // configure method and body
        if (!empty($options['post'])) {
            $encodeData = $options['post']['encode'];
            unset($options['post']['encode']);

            $postData = !empty($encodeData) ? json_encode($options['post']) : $options['post'];
            if (empty($options['method']) || $options['method'] == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options['method']);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        // configure headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        // execute request
        $result = curl_exec($ch);

        if (isset($fp)) {
            fclose($fp);
        } elseif (!isset($options['decodeJson']) || $options['decodeJson']) {
            $result = json_decode($result, true);
        }

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus !== 200) {
            \Emergence\Logger::general_error('MaligunMailer Delivery Error', [
                'exceptionClass' => API::class,
                'exceptionMessage' => $result,
                'exceptionCode' => $httpStatus
            ]);
        }

        return $result;
    }
}
