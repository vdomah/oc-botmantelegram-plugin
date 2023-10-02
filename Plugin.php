<?php namespace Vdomah\BotmanTelegram;

use Event;
use BotMan\Drivers\Telegram\TelegramAudioDriver;
use BotMan\Drivers\Telegram\TelegramFileDriver;
use BotMan\Drivers\Telegram\TelegramLocationDriver;
use BotMan\Drivers\Telegram\TelegramVideoDriver;
use System\Classes\PluginBase;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramPhotoDriver;
use Vdomah\Botman\Classes\Helper;
use Vdomah\BotmanTelegram\Classes\TelegramDriver;

class Plugin extends PluginBase
{
    public $require = ['Vdomah.Botman'];

    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    public function boot()
    {
        // Loading all telegram related driver
        Event::listen(Helper::EVENT_LOAD_DRIVER, function () {
            DriverManager::loadDriver(TelegramDriver::class);
//            DriverManager::loadDriver(TelegramAudioDriver::class);
//            DriverManager::loadDriver(TelegramFileDriver::class);
//            DriverManager::loadDriver(TelegramLocationDriver::class);
            DriverManager::loadDriver(TelegramPhotoDriver::class);
//            DriverManager::loadDriver(TelegramVideoDriver::class);
        });
    }
}
