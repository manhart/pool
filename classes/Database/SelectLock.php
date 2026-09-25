<?php
declare(strict_types = 1);

/*
 * This file is part of POOL (PHP Object-Oriented Library).
 * Copyright (c) Alexander Manhart <alexander@manhart-it.de>
 * See LICENSE for the full copyright and license information.
 */

namespace pool\classes\Database;

/**
 * SELECT locking clauses and wait modifiers; support depends on the database server.
 */
enum SelectLock: string
{
    case forUpdate = 'FOR UPDATE';
    case lockInShareMode = 'LOCK IN SHARE MODE';
    case nowait = 'NOWAIT';
    case skipLocked = 'SKIP LOCKED';

    /**
     * Limit the lock wait to the given number of seconds.
     * Pass the returned clause after the locking mode in the SELECT options.
     */
    public static function wait(int $seconds): SqlStatement
    {
        return new SqlStatement("WAIT $seconds");
    }
}
