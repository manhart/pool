<?php
/*
 * g7system.local
 *
 * JsonConfig.class.php created at 13.04.21, 23:18
 *
 * @author A.Manhart <alexander@manhart-it.de>
 */

interface JsonConfig {
    public function loadConfig(string $json): bool;
    public function getConfig(): string;
}