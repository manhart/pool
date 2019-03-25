<?php
/**
* -= PHP Object Oriented Library (POOL) =-
*
* Die Klasse MCrypt vereinfacht die Bedienung der MCrypt Library.
* Sie unterstuetzt eine grosse Anzahl Block Algorithmen wie
* DES, TripleDES, Blowfish (default), 3-WAY, SAFER-SK64, SAFER-SK128,
* TWOFISH, TEA, RC2 and GOST in CBC, OFB, CFB und ECB cipher Modes.
* Zusaetzlich unterstuetzt es RC6 und IDEA, die als "non-free" gelten.
*
* Beispiel:
* $Crypt = new MCrypt();
* $Crypt -> setCipher('twofish');
* # use cfb mode for strings and cbc mode for files
* $Crypt -> setMode('cfb');
* $Crypt -> setKey('test key');
* $encrypted = $Crypt -> encrypt('this is a test message');
* $decrypted = $Crypt -> decrypt($encrypted);
*
* Unterst�tzte Ciphers:
    MCRYPT_3DES
    MCRYPT_ARCFOUR_IV (libmcrypt > 2.4.x only)
    MCRYPT_ARCFOUR (libmcrypt > 2.4.x only)
    MCRYPT_BLOWFISH
    MCRYPT_CAST_128
    MCRYPT_CAST_256
    MCRYPT_CRYPT
    MCRYPT_DES
    MCRYPT_DES_COMPAT (libmcrypt 2.2.x only)
    MCRYPT_ENIGMA (libmcrypt > 2.4.x only, alias for MCRYPT_CRYPT)
    MCRYPT_GOST
    MCRYPT_IDEA (non-free)
    MCRYPT_LOKI97 (libmcrypt > 2.4.x only)
    MCRYPT_MARS (libmcrypt > 2.4.x only, non-free)
    MCRYPT_PANAMA (libmcrypt > 2.4.x only)
    MCRYPT_RIJNDAEL_128 (libmcrypt > 2.4.x only)
    MCRYPT_RIJNDAEL_192 (libmcrypt > 2.4.x only)
    MCRYPT_RIJNDAEL_256 (libmcrypt > 2.4.x only)
    MCRYPT_RC2
    MCRYPT_RC4 (libmcrypt 2.2.x only)
    MCRYPT_RC6 (libmcrypt > 2.4.x only)
    MCRYPT_RC6_128 (libmcrypt 2.2.x only)
    MCRYPT_RC6_192 (libmcrypt 2.2.x only)
    MCRYPT_RC6_256 (libmcrypt 2.2.x only)
    MCRYPT_SAFER64
    MCRYPT_SAFER128
    MCRYPT_SAFERPLUS (libmcrypt > 2.4.x only)
    MCRYPT_SERPENT(libmcrypt > 2.4.x only)
    MCRYPT_SERPENT_128 (libmcrypt 2.2.x only)
    MCRYPT_SERPENT_192 (libmcrypt 2.2.x only)
    MCRYPT_SERPENT_256 (libmcrypt 2.2.x only)
    MCRYPT_SKIPJACK (libmcrypt > 2.4.x only)
    MCRYPT_TEAN (libmcrypt 2.2.x only)
    MCRYPT_THREEWAY
    MCRYPT_TRIPLEDES (libmcrypt > 2.4.x only)
    MCRYPT_TWOFISH (for older mcrypt 2.x versions, or mcrypt > 2.4.x )
    MCRYPT_TWOFISH128 (TWOFISHxxx are available in newer 2.x versions, but not in the 2.4.x versions)
    MCRYPT_TWOFISH192
    MCRYPT_TWOFISH256
    MCRYPT_WAKE (libmcrypt > 2.4.x only)
    MCRYPT_XTEA (libmcrypt > 2.4.x only)

    => RC6 and IDEA are considered "non-free".
*
* @date $Date: 2005/12/30 12:45:18 $
* @version $Id: MCrypt.class.php,v 1.3 2005/12/30 12:45:18 manhart Exp $
* @version $Revision: 1.3 $
* @version
*
* @since 2004-04-20
* @author Alexander Manhart <alexander@manhart-it.de>
* @link https://alexander-manhart.de
*
*/

/*
    Public Functions
    --------------------------------------------------------------
    destroy()
    clearKey()
    createIV()
    decrypt($encrypted, $keepIV = 0)
    decryptFile($sourcefile, $destfile)
    encrypt($data)
    encryptFile($sourcefile, $destfile)
    getCipher()
    getKey()
    getMode()
    setCipher($ciphername)
    setKey($encryptkey)
    setMode($encryptmode)
    listAlgorithms()
    listModes()

    Private Functions
    _open_cipher()
    --------------------------------------------------------------
*/


/**
 * MCrypt
 *
 * Siehe Dateidescription fuer ausfuehrliche Beschreibung!
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: MCrypt.class.php,v 1.3 2005/12/30 12:45:18 manhart Exp $
 * @access public
 **/
