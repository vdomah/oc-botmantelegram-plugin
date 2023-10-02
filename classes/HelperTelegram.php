<?php namespace Vdomah\BotmanTelegram\Classes;

use Lang;
use Vdomah\Botman\Classes\Helper;
use Vdomah\BotmanTelegram\Classes\DriverTelegram\Keyboard;
use October\Rain\Support\Traits\Singleton;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;

class HelperTelegram
{
    use Singleton;

    const REQUEST_CONTACT = '/request_contact';
    const REQUEST_LOCATION = '/request_location';

    /* @return array */
    public function getKeyboardParameters($obQuestion, $cols = 1, $bWithBack = true, $type = Keyboard::TYPE_INLINE, array $arAddButtons = [])
    {
        $parameters = [];
        $parameters['reply_markup'] = $this->getKeyboard($obQuestion, $cols, $type, $bWithBack, $arAddButtons);

        return $parameters;
    }

    /* @return string */
    public function getKeyboard($question, $cols = 1, $type = Keyboard::TYPE_INLINE, $with_back = true, array $arAddButtons = [])
    {
        $row = [];
        $keyboard = Keyboard::create($type)->resizeKeyboard();

        $buttons = [];
        if ($question) {
            $buttons = $question->getButtons();
        }

        if ($cols > 1) {
            foreach ($buttons as $button) {
                if (count($row) >= $cols) {
                    $keyboard->addRowArr($row);
                    $row = [];
                }
                $row[] = $this->makeButton($button);
            }
        } else {
            foreach ($buttons as $button) {
                $row[] = $this->makeButton($button);
                $keyboard->addRowArr([$row]);
                $row = [];
            }
        }

        if (count($row) > 0) {
            $keyboard->addRowArr($row);
            $row = [];
        }
        //trace_log('$arAddButtons', $arAddButtons);
        if (count($arAddButtons)) {
            $row = [];
            foreach ($arAddButtons as $k=>$arAddButton) {
                //if (is_array($arAddButton)) {
                    if (count($arAddButton) > 1 && ( !isset($arAddButton['text']) || !isset($arAddButton['value']) )) {
                        foreach ($arAddButton as $button) {
                            if (isset($button['text']) && isset($button['value'])) {
                                if (count($row) >= $cols) {
                                    $keyboard->addRowArr($row);
                                    $row = [];
                                }
                                $row[] = $this->makeButton($button);
                            }
                        }
                    } else {
                        if (count($arAddButton) == 1) {
                            foreach ($arAddButton as $sValue=>$sText) {
                                if (count($row) >= $cols) {
                                    $keyboard->addRowArr($row);
                                    $row = [];
                                }
                                $row[] = $this->makeButton(['text' => $sText, 'value' => $sValue]);
                            }
                        } elseif (isset($button['text']) && isset($button['value'])) {
                            if (count($row) >= $cols) {
                                $keyboard->addRowArr($row);
                                $row = [];
                            }
                            $row[] = $this->makeButton($arAddButton);
                        }
                    }
//                } else {//not array
//                    if (count($row) >= $cols) {
//                        $keyboard->addRowArr($row);
//                        $row = [];
//                    }
//                    $row[] = $this->makeButton(['text' => $arAddButton, 'value' => $k]);
//                }
                //trace_log('$row', $row);
            }
            $keyboard->addRowArr($row);
            $row = [];
        }

        if ($with_back) {
            $back_exists = false;
            foreach ($buttons as $button) {
                if ($button['value'] == Helper::VALUE_BACK) {
                    $back_exists = true;
                }
            }
            if (!$back_exists) {
                $sText = Helper::instance()->config['lang']['back'];
                $row[] = $this->makeButton(['text' => $sText, 'value' => Helper::VALUE_BACK]);
                $keyboard->addRowArr($row);
            }
        }

        $arKeyboard = $keyboard->toArray();
       // \Log::debug(['$arKeyboard', $arKeyboard]);
        $markup = json_decode($arKeyboard['reply_markup'], true);

        $sReplyMarkup = json_encode($markup);

        return $sReplyMarkup;
    }

    /* @return KeyboardButton */
    public function makeButton(array $button)
    {
        $btn = KeyboardButton::create($button['text']);
        $btn->callbackData($button['value']);
//trace_log('req_cont', $button['value']);
        if (isset($button['request_contact']) || $button['value'] == self::REQUEST_CONTACT) {
            $btn->requestContact();
        }
        if (isset($button['request_location']) || $button['value'] == self::REQUEST_LOCATION) {
            $btn->requestLocation();
        }

        return $btn;
    }
}