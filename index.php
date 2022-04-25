<?php

require_once 'bootstrap.php';

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Member;
use Discord\WebSockets\Intents;
use Discord\Parts\Channel\Message;
use Discord\Parts\WebSockets\MessageReaction;
use Illuminate\Database\Capsule\Manager as DB;


/**
 * @see https://discord.com/developers/docs/intro
 */

$discord = new Discord([
    'token' => env('DISCORD_TOKEN'),
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS,
    'loadAllMembers' => true,
]);

$allowedChannelNames = [];
$allowedUsernames = [
    '★ [PT] UpgradeZone.net | Competitive #1 ★' => 'competitives',
    '★ [PT] UpgradeZone.net | Competitive #2 ★' => 'competitives',
    '★ [PT] UpgradeZone.net | Competitive #3 ★' => 'competitives',
    '★ [PT] UpgradeZone.net | Retakes #1 ★' => 'default',
    '★ [PT] UpgradeZone.net | Retakes #2 ★' => 'default',
    '★ [PT] UpgradeZone.net | Retakes #3 ★' => 'default',
    '★ [PT] UpgradeZone.net | AWP MAPS ★' => 'default',
];

$discord->on(
    'ready',
    function (Discord $discord) use ($allowedUsernames) {

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
                $log->info('Bot message, ignoring...');
                return;
            }

            // Commands
            if ($message->content === '/clear' && env('DISCORD_COMMAND_CLEAR', false)) {
                // log who called the command
                $channel = $message->channel;
                $content = $message->content;
                $amount = explode(' ', $content)[1] ?? 10;

                $log->info('Command: /clear ' . $amount);
                $log->info('Username: ' . $message->author->username);
                $log->info('Channel: ' . $message->channel->name);
                $log->info('Clearing channel...');

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
                        fn () => $message->channel->sendEmbed(
                            (new Embed($discord))
                                ->setTitle('⚠️ CS:GO report count done.')
                                ->setColor(0xFFC300)
                        )
                    );

                return;
            }

            // END Commands

            if (
                !in_array(
                    $message->author->username,
                    array_keys($allowedUsernames)
                )
                && env('APP_ENV', 'prd') === 'prd'
            ) {
                $log->info('User not allowed! <' . $message->author->username . '>');
                return;
            }

            $message->react('✅')->done(
                function () use ($message, $allowedUsernames, $log) {
                    $connectionNameAccordingToBot = $allowedUsernames[$message->author->username] ?? 'default';
                    $log->info('Connection name: ' . $connectionNameAccordingToBot);

                    try {
                        $report = DB::connection($connectionNameAccordingToBot)
                            ->table('hoyxen_reports')
                            ->whereNull('discord_message_id')
                            ->orderBy('time', 'DESC')
                            ->first();

                        $log->info('Retrieved Report data: ' . json_encode($report));
                    } catch (\Throwable $th) {
                        $tracemicrotime = (int) microtime(true);
                        $log->error('<'. $tracemicrotime .'> Error retrieving Report data: ' . $th->getMessage());
                        $log->error('<'. $tracemicrotime .'> Trace: ' . $th->getTraceAsString());
                    }

                    try {
                        $result = DB::connection($connectionNameAccordingToBot)
                            ->table('hoyxen_reports')
                            ->where('id', $report->id)
                            ->update([
                                'discord_message_id' => $message->id,
                            ]);

                        $log->info('Update result: ' . json_encode($result));
                    } catch (\Throwable $th) {
                        $tracemicrotime = (int) microtime(true);
                        $log->error('<'. $tracemicrotime .'> Error updating report data: ' . $th->getMessage());
                        $log->error('<'. $tracemicrotime .'> Trace: ' . $th->getTraceAsString());
                    }

                }
            );

            $message->createReactionCollector(
                fn (MessageReaction $reaction) => $reaction->emoji->name === '✅',
                ['time' => 900 * 1000, 'limit' => 2]
            )->done(
                function ($reactions) use ($message, $log, $allowedUsernames) {
                    $log->info('Reactions: ' . $reactions->count());

                    $connectionNameAccordingToBot = $allowedUsernames[$message->author->username] ?? 'default';
                    $log->info('Connection name: ' . $connectionNameAccordingToBot);
                    if ($reactions->count() === 2) {
                        /**
                         * @var Member $user
                         */
                        $user = $reactions->last()->user;
                        $message->reply("This report has been claimed by $user!");
                        // TODO: Add value of the user to the "claimed by" column in the BD
                        try {
                            $report = DB::connection($connectionNameAccordingToBot)
                                ->table('hoyxen_reports')
                                ->where('discord_message_id', $message->id)
                                ->first();

                            $log->info('Retrieved Report data: ' . json_encode($report));
                        } catch (\Throwable $th) {
                            $tracemicrotime = (int) microtime(true);
                            $log->error('<'. $tracemicrotime .'> Error retrieving Report data: ' . $th->getMessage());
                            $log->error('<'. $tracemicrotime .'> Trace: ' . $th->getTraceAsString());
                        }

                        if (!empty($report)) {
                            try {
                                $result = DB::connection($connectionNameAccordingToBot)
                                    ->table('hoyxen_reports')
                                    ->where('id', $report->id)
                                    ->update([
                                        'discord_claimed_by_user_id' => $user->id,
                                        'discord_claimed_at' => (int) microtime(true),
                                    ]);

                                $log->info('Update result: ' . json_encode($result));
                            } catch (\Throwable $th) {
                                $tracemicrotime = (int) microtime(true);
                                $log->error('<'. $tracemicrotime .'> Error updating Report data: ' . $th->getMessage());
                                $log->error('<'. $tracemicrotime .'> Trace: ' . $th->getTraceAsString());
                            }
                        }
                    } else {
                        // Report expired
                        $message->deleteReaction(Message::REACT_DELETE_EMOJI, '✅');
                        // TODO: Send post to api to update the report, stating/activating the "not claimed" status
                        $message->react('❌')->done(
                            function () use ($message, $connectionNameAccordingToBot, $log) {
                                try {
                                    $report = DB::connection($connectionNameAccordingToBot)
                                        ->table('hoyxen_reports')
                                        ->where('discord_message_id', $message->id)
                                        ->first();

                                    $log->info('Retrieved Report data: ' . json_encode($report));
                                } catch (\Throwable $th) {
                                    $tracemicrotime = (int) microtime(true);
                                    $log->error('<'. $tracemicrotime .'> Error retrieving Report data: ' . $th->getMessage());
                                    $log->error('<'. $tracemicrotime .'> Trace: ' . $th->getTraceAsString());
                                }

                                try {
                                    $result = DB::connection($connectionNameAccordingToBot)
                                        ->table('hoyxen_reports')
                                        ->where('id', $report->id)
                                        ->update([
                                            'discord_claimed_by_user_id' => 0,
                                            'discord_claimed_at' => (int) microtime(true),
                                        ]);

                                    $log->info('Update result: ' . json_encode($result));
                                } catch (\Throwable $th) {
                                    $tracemicrotime = (int) microtime(true);
                                    $log->error('<'. $tracemicrotime .'> Error updating Report data: ' . $th->getMessage());
                                    $log->error('<'. $tracemicrotime .'> Trace: ' . $th->getTraceAsString());
                                }
                            }
                        );
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