class MCrypt extends PoolObject
{
    //@var string Cipher zum Verschluesseln
    //@access private
    var $cipher;

    //@var string Schluessel
    //@access private
    var $key;

    //@var string Verschluessslungsmodus
    //@access private
    var $mode;

    /**
     * Constructor untersucht Existenz der libmcrypt!
     *
     * @access public
     **/
    function __construct ()
    {
        parent::__construct();

        // make sure we can use mcrypt_generic_init
        if (!function_exists('mcrypt_generic_init')) {
            trigger_error('libmcrypt >= 2.4.x not available.');
            exit;
        }
    }

    /**
     * Enternt Schluessel
     *
     * @access public
     **/
    function clearKey()
    {
        $this -> key = '';
    }

    /**
     * Creates an IV = initialization vector
     *
     * @access public
     **/
    function createIV()
    {
        // bevor wir einen IV generieren, stellen wir sicher, dass ein Cipher gesetzt wurde
        if ((!isset($this -> cipher)) || (!isset($this -> mode))) {
            trigger_error('createIV: cipher and mode must be set before using createIV', E_USER_ERROR);
            return 0;
        }

        // Oeffne Verschluesslungsmodul
        $td = $this -> _open_cipher();

        // versuche einen IV zu generieren
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);

        // wenn wir keinen generieren konnten, erzeugen wir eine benutzerdefinierte Fehlermeldung
        if (!$iv) {
            trigger_error('createIV: unable to create iv', E_USER_ERROR);
        }

        // cleanup
        @mcrypt_module_close($td);

