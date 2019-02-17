<?php
/**
 * User
 *
 * @package pool
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @access public
 **/
class User extends CustomMySQL_DAO
{
    /**
     * Tabellenspalten werden auf '*' gesetzt. Primaer Schluessel ist 'id'.
     *
     * @access public
     * @param object $db Datenbankhandle
     * @param string $dbname Datenbankname
     * @param string $table Tabelle
     **/
    function User(&$db, $dbname, $table, $autoload_fields=true)
    {
        parent::CustomMySQL_DAO($db, $dbname, $table, $autoload_fields);

        if(!$autoload_fields) {
            $this->setPrimaryKey('idUser');
            $this->setColumns(
                'idUser',
                'username',
                'password'
            );
        }
    }
}