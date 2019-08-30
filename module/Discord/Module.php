<?php
/**
 * Perforce Swarm Discord Module
 *
 * @license     Please see LICENSE.txt in top-level readme folder of this distribution.
 */

namespace Discord;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}