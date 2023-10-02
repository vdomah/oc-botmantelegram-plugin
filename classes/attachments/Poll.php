<?php namespace Vdomah\BotmanTelegram\Classes\Attachments;

use BotMan\BotMan\Messages\Attachments\Attachment;

class Poll extends Attachment
{
    /**
     * Pattern that messages use to identify poll attachment.
     */
    const PATTERN = '%%%_POLL_%%%';

    /** @var string */
    protected $latitude;

    /** @var string */
    protected $longitude;

    /**
     * Message constructor.
     * @param string $latitude
     * @param string $longitude
     * @param mixed $payload
     */
    public function __construct($latitude, $longitude, $payload = null)
    {
        parent::__construct($payload);
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * @param string $latitude
     * @param string $longitude
     * @return Location
     */
    public static function create($latitude, $longitude)
    {
        return new self($latitude, $longitude);
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver()
    {
        return [
            'type' => 'poll',
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
