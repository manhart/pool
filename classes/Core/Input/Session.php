<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core\Input;

/**
 * Class Session
 *
 * @package pool\classes\Core\Input
 * @since 2003-07-10
 */
class Session extends Input
{
    /**
     * @var boolean mark the session as started
     */
    private bool $session_started = false;

    /**
     * @var bool writes the session every time a data change occurs and closes the session
     */
    private bool $autoClose = true;

    function __construct($autoWriteCloseAtEachDataChange = true)
    {
        $this->setAutoClose($autoWriteCloseAtEachDataChange);

        $this->start();
        parent::__construct(Input::SESSION);
        $this->write_close();
    }

    /**
     * Starts a session
     */
    public function start(): void
    {
        if (!$this->session_started) {
            $this->session_started = session_start();
        } elseif ($this->autoClose) {
            @session_start(); // reopen session
            $this->reInit();
        }
    }

    /**
     * Set auto close session
     */
    public function setAutoClose($autoClose): static
    {
        $this->autoClose = $autoClose;
        return $this;
    }

    /**
     * Set a value to a variable
     */
    public function setVar(string $key, mixed $value = '', bool $suppressException = false): static
    {
        $this->start();
        try {
            parent::setVar($key, $value, $suppressException);
        }
        finally {
            $this->write_close();
        }
        return $this;
    }

    /**
     * Set more values as an array
     */
    public function setVars(array $assoc, bool $suppressException = false): static
    {
        $this->start();
        foreach ($assoc as $key => $value) {
            parent::setVar($key, $value, $suppressException);
        }
        $this->write_close();
        return $this;
    }

    /**
     * Adds a default value/data to a variable if it does not exist. It does not override existing values! We can also add a filter on an incoming
     * variable.
     *
     * @return Session Erfolgsstatus
     * @deprecated
     */
    public function addVar(string $key, mixed $value = '', int $filter = FILTER_FLAG_NONE, array|int $filterOptions = 0): static
    {
        $this->start();
        parent::addVar($key, $value, $filter, $filterOptions);
        $this->write_close();
        return $this;
    }

    /**
     * Merge array with vars but don't override existing vars
     *
     * @deprecated
     */
    public function addVars(array $vars): static
    {
        $this->start();
        foreach ($vars as $key => $value) {
            parent::addVar($key, $value);
        }

        $this->write_close();
        return $this;
    }

    /**
     * Delete a session variable
     */
    public function delVar(string $key): static
    {
        $this->start();
        parent::delVar($key);
        $this->write_close();
        return $this;
    }

    /**
     * Overwrites the data of the session with the data of the array
     */
    public function setData(array $data): static
    {
        $this->start();
        parent::setData($data);
        $this->write_close();
        return $this;
    }

    /**
     * Joins the variable containers of two input objects. Existing keys are not overwritten.
     *
     * @param boolean $flip if true, the variables of the other input object are merged into the internal container (affects the order of merge)
     */
    public function mergeVars(Input $Input, bool $flip = false): Input
    {
        $this->start();
        parent::mergeVars($Input, $flip);
        $this->write_close();
        return $this;
    }

    /**
     * Sets the data type of variable
     *
     * @param string $type data type
     * @see Input::getType()
     */
    public function setType(string $key, string $type): static
    {
        $this->start();
        parent::setType($key, $type);
        $this->write_close();
        return $this;
    }

    /**
     * Delivers the maximum session lifetime in seconds
     */
    static public function getMaxLifetime(): int
    {
        return (int)get_cfg_var('session.gc_maxlifetime');
    }

    /**
     * Get the current session ID
     */
    public function getSID(): string
    {
        return session_id();
    }

    /**
     * Update the current session id with a newly generated one
     */
    public function regenerate_id(): static
    {
        session_regenerate_id();
        return $this;
    }

    /**
     * Write the session and close it. Recommended for long-running scripts so that other scripts or ajax calls are not blocked
     */
    public function write_close(): static
    {
        if ($this->autoClose) {
            session_write_close();
        }
        return $this;
    }

    /**
     * Destroy the session
     */
    public function destroy(): static
    {
        parent::clear();
        $this->start();
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return $this;
    }
}
