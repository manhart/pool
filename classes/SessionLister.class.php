<?php
/**
 * # PHP Object Oriented Library (POOL) #
 *
 * Class SessionLister listet alle aktuellen Sessions auf einen Server auf.
 * Es benoetigt hierfuer Leserechte auf den Session Temp Ordner (z.B. /tmp)
 *
 * Methoden:
 * - getSessionsCount()
 * - getSessions()
 *
 * Beispiel:
 * echo $sl -> getSessionsCount()." sessions available<br>";
 *
 * foreach( $sl->getSessions() as $sessName => $sessData ) {
 *    echo "<hr>Session ".$sessName." :<br>";
 *    echo " Rawdata = ".$sessData["raw"]."<br>";
 *    echo " creation date = ".date( "d/m/Y H:i:s",$sessData["creation"])."<br>";
 *    echo " last modif date = ".date( "d/m/Y H:i:s",$sessData["modification"])."<br>";
 *    echo " age = ".round($sessData["age"]/3600/24,1)." days<br>";
 * }
 *
 * $Log: SessionLister.class.php,v $
 * Revision 1.1.1.1  2004/09/21 07:49:25  manhart
 * initial import
 *
 * Revision 1.2  2004/04/01 15:08:44  manhart
 * -
 *
 *
 * @version $Id: SessionLister.class.php,v 1.1.1.1 2004/09/21 07:49:25 manhart Exp $
 * @version $Revision: 1.1.1.1 $
 *
 * @since 2004/03/25
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

if(!defined('CLASS_SESSIONLISTER')) {

    #### Prevent multiple loading
    define('CLASS_SESSIONLISTER', 1);

    /**
     * SessionLister
     *
     * Siehe Datei fuer ausfuehrliche Beschreibung!
     *
     * @package pool
     * @author manhart <alexander.manhart@freenet.de>
     * @version $Id: SessionLister.class.php,v 1.1.1.1 2004/09/21 07:49:25 manhart Exp $
     * @access public
     **/
    class SessionLister extends PoolObject
    {
        //@var array Zwischenspeicher der gelesenen Session
        //@access private
        var $diffSess;

        function getSessionsCount()
        {
            if (!$this -> diffSess) {
                $this -> __readSessions();
            }
            return SizeOf($this -> diffSess);
        }

        function getSessions()
        {
            if (!$this -> diffSess) {
                $this -> __readSessions();
            }
            return $this -> diffSess;
        }

        //------------------ PRIVATE ------------------
        function __readSessions()
        {
            $sessPath = get_cfg_var("session.save_path")."\\";
            $diffSess = array();

            $dh = @opendir($sessPath);
            while(($file = @readdir($dh)) !==false )
            {
                if($file != "." && $file != "..")
                {
                    $fullpath = $sessPath.$file;
                    if(!@is_dir($fullpath))
                    {
                        // "sess_7480686aac30b0a15f5bcb78df2a3918"
                        $fA = explode("_", $file);
                        // array("sess", "7480686aac30b0a15f5bcb78df2a3918")
                        $sessValues = file_get_contents ( $fullpath );	// get raw session data
                        // this raw data looks like serialize() result, but is is not extactly this, so if you can process it... le me know
                        $this->diffSess[$fA[1]]["raw"] = $sessValues;
                        $this->diffSess[$fA[1]]["age"] = time()-filectime( $fullpath );
                        $this->diffSess[$fA[1]]["creation"] = filectime( $fullpath );
                        $this->diffSess[$fA[1]]["modification"] = filemtime( $fullpath );
                    }
                }
            }
            @closedir($dh);
        }
    }
}