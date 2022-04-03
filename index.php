<?php

include 'vendor/autoload.php';

use App\Test;
use App\Application;
use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WebSockets\Intents;
use Discord\Parts\Channel\Message;
use Discord\Builders\MessageBuilder;
use Discord\Parts\WebSockets\MessageReaction;


/**
 * @see https://discord.com/developers/docs/intro
 */

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

/* Application::run([
    'token' => env('DISCORD_TOKEN'),
    #'endpoint' => env('DISCORD_ENDPOINT'),
    'logger' => true,
    'intents' => Intents::GUILDS | Intents::GUILD_MESSAGES | Intents::GUILD_MESSAGE_REACTIONS,
]);

return; */

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

        /* Test::run($discord);
        return; */

        /* $discord->guilds->get('599261105453268992'); */

        $log = $discord->getLogger();
        $log->info('Bot is ready!');

        $discord->on(Event::MESSAGE_CREATE, function (Message $message) use ($log, $allowedUsernames, $discord) {
            $log->info('Message created!');
            $log->info('Username: ' . $message->author->username);
            $log->info('Channel: ' . $message->channel->name);

            if (
                $message->author->bot
                && $message->author->id === env('DISCORD_CLIENT_ID')
            ) {
                return;
            }

            // Commands
            if ($message->content === '/clear' && env('DISCORD_COMMAND_CLEAR', false)) {
                $log->info('Clearing channel...');
                $channel = $message->channel;
                $content = $message->content;

                $amount = explode(' ', $content)[1] ?? 10;

                $channel->limitDelete($amount);
                return;
            }

            // TODO: Add command to get all of the reports in the last:
            // - 24 hours
            // - 7 days
            // - 30 days
            // - On a specific server with the previous options (24 hours, 7 days & 30 days)

            if ($message->content === '/countdone' && env('DISCORD_COMMAND_COUNT_DONE', false)) {
                $log->info('Showing message of "CountDone" command...');

                $message->delete()
                    ->done(
                        function () use ($message, $discord) {
                            $message->channel->sendEmbed(
                                (new Embed($discord))
                                    ->setTitle('⚠️ CS:GO report count done.')
                                    ->setColor(0xFFC300)
                            );
                        }
                    );

                return;
            }

            // END Commands

            if (!in_array($message->author->username, $allowedUsernames) && env('APP_ENV', 'prd') === 'prd') {
                $log->info('User not allowed! <' . $message->author->username . '>');
                return;
            }

            $message->react('✅')->done(
                function () {
                    // TODO : Add message id to the BD
                }
            );

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
                        // TODO: Add value of the user to the "claimed by" column in the BD
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