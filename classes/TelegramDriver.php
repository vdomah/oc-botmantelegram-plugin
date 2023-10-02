<?php namespace Vdomah\BotmanTelegram\Classes;

use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\TelegramDriver as TelegramDriverBase;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Location;
use Vdomah\Botman\Classes\BotMan\IncomingMessage;

class TelegramDriver extends TelegramDriverBase
{
    public static $editActions = [
        'editMessageText',
        'editMessageCaption',
        'editMessageMedia',
        'editMessageReplyMarkup',
    ];

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $noAttachments = $this->event->keys()->filter(function ($key) {
            return in_array($key, ['audio', 'voice', 'video', 'photo', 'location', 'contact', 'document', 'poll']);
        })->isEmpty();

        if ($noAttachments && !is_null($this->payload->get('poll'))) {
            $noAttachments = false;
        }
//\Log::debug(['$this->event, $this->payload', $this->event, $this->payload]);
        $out = $noAttachments && is_null($this->payload->get('inline_query')) && (! is_null($this->event->get('from')) || ! is_null($this->payload->get('callback_query'))) && ! is_null($this->payload->get('update_id'));
//    \Log::debug(['$out matchesRequest', $out, $noAttachments, ! is_null($this->event->get('from')), ! is_null($this->payload->get('callback_query')), ! is_null($this->payload->get('update_id'))]);
        return $out;
    }

    /**
     * Convert a Question object into a valid
     * quick reply response object.
     *
     * @param \BotMan\BotMan\Messages\Outgoing\Question $question
     * @return array
     */
    private function convertQuestionKeyboard(Question $question)
    {
        $replies = Collection::make($question->getButtons())->map(function ($button) {
            $data = [
                'text' => (string) $button['text'],
            ];

            if (!isset($button['additional']['switch_inline_query']) &&
                !isset($button['additional']['switch_inline_query_current_chat'])) {
                $data['callback_data'] = (string) $button['value'];
            }

            $data = array_merge($data, $button['additional']);

            return [$data];
        });

        return $replies->toArray();
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {//\Log::debug(['$additionalParameters tg dr', $additionalParameters]);
        $this->endpoint = 'sendMessage';

        $recipient = $matchingMessage->getRecipient() === '' ? $matchingMessage->getSender() : $matchingMessage->getRecipient();
        $parameters = array_merge_recursive([
            'chat_id' => $recipient,
        ], $additionalParameters);

        if (isset($parameters['message_id'])) {
            $this->endpoint = 'editMessageText';
        } else {
//            $parameters['text'] = $message->getText();
        }

        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $message->getText();
//\Log::debug(['$parameters', $parameters]);
            if (!isset($parameters['reply_markup'])) {
                $parameters['reply_markup'] = json_encode([
                    'inline_keyboard' => $this->convertQuestionKeyboard($message),
                ], true);
            }
        } elseif ($message instanceof OutgoingMessage) {
            if ($message->getAttachment() !== null) {
                $attachment = $message->getAttachment();
                $parameters['caption'] = $message->getText();
                if ($attachment instanceof Image) {
                    if (strtolower(pathinfo($attachment->getUrl(), PATHINFO_EXTENSION)) === 'gif') {
                        $this->endpoint = 'sendDocument';
                        $parameters['document'] = $attachment->getUrl();
                    } else {
                        $this->endpoint = 'sendPhoto';
                        $parameters['photo'] = $attachment->getUrl();
                    }
                    // If has a title, overwrite the caption
                    if ($attachment->getTitle() !== null) {
                        $parameters['caption'] = $attachment->getTitle();
                    }
                } elseif ($attachment instanceof Video) {
                    $this->endpoint = 'sendVideo';
                    $parameters['video'] = $attachment->getUrl();
                } elseif ($attachment instanceof Audio) {
                    $this->endpoint = 'sendAudio';
                    $parameters['audio'] = $attachment->getUrl();
                } elseif ($attachment instanceof File) {
                    $this->endpoint = 'sendDocument';
                    $parameters['document'] = $attachment->getUrl();
                } elseif ($attachment instanceof Location) {
                    $this->endpoint = 'sendLocation';
                    $parameters['latitude'] = $attachment->getLatitude();
                    $parameters['longitude'] = $attachment->getLongitude();
                    if (isset($parameters['title'], $parameters['address'])) {
                        $this->endpoint = 'sendVenue';
                    }
                }
            } elseif (isset($parameters['question']) && isset($parameters['options'])) {
                $this->endpoint = 'sendPoll';
            } else {
                $parameters['text'] = $message->getText();
            }
        } else {
            $parameters['text'] = $message;
        }

        return $parameters;
    }

    public function loadMessages()
    {
        $id_prefix = isset($this->config['id_prefix']) ? $this->config['id_prefix'] : '';

        if ($this->payload->get('callback_query') !== null) {
            $callback = Collection::make($this->payload->get('callback_query'));

            $messages = [
                new IncomingMessage($callback->get('data'), $callback->get('from')['id'],
                    $callback->get('message')['chat']['id'], $callback->get('message'), $id_prefix),
            ];
        } elseif ($this->isValidLoginRequest()) {
            $messages = [
                new IncomingMessage('', $this->queryParameters->get('id'), $this->queryParameters->get('id'), $this->queryParameters, $id_prefix),
            ];
        } else {
            $event = $this->event->all();

            $messages = [
                new IncomingMessage(
                    $this->event->get('text'),
                    isset($event['from']['id']) ? $event['from']['id'] : null,
                    isset($event['chat']['id']) ? $event['chat']['id'] : null,
                    $this->event
                ),
            ];
        }

        $this->messages = $messages;
    }

    /**
     * Edits Message Text
     * message.
     * @param  int $chatId
     * @param  int $messageId
     * @return Response
     */
    private function editMessageText($chatId, $messageId, $text)
    {
        $parameters = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'inline_keyboard' => [],
        ];
        if ($this->config->get('throw_http_exceptions')) {
            return $this->postWithExceptionHandling($this->buildApiUrl('editMessageText'), [], $parameters);
        }
        return $this->http->post($this->buildApiUrl('editMessageText'), [], $parameters);
    }

    /**
     * @param $url
     * @param array $urlParameters
     * @param array $postParameters
     * @param array $headers
     * @param bool $asJSON
     * @param int $retryCount
     * @return Response
     * @throws TelegramConnectionException
     */
    private function postWithExceptionHandling(
        $url,
        array $urlParameters = [],
        array $postParameters = [],
        array $headers = [],
        $asJSON = false,
        int $retryCount = 0
    ) {
        $response = $this->http->post($url, $urlParameters, $postParameters, $headers, $asJSON);
        $responseData = json_decode($response->getContent(), true);
        if ($response->isOk() && isset($responseData['ok']) && true ===  $responseData['ok']) {
            return $response;
        } elseif ($this->config->get('retry_http_exceptions') && $retryCount <= $this->config->get('retry_http_exceptions')) {
            $retryCount++;
            if ($response->getStatusCode() == 429 && isset($responseData['retry_after']) && is_numeric($responseData['retry_after'])) {
                usleep($responseData['retry_after'] * 1000000);
            } else {
                $multiplier = $this->config->get('retry_http_exceptions_multiplier')??2;
                usleep($retryCount*$multiplier* 1000000);
            }
            return $this->postWithExceptionHandling($url, $urlParameters, $postParameters, $headers, $asJSON, $retryCount);
        }
        $responseData['description'] = $responseData['description'] ?? 'No description from Telegram';
        $responseData['error_code'] = $responseData['error_code'] ?? 'No error code from Telegram';
        $responseData['parameters'] = $responseData['parameters'] ?? 'No parameters from Telegram';


        $message = "Status Code: {$response->getStatusCode()}\n".
            "Description: ".print_r($responseData['description'], true)."\n".
            "Error Code: ".print_r($responseData['error_code'], true)."\n".
            "Parameters: ".print_r($responseData['parameters'], true)."\n".
            "URL: $url\n".
            "URL Parameters: ".print_r($urlParameters, true)."\n".
            "Post Parameters: ".print_r($postParameters, true)."\n".
            "Headers: ". print_r($headers, true)."\n";

        $message = str_replace($this->config->get('token'), 'TELEGRAM-TOKEN-HIDDEN', $message);
        throw new TelegramConnectionException($message);
    }

    /**
     * This hide the inline keyboard, if is an interactive message.
     */
    public function messagesHandled()
    {
        $callback = $this->payload->get('callback_query');
        $hideInlineKeyboard = $this->config->get('hideInlineKeyboard', true);
//\Log::debug(['$hideInlineKeyboard', $hideInlineKeyboard]);
        if ($callback !== null && $hideInlineKeyboard && !in_array($this->endpoint, self::$editActions)) {
            $callback['message']['chat']['id'];
            $this->removeInlineKeyboard(
                $callback['message']['chat']['id'],
                $callback['message']['message_id']
            );
        }
    }

    /**
     * Removes the inline keyboard from an interactive
     * message.
     * @param  int $chatId
     * @param  int $messageId
     * @return Response
     */
    private function removeInlineKeyboard($chatId, $messageId)
    {
        $hideInlineKeyboard = $this->config->get('hideInlineKeyboard', true);

        if (!$hideInlineKeyboard) {
            return;
        }

        $parameters = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'inline_keyboard' => [],
        ];
        if ($this->config->get('throw_http_exceptions')) {
            return $this->postWithExceptionHandling($this->buildApiUrl('editMessageReplyMarkup'), [], $parameters);
        }
        return $this->http->post($this->buildApiUrl('editMessageReplyMarkup'), [], $parameters);
    }
}