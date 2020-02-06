<?php

namespace Emergence\Mailgun;

use Emergence\Mailer\AbstractMailer;

class Mailer extends AbstractMailer
{
    public static $apiKey;
    public static $domain;

    public static function send($to, $subject, $body, $from = false, $options = [])
    {
        if (!$from) {
            $from = static::getDefaultFrom();
        }

        // Full options can be found here: https://documentation.mailgun.com/en/latest/api-sending.html#sending
        return API::request('/messages', [
            'post' => [
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                'html' => $body
            ]
        ]);
    }
}
