<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
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

    function __construct($autoClose = true)
    {
        $this->setAutoClose($autoClose);

        $this->start();
        parent::__construct(Input::INPUT_SESSION);
        $this->write_close();
    }

    /**
     * Starts Session
     */
    function start()
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
     */
    function setAutoClose($autoClose)
    {
        $this->autoClose = $autoClose;
    }

    /**
     * Setzt eine Variable im internen Container.
     * Im Unterschied zu Input::addVar ueberschreibt Input::setVar alle Variablen.
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     * @param mixed $value Wert der Variable
     */
    public function setVar($key, $value = ''): Input
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
     * Setzt eine Variable im internen Container.
     * Im Unterschied zu Input::setVar ueberschreibt Input::addVar keine bereits vorhanden Variablen.
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     * @param mixed $value Wert der Variable
     * @param int $filter
     * @param mixed $filterOptions
     * @return Input Erfolgsstatus
     */
    public function addVar($key, mixed $value = '', int $filter = FILTER_FLAG_NONE, $filterOptions = 0): Input
    {
        $this->start();
        parent::addVar($key, $value, $filter, $filterOptions);
        $this->write_close();
        return $this;
    }

    /**
     * Loescht eine Variable aus dem internen Container.
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     */
    public function delVar($key): self
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
    public function setData(array $data)
    {
        $this->start();
        parent::setData($data);
        $this->write_close();
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
    public function setType(string $key, string $type): bool
    {
        $this->start();
        $result = parent::setType($key, $type);
        if($result) {
            $this->write_close();
        }
        return $result;
    }

    /**
     * Gibt die maximale Lebenszeit der Session zur�ck
     *
     * @return int Maximale Lebenszeit in Sekunden
     */
    function getMaxLifetime(): int
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
    public function regenerate_id()
    {
        session_regenerate_id();
    }

    /**
     * write session and close it. Zu empfehlen bei lang laufenden Programmen, damit andere Scripte nicht gesperrt werden
     *
     */
    function write_close(): bool
    {
        if($this->autoClose) {
            return session_write_close();
        }
        return false;
        //$this->session_started = 0;
    }

    /**
     * destroy the session
     */
    function destroy()
    {
        parent::destroy();
        $this->start();
        if(session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}