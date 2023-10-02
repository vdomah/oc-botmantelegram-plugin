<?php namespace Vdomah\BotmanTelegram\Classes;

use Vdomah\Botman\Classes\BotMan\IncomingMessage;
use Vdomah\BotmanTelegram\Classes\Attachments\Poll;

class TelegramPollUpdateDriver extends TelegramDriver
{
    const DRIVER_NAME = 'TelegramPollUpdate';

    /**
     * @var array
     */
    protected static $listeners = [];

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return is_array($this->payload->get('poll')) && ! is_null($this->payload->get('update_id'));
    }

    /**
     * Load Telegram messages.
     */
    public function loadMessages()
    {
        foreach (self::$listeners as $listener) {
            $results = $listener($this->payload->get('poll'));

            if ($results) {
                break;
            }
        }

        echo 1;exit;
    }

    /*
     * Adding listeners to prepare results for inline request.
     */
    public static function listen($closure)
    {
        array_unshift(self::$listeners, $closure);

        self::$listeners = array_unique(self::$listeners);
    }
}