<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use pool\classes\Core\Input;

class InputSession extends Input
{
    /**
     * @var boolean Flag, ob Session initiiert wurde.
     */
    private bool $session_started = false;

    /**
     * @var bool Schreibe u. entsperre Session
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
     * Starts Session
     */
    public function start(): void
    {
        if(!$this->session_started) {
            $this->session_started = session_start();
        }
        elseif($this->autoClose) {
            @session_start(); // reopen session
            $this->reInit();
        }
    }

    /**
     * @param $autoClose
     * @return InputSession
     */
    function setAutoClose($autoClose): static
    {
        $this->autoClose = $autoClose;
        return $this;
    }

    /**
     * assign data to a variable
     *
     * @param string $key name of variable
     * @param mixed $value value of variable
     */
    public function setVar(string $key, mixed $value = ''): static
    {
        $this->start();
        parent::setVar($key, $value);
        $this->write_close();
        return $this;
    }

    /**
     * assign data as array
     *
     * @param array $assoc
     * @return Input
     */
    public function setVars(array $assoc): Input
    {
        $this->start();
        parent::setVars($assoc);
        $this->write_close();
        return $this;
    }

    /**
     * Adds a default value/data to a variable if it does not exist. It does not override existing values! We can also add a filter on an incoming variable.
     *
     * @param string $key name of variable
     * @param mixed $value value of variable
     * @param int $filter
     * @param mixed $filterOptions
     * @return InputSession Erfolgsstatus
     */
    public function addVar(string $key, mixed $value = '', int $filter = FILTER_FLAG_NONE, array|int $filterOptions = 0): static
    {
        $this->start();
        parent::addVar($key, $value, $filter, $filterOptions);
        $this->write_close();
        return $this;
    }

    /**
     * merge array with vars but don't override existing vars
     *
     * @param array $vars
     * @return $this
     */
    public function addVars(array $vars): static
    {
        $this->start();
        parent::addVars($vars);
        $this->write_close();
        return $this;
    }

    /**
     * Delete a variable from the session
     *
     * @param string $key name of variable
     */
    public function delVar(string $key): static
    {
        $this->start();
        parent::delVar($key);
        $this->write_close();
        return $this;
    }

    /**
     * Setzt die Daten f�r Input.
     *
     * @param array $data Indexiertes Array, enth�lt je Satz ein assoziatives Array
     */
    public function setData(array $data): static
    {
        $this->start();
        parent::setData($data);
        $this->write_close();
        return $this;
    }

    /**
     * Vereinigt die Variablen Container von zwei Input Objekten. Vorhandene Keys werden nicht ueberschrieben.
     *
     * @param Input $Input Objekt vom Typ Input
     * @param boolean $flip Fuegt die Daten in umgekehrter Reihenfolge zusammen (true), Standard ist false (Parameter nicht erforderlich)
     **/
    public function mergeVars(Input $Input, bool $flip = false): Input
    {
        $this->start();
        parent::mergeVars($Input, $flip);
        $this->write_close();
        return $this;
    }

    /**
     * sets the data type of variable
     *
     * @param string $key variable name
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
     * Gibt die maximale Lebenszeit der Session zur�ck
     *
     * @return int Maximale Lebenszeit in Sekunden
     */
    public function getMaxLifetime(): int
    {
        return (int)get_cfg_var('session.gc_maxlifetime');
    }

    /**
     * get the session ID
     *
     * @return string
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
     * write session and close it. Zu empfehlen bei lang laufenden Programmen, damit andere Scripte nicht gesperrt werden
     *
     */
    public function write_close(): static
    {
        if($this->autoClose) {
            session_write_close();
        }
        return $this;
    }

    /**
     * destroy the session
     */
    public function destroy(): static
    {
        parent::clear();
        $this->start();
        if(session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return $this;
    }
}