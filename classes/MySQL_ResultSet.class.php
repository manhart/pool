<?php
/**
 * # PHP Object Oriented Library (POOL) #
 *
 * Class MySQL_Resultset abgeleitet von der abstrakten Basisklasse Resultset.
 * Diese Klasse kuemmert sich um das Ergebnis eines ausgefuehrten SQL Statement.
 * Es fuehrt ein SQL Statement aus, speichert je nach Operation (insert, update,
 * select) die Ergebnismenge zwischen. Vererbte Iteratoren sorgen fuer die
 * Navigation in der Ergebnismenge (z.B. $this -> next).
 *
 * Ein "insert" liefert die "last_insert_id" zurueck. Sie kann ueber
 * $this -> getValue('last_insert_id') abgefragt werden. Falls es sich bei der
 * Tabelle um einen Primaerschluessel mit dem Attribut auto_increment handelt.
 *
 * Ein "update" liefert immer die Anzahl betroffener Zeilen: "affected_row"!
 *
 * Ein "select" natuerlich die Ergebnismenge/Entitaeten der Abfrage.
 *
 * @version $Id: MySQL_Resultset.class.php,v 1.13 2007/03/29 09:20:18 manhart Exp $
 * @version $Revision: 1.13 $
 *
 * @see Resultset.class.php
 * @see MySQL_db.class.php
 * @see MySQL_DAO.class.php
 *
 * @since 2003/07/10
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

use pool\classes\Database\DataInterface;
use pool\classes\Utils\Singleton;

/**
 * MySQL_ResultSet
 *
 * Siehe Datei fuer ausfuehrliche Beschreibung!
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@gmx.de>
 * @version $Id: MySQL_Resultset.class.php,v 1.13 2007/03/29 09:20:18 manhart Exp $
 **/
class MySQL_ResultSet extends ResultSet
{
    /**
     * Database Interface for MySQL
     *
     * @var DataInterface|null
     */
    private ?DataInterface $db;

    /**
     * Erwartet Datenbank Layer als Parameter.
     * Der Datenbank Layer ist die Schnittstelle zur MySQL Datenbank.
     * Die MySQL_db Klasse uebt die eigentlichen datenbankspezfischen
     * Operationen (z.B. mysql_connect, mysql_query) aus.
     *
     * @param MySQL_Interface|MySQLi_Interface $db database layer
     * @see MySQL_db
     */
    public function __construct(MySQL_Interface|MySQLi_Interface $db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @return MySQL_Interface|MySQLi_Interface
     */
    public function getDataInterface(): MySQL_Interface|MySQLi_Interface
    {
        return $this->db;
    }

    /**
     * Die Funktion "execute" fuehrt das uebergebene SQL Statement aus
     * und speichert die Ergebnismenge zwischen. Ueber vererbte Iteratoren
     * kann durch das Ergebnis navigiert werden (z.B. $this -> prev()).
     *
     * Fehlermeldungen landen im $this -> errorStack und koennen ueber
     * $this -> getLastError() abgefragt werden.
     *
     * @param string $sql SQL Statement
     * @param string $dbname database name
     * @param callable|null $callbackOnFetchRow
     * @param array $metaData
     * @return boolean Erfolgsstatus (SQL Fehlermeldungen koennen ueber $this -> getLastError() abgefragt werden)
     * @see ResultSet::getLastError()
     */
    public function execute(string $sql, string $dbname='', ?callable $callbackOnFetchRow = null, array $metaData = []): bool
    {
        //clear stored rows
        $this->rowset = [];

        if (!$this->db) {//missing interface
            $this->raiseError(__FILE__, __LINE__, 'No DataInterface available (@execute).');
            return false;//Alternative is a TypeError
        }

        /** @var ?Stopwatch $Stopwatch Logging Stopwatch*/
        $Stopwatch = defined($x = 'LOG_ENABLED') && constant($x) && defined($x = 'ACTIVATE_RESULTSET_SQL_LOG') && constant($x) == 1 ?
            Singleton::get('Stopwatch')->start('SQLQUERY') : null;// start time measurement
        $result = $this->db->query($sql, $dbname);//run
        if ($result) {//success
            switch ($this->db->getLastSQLCommand()) {
                case 'SELECT':
                case 'SHOW':
                case 'DESCRIBE':
                case 'EXPLAIN': //? or substr($cmd, 0, 1) == '('
                    //? ( z.B. UNION
                    if ($this->db->numrows($result) > 0) {
                        $this->rowset = $this->db->fetchrowset($result, $callbackOnFetchRow, $metaData);
                        $this->reset();
                    }
                    $this->db->freeresult($result);
                    break;
                /** @noinspection PhpMissingBreakStatementInspection */
                case 'INSERT'://DML commands
                    $last_insert_id = $this->db->nextid();
                    $idColumns = [
                        'last_insert_id' => $last_insert_id,
                        'id' => $last_insert_id,
                    ];
                case 'UPDATE':
                case 'DELETE':
                    $affected_rows = $this->db->affectedrows();
                    $row = [//id of inserted record or number of rows
                        0 => $last_insert_id ?? $affected_rows,
                        'affected_rows' => $affected_rows
                    ] + ($idColumns??[]);//for insert save value db->nextid()
                    $this->rowset = [0 => $row];
                    $this->reset();
                    break;
            }
        } else {//statement failed
            $error_msg = $this->db->getErrormsg() . ' SQL Statement failed: ' . $sql;
            $this->raiseError(__FILE__, __LINE__, $error_msg);
            $error = $this->db->getError();
            $error['sql'] = $sql;
            $this->errorStack[] = $error;
        }

        // SQL Statement Logging:
        if ($Stopwatch && ($metaData['ResultSetSQLLogging'] ?? true)) {
            $timeSpent = $Stopwatch->stop('SQLQUERY')->getDiff('SQLQUERY');
            $onlySlowQueries = defined($x = 'ACTIVATE_RESULTSET_SQL_ONLY_SLOW_QUERIES') && constant($x);
            $slowQueriesThreshold = defined($x = 'ACTIVATE_RESULTSET_SQL_SLOW_QUERIES_THRESHOLD') && constant($x) ?: 0.01;
            if (!$onlySlowQueries || $timeSpent > $slowQueriesThreshold)
                Log::message("SQL ON DB $dbname: '$sql' in $timeSpent sec.", $timeSpent > $slowQueriesThreshold ? Log::LEVEL_WARN : Log::LEVEL_INFO,
                    configurationName: Log::SQL_LOG_NAME);
            if (!$result) Log::error('SQL-ERROR ON DB ' . $dbname . ': ' . $this->db->getErrormsg());
        }
        return (bool)$result;
    }

    /**
     * define callback for event onFetchingRow
     *
     * @param callable $callback
     */
    public function onFetchingRow(callable $callback)
    {
        $this->db->onFetchingRow($callback);
    }

    /**
     * Gibt die komplette Ergebnismenge im als SQL Insert Anweisungen (String) zurueck.
     *
     * @param string|null $table
     * @return string
     */
    function getSQLInserts(?string $table = null): string
    {
        $sql = '';

        if($this->count() && $table) {
            $line_break = chr(10);
            // Zuerst die Insert Anweisung und die Feldnamen
            foreach($this -> rowset as $row)
            {
                $sql .= 'INSERT INTO '.$table.' (';
                $sql .= implode(',', array_keys($this -> rowset[0]));
                $sql .= ') VALUES (\''.implode('\',\'', array_values($row)).'\');'.$line_break;
            }
        }
        return $sql;
    }
}