        // return iv
        return $iv;
    }

    /**
     * Verschluesselt Daten (String).
     *
     * @access public
     * @param string $data Daten
     * @return string Verschluesselte Daten
     **/
    function encrypt($data)
    {
        if ((!isset($this -> cipher)) || (!isset($this -> mode)) || (!isset($this -> key))) {
            trigger_error('encrypt: cipher, mode, and key must be set before using encrypt', E_USER_ERROR);
        }

        // create an IV
        $iv = $this -> createIV();

        // Oeffne Verschluesslungsmodul
        $td = $this -> _open_cipher();

        // initialize encryption
        mcrypt_generic_init ($td, $this->key, $iv);

        $encrypted_data = mcrypt_generic($td, $data);

        // cleanup
        if (function_exists('mcrypt_generic_deinit')) {
            mcrypt_generic_deinit($td);
        }
        mcrypt_module_close($td);

        // free original data
        unset($data);

        // return base64 encoded string
        return base64_encode($iv . $encrypted_data);
    }

    /**
     * Entschluesselt die Daten (String)
     *
     * @param string $encrypted
     * @param integer $keepIV
     * @return entschluesselte Daten
     * @access public
     **/
    function decrypt($encrypted, $keepIV = 0)
    {
        if ((!isset($this -> cipher)) || (!isset($this -> mode)) || (!isset($this -> key))) {
            trigger_error('decrypt: cipher, mode, and key must be set before using decrypt', E_USER_ERROR);
        }

        // extract encrypted value from base64 encoded value
        $data = base64_decode($encrypted);

        // oeffne Verschluesslungsmodul
        $td = $this -> _open_cipher();

        $ivsize = mcrypt_enc_get_iv_size($td);

        // ermittle den IV aus den verschluesselten Daten
        $iv = substr($data, 0, $ivsize);

        // entferne IV aus den Daten um sauber zu entschluesseln
        if ($keepIV != 1) {
            $data = substr($data, $ivsize);
        }

        // initialize decryption
        mcrypt_generic_init ($td, $this -> key, $iv);

        // entschluessle Daten
        $decrypted = mdecrypt_generic ($td, $data);

        // cleanup
        if (function_exists('mcrypt_generic_deinit')) {
            mcrypt_generic_deinit($td);
        }

        mcrypt_module_close($td);

        // free original data
        unset($data);

        return $decrypted;
    }

    /**
     * Verschluesselt eine Datei
     *
     * @param string $sourcefile Quelldatei
     * @param string $destfile Zieldatei
     * @return bool Erfolgsstatus
     **/
    function encryptFile($sourcefile, $destfile)
    {
        // make sure required fields are specified
        if ((!isset($this -> cipher)) || (!isset($this -> mode)) || (!isset($this -> key))) {
            trigger_error('encryptFile: cipher, mode, and key must be set before using encryptFile', E_USER_ERROR);
        }

        // sicherstellen, dass die Datei lesbar ist
        if (!is_readable($sourcefile)) {
            return 0;
        }

        @touch($destfile);

        if (!is_writable($destfile)) {
            return 0;
        }

        // les die Datei in den Speicher zum Entschluesseln
        $fp = fopen($sourcefile, 'r');

        // return false wenn Datei nicht geoeffnet werden kann
        if (!$fp) {
            return 0;
        }

        $filecontents = fread($fp, filesize($sourcefile));
        fclose($fp);

        // oeffne die Zieldatei zum Schreiben
        $dest_fp = fopen($destfile, 'w');

        // return false if unable to open file
        if (!$dest_fp) {
            return 0;
        }

        // write encrypted data to file
        fwrite($dest_fp, $this -> encrypt($filecontents));

        // schliesse Dateizeiger
        fclose($dest_fp);

        return 1;
    }

    /**
     * Entschluesselt eine Datei.
     *
     * @param string $sourcefile Quelldatei
     * @param string $destfile Zieldatei
     * @return bool Erfolgsstatus
     **/
    function decryptFile($sourcefile, $destfile)
    {
        // make sure required fields are specified
        if ((!isset($this -> cipher)) || (!isset($this -> mode)) || (!isset($this -> key))) {
            trigger_error('decryptFile: cipher, mode, and key must be set before using decryptFile', E_USER_ERROR);
        }

        if (!is_readable($sourcefile)) {
            return 0;
        }

        @touch($destfile);

        if (!is_writable($destfile)) {
            return 0;
        }

        $fp = fopen($sourcefile, 'r');

        if (!$fp) {
            return 0;
        }

        $filecontents = fread($fp, filesize($sourcefile));
        fclose($fp);

        $dest_fp = fopen($destfile, w);

        if (!$dest_fp) {
            return 0;
        }

        fwrite($dest_fp, $this -> decrypt($filecontents));

        fclose($dest_fp);

        return 1;
    }

    /**
     * Setzt den Cipher per $ciphername, ueberprueft ob der Cipher unterstuetzt wird
     *
     * @param string $ciphername
     * @return bool Erfolgsstatus
     **/
    function setCipher($ciphername)
    {
        if (in_array($ciphername, $this -> listAlgorithms())) {
            $this -> cipher = $ciphername;
            return 1;
        }
        else {
            return 0;
        }
    }

    /**
     * Gibt den Namen des aktuellen Cipher zurueck.
     *
     * @access public
     * @return string return the name of the current cipher
     **/
    function getCipher()
    {
        return $this -> cipher;
    }

    /**
     * Schluessel setzen.
     *
     * @access public
     * @param string $encryptkey Schluessel
     * @return bool Erfolgsstatus
     **/
    function setKey($encryptkey)
    {
        // make sure cipher and mode are set before setting IV
        if ((!isset($this -> cipher)) || (!isset($this -> mode))) {
            trigger_error('setKey: cipher and mode must be set before using setKey', E_USER_ERROR);
        }

        if (!empty($encryptkey)) {
            // get the size of the encryption key
            $keysize = mcrypt_get_key_size ($this->cipher, $this->mode);

            // if the encryption key is less than 32 characters long and the expected keysize is at least 32 md5 the key
            if ((strlen($encryptkey) < 32) && ($keysize >= 32)) {
                $encryptkey = md5($encryptkey);

            }
            // if encryption key is longer than $keysize and the keysize is 32 then md5 the encryption key
            elseif ((strlen($encryptkey) > $keysize) && ($keysize == 32)) {
                $encryptkey = md5($encryptkey);
            }
            else {
                // if encryption key is longer than the keysize substr it to the correct keysize length
                $encryptkey = substr($encryptkey, 0, $keysize);
            }

            $this->key = $encryptkey;
        }
        else {
            return 0;
        }
    }

    /**
     * Gibt den Schluessel zurueck
     *
     * @access public
     * @return string return the encryption/decryption key
     **/
    function getKey()
    {
        return $this -> key;
    }

    /**
     * Setzt den Verschluesslungsmodus $encryptmode
     *
     * @access public
     * @param string $encryptmode Verschluesslungs-Modus
     * @return bool Erfolgsstatus
     **/
    function setMode($encryptmode)
    {
        // make sure encryption mode is a valid mode
        if (in_array($encryptmode, mcrypt_list_modes())) {
            $this -> mode = $encryptmode;
        }
        else {
            return 0;
        }
    }

    /**
     * Gibt den Verschluesslungs-Modus zurueck
     *
     * @access public
     * @return string return the encryption mode
     **/
    function getMode()
    {
        return $this -> mode;
    }

    /**
     * returns an array with all algorithms
     *
     * @access public
     * @return array returns an array with all algorithms
     **/
    function listAlgorithms()
    {
        return mcrypt_list_algorithms();
    }

    /**
     * returns an array with all modes
     *
     * @access public
     * @return array returns an array with all modes
     **/
    function listModes()
    {
        return mcrypt_list_modes();
    }

    /**
     * Oeffne Cipher
     *
     * @access private
     * @return resource
     **/
    function _open_cipher()
    {
        // oeffne Verschluesslungsmodul
        $td = @mcrypt_module_open($this -> cipher, '', $this -> mode, '');

        // display error if we couldn't open the cipher
        if (!$td) {
            trigger_error('unable to open cipher ' . $this -> cipher . ' in ' . $this -> mode . ' mode', E_USER_ERROR);
        }

        return $td;
    }
}