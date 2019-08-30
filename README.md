# SwarmDiscordModule
An Helix Swarm module to send notifications to a discord channel when reviews are created or modified

## Use
This module has been developed for Helix Swarm 2019.1 which is based on Zend Framework 3, it won't work with previous versions of Swarm

It will listen to swarm events and will send discord messages through a webhook
The events are :
- review creation and modification
- review comments

The user that owns the review will be tagged at the beginning of the message, except at review creation, for which @everyone is used

## Installation
* Copy the module/Discord directory into your swarm installation module folder (typically /opt/perforce/swarm/module)
* If it doesn't exist, create the custom.modules.config.php file in your swarm config folder  (typically /opt/perforce/swarm/config)
* copy the following code into custom.modules.config.php :
```
<?php
\Zend\Loader\AutoloaderFactory::factory(
    array(
        'Zend\Loader\StandardAutoloader' => array(
            'namespaces' => array(
                'Discord'      => BASE_PATH . '/module/Discord/src',
            )
        )
    )
);
return [
    'Discord'
];
```
* edit the module configuration file to fill in your data (see below)
* restart your swarm installation (probably by restarting the apache server with apachectl restart)

## module configuration
the configuration file is in module/Discord/config/module.config.php

only the last part 'discord' should be edited with the following data :

```
'discord' => [
  // ip of the server, used if you don't have a hostname, otherwise swarm will generate urls based on 'localhost'
	'swarm_host' => 'xxx.xxx.xxx.xxx',
  // array of correspondance between swarm/perforce ids and discord ids
  // see https://support.discordapp.com/hc/en-us/articles/206346498-Where-can-I-find-my-User-Server-Message-ID-
	'discord_ids' => [
		'swarm_user_id' => 'discord_id',
		'joe' => '123456789132456789',
	],
  // the webhook url generated on discord to be able to post messages in your discord channel
  // see https://support.discordapp.com/hc/en-us/articles/228383668-Intro-to-Webhooks
  // don't append anything at the end of the webhook url
	'discord_webhook' => 'https://discordapp.com/api/webhooks/123456789/xxxxxxxxxxxxxxxxx',
]
```
