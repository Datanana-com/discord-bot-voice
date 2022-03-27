<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/helpers/main.php';

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\Parts\User\Member;
use Discord\WebSockets\Intents;
use Discord\Parts\Channel\Message;
use Discord\Parts\WebSockets\MessageReaction;

/**
 * @see https://discord.com/developers/docs/intro
 */

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$discord = new Discord([
    'token' => env('DISCORD_TOKEN'),
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS,
    'loadAllMembers' => true,
]);

$discord->on(
    'ready',
    function (Discord $discord) {
        $log = $discord->getLogger();
        $log->info('Bot is ready!');
        $loop = $discord->getLoop();

        $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($log, $loop) {
            $log->info('Message created!');
            $log->info('Username: ' . $message->author->username);
            $log->info('Channel: ' . $message->channel->name);

            if (
                $message->author->username === env('DISCORD_BOT_NAME', 'TestingDevBot')
                || $message->channel->name !== env('DISCORD_CHANNEL_ID', 'sky-testing-bot')
            ) {
                return;
            }

            if ($message->content === '/clear') {
                $log->info('Clearing channel...');
                $channel = $message->channel;
                $content = $message->content;

                $amount = explode(' ', $content)[1] ?? 10;

                $channel->limitDelete($amount);
                return;
            }

            $message->react('✅');

            $message->createReactionCollector(
                fn (MessageReaction $reaction) => $reaction->emoji->name === '✅',
                ['time' => 900 * 1000, 'limit' => 2]
            )->done(
                function ($reactions) use ($message, $log) {
                    $log->info('Reactions: ' . $reactions->count());
                    if ($reactions->count() === 2) {
                        /**
                         * @var Member $user
                         */
                        $user = $reactions->last()->user;
                        $message->reply('This report has been claimed by ' . $user);
                        // TODO: Send count +1 to the api to update the count for the mod
                    } else {
                        // Report expired
                        $message->deleteReaction(Message::REACT_DELETE_EMOJI, '✅');
                        $message->react('❌');
                        // TODO: Send post to api to update the report, stating/activating the "not claimed" status
                    }
                }
            );
        });

        $discord->on(Event::MESSAGE_REACTION_ADD, function (MessageReaction $messageReaction, Discord $discord) {
            if ($messageReaction->user->username === env('DISCORD_BOT_NAME', 'TestingDevBot')) {
                return;
            }

            echo "Reaction added by \"{$messageReaction->user->username}\"", PHP_EOL;
        });

        $discord->on(Event::MESSAGE_REACTION_REMOVE, function (MessageReaction $messageReaction, Discord $discord) {
            if ($messageReaction->user->username === env('DISCORD_BOT_NAME', 'TestingDevBot')) {
                return;
            }

            echo "Reaction removed by \"{$messageReaction->user->username}\"", PHP_EOL;
        });
    }
);

$discord->run();