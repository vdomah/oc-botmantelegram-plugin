<?php namespace Vdomah\BotmanTelegram\Classes\DriverTelegram;

use Illuminate\Support\Collection;

class KeyboardButton extends \BotMan\Drivers\Telegram\Extensions\KeyboardButton
{
    public $switch_inline_query_current_chat;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $callbackData;

    /**
     * @var bool
     */
    protected $requestContact = false;

    /**
     * @var bool
     */
    protected $requestLocation = false;

    /**
     * @param $text
     * @return KeyboardButton
     */
    public static function create($text)
    {
        return new self($text);
    }

    /**
     * KeyboardButton constructor.
     * @param $text
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * @param $url
     * @return $this
     */
    public function url($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param $callbackData
     * @return $this
     */
    public function callbackData($callbackData)
    {
        $this->callbackData = $callbackData;

        return $this;
    }

    /**
     * @param bool $active
     * @return $this
     */
    public function requestContact($active = true)
    {
        $this->requestContact = $active;

        return $this;
    }

    /**
     * @param bool $active
     * @return $this
     */
    public function requestLocation($active = true)
    {
        $this->requestLocation = $active;

        return $this;
    }

    public function inlineQueryCurrent($val)
    {
        $this->switch_inline_query_current_chat = $val;

        return $this;
    }

    public function jsonSerialize()
    {
        return Collection::make([
            'url' => $this->url,
            'switch_inline_query_current_chat' => $this->switch_inline_query_current_chat,
            'callback_data' => $this->callbackData,
            'request_contact' => $this->requestContact,
            'request_location' => $this->requestLocation,
            'text' => $this->text,
        ])->filter()->toArray();
    }
}