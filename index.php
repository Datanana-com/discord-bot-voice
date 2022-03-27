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

$allowedChannelNames = [];
$allowedUsernames = [
    '★ [PT] UpgradeZone.fun | Competitive #1 ★',
    '★ [PT] UpgradeZone.fun | Competitive #2 ★',
    '★ [PT] UpgradeZone.fun | Competitive #3 ★',
    '★ [PT] UpgradeZone.fun | Retakes #1 ★',
    '★ [PT] UpgradeZone.fun | Retakes #2 ★',
    '★ [PT] UpgradeZone.fun | Retakes #3 ★',
];

$discord->on(
    'ready',
    function (Discord $discord) use ($allowedUsernames) {
        $log = $discord->getLogger();
        $log->info('Bot is ready!');

        $discord->on(Event::MESSAGE_CREATE, function (Message $message) use ($log, $allowedUsernames) {
            $log->info('Message created!');
            $log->info('Username: ' . $message->author->username);
            $log->info('Channel: ' . $message->channel->name);

            if (
                $message->author->bot
                && $message->author->id === env('DISCORD_CLIENT_ID')
            ) {
                return;
            }

            if ($message->content === '/clear' && env('DISCORD_COMMAND_CLEAR', false)) {
                $log->info('Clearing channel...');
                $channel = $message->channel;
                $content = $message->content;

                $amount = explode(' ', $content)[1] ?? 10;

                $channel->limitDelete($amount);
                return;
            }

            if (!in_array($message->author->username, $allowedUsernames) && env('APP_ENV', 'prd') === 'prd') {
                $log->info('User not allowed! <' . $message->author->username . '>');
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
                        $message->reply('This report has been claimed by ' . $user . '!');
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

        $discord->on(Event::MESSAGE_REACTION_ADD, function (MessageReaction $messageReaction) use ($log) {
            if ($messageReaction->user->username === env('DISCORD_CLIENT_ID')) {
                return;
            }

            $log->info("Reaction added by \"{$messageReaction->user->username}\"");
        });

        $discord->on(Event::MESSAGE_REACTION_REMOVE, function (MessageReaction $messageReaction) use ($log) {
            if ($messageReaction->user->username === env('DISCORD_CLIENT_ID')) {
                return;
            }

            $log->info("Reaction removed by \"{$messageReaction->user->username}\"");
        });
    }
);

$discord->run();