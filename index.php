<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/helpers/main.php';

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Guild;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\Repository\Guild\EmojiRepository;
use Discord\WebSockets\Event;

/**
 * @see https://discord.com/developers/docs/intro
 */

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$discord = new Discord([
    'token' => env('DISCORD_TOKEN')
]);

$discord->on(
    'ready',
    function (Discord $discord) {
        $discord->getLogger()->info('Bot is ready!');

        $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            if ($message->channel !== env('DISCORD_CHANNEL_ID', 'testing-bot')) {
                return;
            }

            $message->react('✅');
            $message->react('❌');
        });

        $discord->on(Event::MESSAGE_REACTION_ADD, function (MessageReaction $messageReaction, Discord $discord) {
            if ($messageReaction->user->bot) {
                return;
            }

            $messageReaction;

            echo "Reaction added by \"{$messageReaction->user->username}\"", PHP_EOL;

            if ($channel = $discord->getChannel(943139145344229466)) {
                $channel->sendMessage(
                    MessageBuilder::new()
                    ->setContent("Reaction added by {$messageReaction->user}")
                );
            } else {
                echo "Could not find channel \"testing-bot-report\"";
            }

        });

        $discord->on(Event::MESSAGE_REACTION_REMOVE, function (MessageReaction $messageReaction, Discord $discord) {
            echo "Reaction removed by \"{$messageReaction->user->username}\"", PHP_EOL;
        });
    }
);

$discord->run();