<?php namespace Vdomah\BotmanTelegram\Classes;

use BotMan\BotMan\Messages\Attachments\Image;
use Vdomah\Botman\Classes\BotMan\IncomingMessage;
use BotMan\Drivers\Telegram\Exceptions\TelegramAttachmentException;

class TelegramPhotoDriver extends \BotMan\Drivers\Telegram\TelegramPhotoDriver
{
    /**
     * Load Telegram messages.
     */
    public function loadMessages()
    {
        $message = new IncomingMessage(
            Image::PATTERN,
            $this->event->get('from')['id'],
            $this->event->get('chat')['id'],
            $this->event, $this->config['id_prefix'] ?? ''
        );
        $message->setImages($this->getImages());

        $this->messages = [$message];
    }

    /**
     * Retrieve a image from an incoming message.
     * @return array A download for the image file.
     * @throws TelegramAttachmentException
     */
    private function getImages()
    {
        $photos = $this->event->get('photo');
        $largetstPhoto = array_pop($photos);
        $response = $this->http->get($this->buildApiUrl('getFile'), [
            'file_id' => $largetstPhoto['file_id'],
        ]);

        $responseData = json_decode($response->getContent());

        if ($response->getStatusCode() !== 200) {
            throw new TelegramAttachmentException('Error retrieving file url: '.$responseData->description);
        }

        $url = $this->buildFileApiUrl($responseData->result->file_path);

        return [new Image($url, $largetstPhoto)];
    }

}