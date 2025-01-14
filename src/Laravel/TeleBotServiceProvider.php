<?php

namespace WeStacks\TeleBot\Laravel;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use WeStacks\TeleBot\BotManager;
use WeStacks\TeleBot\Laravel\Artisan\CommandsCommand;
use WeStacks\TeleBot\Laravel\Artisan\LongPollCommad;
use WeStacks\TeleBot\Laravel\Artisan\WebhookCommand;
use WeStacks\TeleBot\Laravel\Controllers\WebhookController;

class TeleBotServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishConfig();

        Route::post('/telebot/webhook/{bot}/{token}', [
            'as' => 'telebot.webhook',
            'uses' => WebhookController::class
        ]);
    }

    public function register()
    {
        $this->registerCommands();
        $this->registerBindings();
        $this->registerNotificationDriver();
    }

    private function publishConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/config/telebot.php', 'telebot');

        $this->publishes([
            __DIR__.'/config/telebot.php' => $this->getConfigPath('telebot.php'),
        ], 'telebot');
    }

    private function getConfigPath($path = '')
    {
        if (function_exists('config_path')) {
            return config_path($path);
        }

        return $this->app->basePath() . '/config' . ($path ? '/' . $path : $path);
    }

    private function registerBindings()
    {
        $this->app->singleton(BotManager::class, function () {
            return new BotManager(config('telebot'));
        });
        $this->app->alias(BotManager::class, 'telebot');
    }

    private function registerCommands()
    {
        $this->commands([
            WebhookCommand::class,
            LongPollCommad::class,
            CommandsCommand::class,
        ]);
    }

    private function registerNotificationDriver()
    {
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('telegram', function () {
                return new TelegramChannel(TeleBot::getFacadeRoot());
            });
        });
    }
}
