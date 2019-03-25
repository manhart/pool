<?php
/**
 * GUI_Calendar
 *
 * @package pool
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 * @copyright Copyright (c) 2003
 * @version $Id: gui_calendar.class.php,v 1.2 2007/05/31 14:35:57 manhart Exp $
 * @access public
 **/
class GUI_Calendar extends GUI_Module
{
    /**
    * Default Style f�r normale Zellen
    * @access private
    */
    var $styleNormal = "calCell";

    /**
    * Default Style f�r hervorgehobene Zelle
    * @access private
    */
    var $styleHighlight = "calCellHighlight";

    /**
    * Soll das Datum mittels Styleformatierung hervorgehoben werden?
    * @access private
    */
    var $highlightDate = false;

    function __construct(& $Owner)
    {
        parent::__construct($Owner);
    }

    function init($superglobals=I_EMPTY)
    {
        /*
        * $this -> Defaults -> addVar('id', $this -> getName());
        $this -> Defaults -> addVar('name', $this -> getName());
        $this -> Defaults -> addVar('formname', '');

        $this -> Defaults -> addVar('label', '');
        $this -> Defaults -> addVar('enabled', 1);
        $this -> Defaults -> addVar('checked', 0);
        $this -> Defaults -> addVar('value', '');

        $this -> Defaults -> addVar('bordercolor', 'black');

        $this -> Defaults -> addVar('guierror', '');
        $this -> Defaults -> addVar('bordercolorerror', '#FF0000');

        $this -> Defaults -> addVar('convertcode', '');

        $this -> Defaults -> addVar('onfocus', 'return (false);');

        $this -> Defaults -> addVar('gap', 0);

        parent :: init();
        */
    }

    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_calendar.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('stdout', $file);
    }

    function prepare ()
    {
        /*
        * $Template = & $this -> Template;
        $Input = & $this -> Input;

        $Input -> setByteStream($Input -> getVar($Input -> getVar('name') . '_data'));



        $Input_Data = new Input(INPUT_EMPTY);
        $Input_Data -> setVar('monat', $Input -> getVar('monat') + 1);
        $Url = new Url();
        $Url -> modifyParam($Input -> getVar('name') . '_data', $Input_Data -> getByteStream());
        $Template -> setVar('link_12', $Url -> getUrl());

        */

        //$this -> drawCalendar($this->Input -> getVar('d'),$this->Input -> getVar('m'),$this->Input -> getVar('y'));
        $this -> drawCalendar('1','11','2003');
        /*
        $id = $Input -> getVar('id');
        $name = $Input -> getVar('name');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this -> getName() and $id == $this -> getName()) {
            $id = $name;
        }
        if ($id != $this -> getName() and $name == $this -> getName()) {
            $name = $id;
        }

        //$varname = 'submit_'.$this -> Input -> getVar('formname');
        $Template -> setVar(
            array(
                'ID' => $id,
                'NAME' => $name,
                'SIZE' => $Input -> getVar('size'),
                'DISABLED' => ($Input -> getVar('enabled') == 1) ? '' : 'disabled',
                'CHECKED' => ($Input -> getVar('checked') ? 'checked' : ''),
                'BORDERCOLOR' => ($Input -> getVar('guierror') == $name ? $Input -> getVar('bordercolorerror') : $Input -> getVar('bordercolor')),
                'ONFOCUS' => 'onFocus="' . $Input -> getVar('onfocus') . '"',
                'VALUE' => ($Input -> getVar('value') == '')  ? $Input -> getVar($name) : $Input -> getVar('value')
            )
        );

        if ($Input -> getVar('gap') > 0) {
            $Template -> newBlock('GAP');
            $Template -> setVar('GAP', $Input -> getVar('gap'));
        }

        if ($Input -> getVar('label') != '') {
            $Template -> newBlock('Label');
            $Template -> setVar('label', $Input -> getVar('label'));
            $Template -> setVar('ID', $Input -> getVar('id'));
        }*/
    }


    /**
     * GUI_Calendar::drawCalendar()
     *
     * @param integer $d
     * @param integer $m
     * @param integer $y
     * @return
     **/
    function drawCalendar($d = null,$m = null,$y = null)
    {
        // Als Standardwert aktuelles Datum setzen
        if ((!$m) || (!$y) || (!$d))
        {
            $d = date("d",mktime());
            $m = date("m",mktime());
            $y = date("Y",mktime());
        }
        // Hilfsvariable, die besagt, dass es sich um die erste Woche im Monat handelt
        $firstWeek = true;
        // Jetzt alle Tage abarbeiten
        $this -> Template ->newBlock("cal_row");
        for($i = 0; $i <= $this -> getLastDayOfMonth($m,$y); $i++)
        {
            // Bestimmen, ob das akt. Datum hervorgehoben wird und welcher Style angezeigt wird
            $today = date('Y-m-d',mktime());		// Heute
            if ($this -> highlightDate)
                $actStyle = $this -> styleHighlight;
            else
                $actStyle = $this -> styleNormal;

            if ($firstWeek)
            {
                //echo $this -> getWeekDayOfFirstDayInMonth($m,$y)."<br>";
                for ($j=1; $j < $this -> getWeekDay(1,$m,$y); $j++)
                {
                    $this -> Template ->setVar("CELL_STYLE",$actStyle);
                    $this -> Template ->newBlock("cal_cell");
                    $this -> Template ->setVar("DAY","&nbsp;");
                }
                $firstWeek = false;
            }
            //echo $i." - ".$this -> getWeekDay($i,$m,$y)."<br>";
            else if ($this -> getWeekDay($i,$m,$y) == 0)
            {
                $this -> Template ->newBlock("cal_cell");
                $this -> Template ->setVar("CELL_STYLE",$actStyle);
                $this -> Template ->setVar("DAY",$i);
                $this -> Template ->newBlock("cal_row");

                $emptyRow = true;
            }
            else
            {
                $this -> Template ->newBlock("cal_cell");
                $this -> Template ->setVar("CELL_STYLE",$actStyle);
                $this -> Template ->setVar("DAY",$i);
                $emptyRow = false;
            }
        }
    }

    /**
     * GUI_Calendar::getLastDayOfMonth()
     * Diese Funktion ermittelt den letzten Tag eines Monats
     * @param integer $m
     * @param integer $y
     * @return integer Letzter Tag
     **/
    function getLastDayOfMonth($m,$y)
    {
        for ($tday=28; $tday <= 31; $tday++)
        {
            $tdate = getdate(mktime(0,0,0,$m,$tday,$y));
            if ($tdate["mon"] != $m)
                break;
        }
        $tday--;
        return $tday;
    }

    /**
     * GUI_Calendar::getWeekDayOfFirstDayInMonth()
     * Ermittelt den Wochentag des ersten Tages eines Monats als Zahl
     * von 0 als Sonntag bis 6 als Samstag
     * @param integer $m
     * @param integer $y
     * @return integer Numerischer Wert f�r Tag der Woche: von 0 als Sonntag bis 6 als Samstag
     **/
    function getWeekDayOfFirstDayInMonth($m,$y)
    {
        // Wochentag des ersten Tages ermitteln
        $tStamp = mktime(0,0,0,$m,1,$y);
        $tmpd = getdate($tStamp);
        setlocale("LC_TIME","de_DE");
        $month = strftime("%B",$tStamp);
        return $tmpd["wday"];
    }

    /**
     * GUI_Calendar::getWeekDay()
     * Ermittelt den Wochentag f�r ein Datum als Zahl
     * von 0 als Sonntag bis 6 als Samstag
     * @param integer $m
     * @param integer $y
     * @return integer Numerischer Wert f�r Tag der Woche: von 0 als Sonntag bis 6 als Samstag
     **/
    function getWeekDay($d,$m,$y)
    {
        // Wochentag ermitteln
        $tStamp = mktime(0,0,0,$m,$d,$y);
        $tmpd = getdate($tStamp);
        setlocale("LC_TIME","de_DE");
        $month = strftime("%B",$tStamp);
        return $tmpd["wday"];
    }

    /**
     * GUI_Calendar::finalize()
     *
     * @return
     **/
    function finalize()
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }

    /**
     * GUI_Calendar::setCellStyle()
     *
     * @param $styleName
     * @return void
     **/
    function setCellStyle($styleName)
    {
        $this -> styleNormal = $styleName;
    }

    /**
     * GUI_Calendar::setCellStyleHighlight()
     *
     * @param $styleName
     * @return void
     **/
    function setCellStyleHighlight($styleName)
    {
        $this -> styleHighlight = $styleName;
    }
}