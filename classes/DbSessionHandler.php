<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Session management via Database (z.B. MySQL).
 * CREATE TABLE `Session` (
 *   `sid` varchar(128) not null,
 *   `expire` int unsigned not null default 0,
 *   `data` mediumblob,
 *   `clientIP` varchar(45),
 *   `userAgent` varchar(255),
 *   PRIMARY KEY  (`sid`),
 *   KEY `ix_expire` (`expire`)
 * ) TYPE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * @since 2004-02-02
 */

declare(strict_types = 1);

namespace pool\classes;

use pool\classes\Core\Http\Request;
use pool\classes\Core\PoolObject;
use pool\classes\Database\DAO;
use pool\classes\Database\Operator;
use SessionHandlerInterface;
use Throwable;

class DbSessionHandler extends PoolObject implements SessionHandlerInterface
{
    private DAO $sessionDAO;

    private array $tableColumns = [];

    private readonly ?int $maxlifetime;

    public function __construct(string $class, ?int $maxlifetime = null)
    {
        /** @var DAO $class */
        $this->sessionDAO = $class::create(throws: true);
        $this->tableColumns = array_flip($this->sessionDAO->getDefaultColumns());
        $maxlifetime ??= (int)ini_get('session.gc_maxlifetime');
        $this->maxlifetime = $maxlifetime;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        try {
            $this->sessionDAO->delete($id);
            return true;
        } catch (Throwable $e) {
            error_log('[DbSessionHandler] Destroy failed: '.$e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $filter = [
                ['expire', Operator::less, time()],
            ];
            return $this->sessionDAO->deleteMultiple($filter)->getAffectedRows();
        } catch (Throwable $e) {
            error_log('[DbSessionHandler] GC failed: '.$e->getMessage());
            return false;
        }
    }

    public function read(string $id): string|false
    {
        try {
            $data = $this->sessionDAO->get($id)->getValue('data', '');
            return (string)$data;
        } catch (Throwable $e) {
            error_log('[DbSessionHandler] Read failed: '.$e->getMessage());
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $lifetime = $this->maxlifetime;
            if ($lifetime <= 0) $lifetime = 1440;
            $expire = time() + $lifetime;

            $remoteAddr = Request::clientIp();
            $userAgent = Request::userAgent();

            $record = [
                'sid' => $id,
                'data' => $data,
                'expire' => $expire,
            ];

            if (isset($this->tableColumns['userAgent'])) $record['userAgent'] = substr($userAgent, 0, 255);
            if (isset($this->tableColumns['clientIP'])) $record['clientIP'] = substr($remoteAddr, 0, 45);

            $this->sessionDAO->upsert($record);

            return true;
        } catch (Throwable $e) {
            error_log('[DbSessionHandler] Write failed: '.$e->getMessage());
            return false;
        }
    }
}
