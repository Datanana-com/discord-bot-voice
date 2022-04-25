<?php

require_once 'bootstrap.php';

use App\Application;
use Discord\WebSockets\Intents;

/**
 * @see https://discord.com/developers/docs/intro
 */

$app = new Application([
    'token' => env('DISCORD_TOKEN'),
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS,
    'loadAllMembers' => true,
]);

$app->discord->run();