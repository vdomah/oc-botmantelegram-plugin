# BotMan for OctoberCMS plugin
Plugin use BotMan framework to create bot backend. Full BotMan docs: https://botman.io.

Manually installing botman package:
````$xslt
composer require botman/botman
````

## Gettings started

1. First you need to install any messenger driver you want to use. For telegram run this in your project route:
````$xslt
composer require botman/driver-telegram
````

2. Then you need to enter your bot's token (and any other params required) in Backend > Settings > Botman.
Create entry in Drivers section and add the following data (for telegram case):
Code: telegram
Settings: 
    token: yourTOKEN

3. Writing bot's logic. With this plugin you can create your own scenarios for your bot to interact with users. 
Scenarios can be set in 
- "user's question pattern - bot's reaction" manner https://botman.io/2.0/receiving
- using conversation classes https://botman.io/2.0/conversations

### Listening to events

You need to create your own plugin to listen to BotMan's events and write your handlers or conversation classes.

In your Plugin.php:
````
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\Telegram\TelegramPhotoDriver;
use Vdomah\Botman\Classes\Helper;
use You\YourPlugin\Conversations\DefaultConversation;

public function boot()
{
    // You need to load required drivers, telegram in this case. 
    // TelegramDriver::class is loaded by default in case you've installed botman/driver-telegram package
    // and no driver was loaded
    Event::listen(Helper::EVENT_LOAD_DRIVER, function () {
        DriverManager::loadDriver(TelegramDriver::class);
        DriverManager::loadDriver(TelegramPhotoDriver::class);
    });

    // than you create your listeners to hear and reply to a message or init a conversation.
    Event::listen(Helper::EVENT_BEFORE_LISTEN, function ($botman) {
        // https://botman.io/2.0/receiving
        $botman->hears('My First Message', function ($bot) {
            $bot->reply('Your First Response');
        });
        
        // https://botman.io/2.0/conversations
        $botman->hears('hello|/start', function (BotMan $bot) {
            $bot->startConversation(new DefaultConversation());
        });
    });
}
````

## Conversations
Conversation is a class that you create in your plugin. For example in "conversations" directory. Please check BotMan's docs: https://botman.io/2.0/conversations

Example from BotMan docs:
````
class OnboardingConversation extends Conversation
{
    protected $firstname;

    protected $email;

    public function askFirstname()
    {
        $this->ask('Hello! What is your firstname?', function(Answer $answer) {
            // Save result
            $this->firstname = $answer->getText();

            $this->say('Nice to meet you '.$this->firstname);
            $this->askEmail();
        });
    }

    public function askEmail()
    {
        $this->ask('One more thing - what is your email?', function(Answer $answer) {
            // Save result
            $this->email = $answer->getText();

            $this->say('Great - that is all we need, '.$this->firstname);
        });
    }

    public function run()
    {
        // This will be called immediately
        $this->askFirstname();
    }
}
````

## Using same application as backend for several different bots

For this purpose you need to create your own endpoints in your plugin in routes.php 
and implement the same logic as in Botman plugin's routes.php.

The key difference in this approach is requirement to add id_prefix parameter to each bot.
To add it to the main bot you can extend default plugin configurations using this event:

````$xslt
Event::listen(Helper::EVENT_AFTER_CONFIG_READY, function ($arConfig) {
    $arConfig['config']['id_prefix'] = 'main_bot';

    return $arConfig;
});
````

And to create your own endpoint create the following routes.php in your plugin:
````$xslt
<?php
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\LaravelCache;
use Vdomah\Botman\Classes\Helper;

Route::any('/botman', function () {
    // load driver(s) you want to use.
    DriverManager::loadDriver(TelegramDriver::class);

    $config = Helper::instance()->config;
    $config['config']['id_prefix'] = 'custom_bot';

    // Create an instance
    $obBotman = BotManFactory::create($config, new LaravelCache);

    // Start listening
    $obBotman->listen();
});
````