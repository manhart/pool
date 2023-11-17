<?php

/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */
declare(strict_types = 1);

namespace pool\traits;

use Closure;
use pool\classes\Core\Weblication;
use pool\classes\Database\DAO;
use pool\classes\Exception\InvalidArgumentException;

Trait CUD{
    abstract protected function getInput();

    abstract protected function idUser();

    /**
     * @param DAO $DAO
     * @param array|null $dataMask
     * @param string $successMessage
     * @param array $verbs
     * @param string|null $rowName
     * @param array $collisionFilter
     * @param string $collisionMessage
     * @param array|null $data
     * @param Closure|null $savePreHook
     * @param Closure|null $savePostHook
     * @param Closure|null $rowGenerator
     * @param Closure|null $updateOverride
     * @param Closure|null $insertOverride
     * @return array
     */
    protected function cudSave(DAO    $DAO, ?array $dataMask = null, string $successMessage = 'successfully %s.', array $verbs = ['inserted', 'updated'], string $rowName = null,
                               array  $collisionFilter = [], string $collisionMessage = 'saving failed: non unique identifier',
                               ?array &$data = null, ?Closure $savePreHook = null, ?Closure $savePostHook = null, ?Closure $rowGenerator = null, ?Closure $updateOverride = null, ?Closure $insertOverride = null): array
    {
        if (!isset($dataMask) && !isset($data))
            throw new InvalidArgumentException('Attempted to perform save operation without any data');
        $rowName ??= 'row';
        $pk = $DAO->getPrimaryKey()[0];
        [&$result, &$persistId, &$row, &$success, &$message] = Weblication::makeResultArray(
            ...([$pk => (int)($this->getInput()->getVar($pk) ?? 0), $rowName => []]), success: false, message: '');
        $collisionFilter[] = [$pk, 'unequal', $persistId];
        if (count($collisionFilter) > 1 && $DAO->getCount(filter: $collisionFilter)->getCountValue()) {
            $message = $collisionMessage;
            return $result;
        }
        if (isset($dataMask))
            $data = array_intersect_key($this->getInput()->getData(), array_flip($dataMask));

        //preprocess data
        $savePreHook?->call($this, $DAO, $persistId, $pk);

        //db transaction
        $dbColumns = $DAO->getDefaultColumns();
        if (array_search('modifier', $dbColumns))
            $data['modifier'] = $this->idUser();
        if ($isUpdate = (bool)$persistId) {// update
            $data[$pk] = $persistId;
            $Set = $updateOverride?->call($this, $DAO, $persistId, $pk) ?? $DAO->update($data);
        } else {// insert
            if (array_search('creator', $dbColumns))
                $data['creator'] = $this->idUser();
            unset($data[$pk]);
            $Set = $insertOverride?->call($this, $DAO, $pk) ?? $DAO->insert($data);
            $persistId = $Set->getLastInsertID();
        }
        if ($lastError = $Set->getLastError()) {
            $message = $lastError['message'];
            return $result;
        }

        //postprocess hook
        $savePostHook?->call($this, $DAO, $persistId, $pk);

        //craft result
        $rowSet = $rowGenerator?->call($this, $DAO, $persistId, $pk)
            ?? $DAO->get($persistId);
        if (($lastError = $rowSet->getLastError())) {
            $message = $lastError['message'];//success?
            return $result;
        }
        $row = $rowSet->getRecord();
        $message = sprintf($successMessage, $verbs[(int)$isUpdate]);
        $success = true;
        return $result;
    }
}


