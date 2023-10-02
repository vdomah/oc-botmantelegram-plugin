<?php namespace Vdomah\BotmanTelegram\Classes;


use Vdomah\Botman\Classes\BotMan\IncomingMessage;
use Vdomah\BotmanTelegram\Classes\Attachments\Poll;

class TelegramPollDriver extends TelegramDriver
{
    const DRIVER_NAME = 'TelegramPoll';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('from')) && ! is_null($this->event->get('poll'));
    }

    /**
     * Load Telegram messages.
     */
    public function loadMessages()
    {
        $message = new IncomingMessage(
            Poll::PATTERN,
            $this->event->get('from')['id'],
            $this->event->get('chat')['id'],
            $this->event, $this->config['id_prefix'] ?? ''
        );
        $message->addExtras('poll', $this->event->get('poll'));

        $this->messages = [$message];
    }
}