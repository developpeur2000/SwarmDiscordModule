<?php
/**
 * Perforce Swarm Discord Module
 *
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 */

$listeners = [Discord\Listener\DiscordActivityListener::class];

return [
    'listeners' => $listeners,
    'service_manager' =>[
        'factories' => array_fill_keys(
            $listeners,
            Events\Listener\ListenerFactory::class
        )
    ],
    Events\Listener\ListenerFactory::EVENT_LISTENER_CONFIG => [
        Events\Listener\ListenerFactory::ALL => [
            Discord\Listener\DiscordActivityListener::class => [
                [
                    Events\Listener\ListenerFactory::PRIORITY => Events\Listener\ListenerFactory::HANDLE_MAIL_PRIORITY + 1,
                    Events\Listener\ListenerFactory::CALLBACK => 'handleEvent',
                    Events\Listener\ListenerFactory::MANAGER_CONTEXT => 'queue'
                ]
            ]
        ],
    ],
    'discord' => [
		'swarm_host' => 'your_host_ip',
		'discord_ids' => [
			'swarm_id' => 'discord_id',
		],
		'discord_webhook' => 'https://discordapp.com/api/webhooks/webhook_id/webhook_token',
    ]
];