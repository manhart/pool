<?php
/**
* Class GUI_DHtmlCalendar
*
*
* Alle weiteren Parameter werden in der Funktion "init" beschrieben.
*
* Verwendung:
*
* <code>
* </code>
*
* $Log: gui_dhtmlcalendar.class.php,v $
* Revision 1.4  2004/12/07 12:19:17  manhart
* Fix CLICKABLE einstellbar
*
* Revision 1.3  2004/11/23 10:56:47  manhart
* -/-
*
* Hinweis (Alex):
* Letztes Projekt mit den neuesten Features werden in Media-Auftrag GUI_AnzeigeTermin verwendet.
*
* @version $Id: gui_dhtmlcalendar.class.php,v 1.4 2004/12/07 12:19:17 manhart Exp $
* @version $Revision: 1.4 $
*
* @since 2004-11-23
* @package pool
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 * @see GUI_DHtmlCalendar::init()
*/
class GUI_DHtmlCalendar extends GUI_Module
{
    function init($superglobals=I_EMPTY)
    {
        $this->Defaults->addVar(
            array(
                'name' => 'calendar',
                'coupledCalendar' => null,
                'skipMonth' => 0, // wird auch als Url Parameter verwendet, daher kann man bei der Anzeige von mehreren Kalendern mit "modifyMonth" arbeiten
                'modifiyMonth' => 0, // addiert oder subtrahiert von skipMonth eine Anzahl von Monaten! Nur bei mehreren Kalendern sinnvoll
                'user_function_cellclick' => '', // JS Funktion wird ausgefuehrt beim Klick auf eine Kalenderzelle (=Tag)
                'user_function_changemonth' => 'changeMonat',
                'paramNameSkipMonth' => 'skipMonth',
                'title' => 'Kalender-�bersicht',
                'selected_day' => 0,
                'selected_month' => 0,
                'selected_year' => 0,
                // 1 = alle Tage klickbar
                // 2 = vergangene Tage nicht klickbar, heutiger Tag und zukuenftige klickbar
                // 3 = nur Termine klickbar
                'clickable' => 1,

                'template' => 'tpl_dhtmlcalendar.html',
                'css' => 'gui_dhtmlcalendar.css'
            )
        );

        parent::init(I_GET);
    }

    /**
     * L�dt alle ben�tigten Vorlagen, JavaScript und StyleSheet-Dateien
     *
     */
    function loadFiles()
    {
        // Dateiname des Templates
        $template = $this->Input->getVar('template');
        $css = $this->Input->getVar('css');

        // Lade Template
        $file = $this->Weblication->findTemplate($template, $this->getClassName(), true);
        $this->Template->setFilePath('stdout', $file);

        $Headerdata = &$this->Weblication->findComponent('Headerdata');
        if($Headerdata) {
            // prototype.js ben�tigt von date.js
            $jsfile = $this->Weblication->findJavaScript('prototype.js', $this->getClassName(), true);
            $Headerdata->addJavaScript($jsfile);
            // dhtmlcalendar.js ben�tigt von dhtmlcalendar.js
            $jsfile = $this->Weblication->findJavaScript('date.js', $this->getClassName(), true);
            $Headerdata->addJavaScript($jsfile);
            $jsfile = $this->Weblication->findJavaScript('dhtmlcalendar.js', $this->getClassName(), true);
            $Headerdata->addJavaScript($jsfile);
            $cssfile = $this->Weblication->findStyleSheet($css, $this->getClassName(), true);
            $Headerdata->addStyleSheet($cssfile);
        }
    }

    function prepare()
    {
        $Template = & $this->Template;
        $Input = & $this->Input;

        $name = $Input->getVar('name');
        $coupledCalendar = $Input->getVar('coupledCalendar');
        $skipMonth = $Input->getVar($Input->getVar('paramNameSkipMonth'));
        $skipMonth = $skipMonth + $Input->getVar('modifyMonth');

        $Template->setVar(
            array(
                'NAME' => $name,
                'SKIPMONTH' => $skipMonth,
                'OBJFOLLOW' => is_null($coupledCalendar) ? 'null' : $coupledCalendar,
                'USER_FUNCTION_CELLCLICK' => $Input->getVar('user_function_cellclick'),
                'USER_FUNCTION_CHANGEMONTH' => $Input->getVar('user_function_changemonth'),
                'TITLE' => $Input -> getVar('title'),
                'SELECTED_DAY' => $Input -> getVar('selected_day'),
                'SELECTED_MONTH' => $Input -> getVar('selected_month'),
                'SELECTED_YEAR' => $Input -> getVar('selected_year'),
                'CLICKABLE' => $Input -> getVar('clickable')
            )
        );

        // if($coupledCalendar)
        if (!isAjax()) {
            $this->Weblication->Main->addBodyLoad($Input->getVar('name').'.init();');
        }
    }

    /**
     * F�gt einen Termin ein
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @param int $starttime
     * @param int $endtime
     * @param string $imageFull
     * @param string $imageHalf
     * @param string $value
     * @param string $hint
     */
    function addCalendarDate($day, $month, $year, $starttime, $endtime, $imageFull, $imageHalf, $value, $hint)
    {
        $this->Template->newBlock('CalDate');
        $this->Template->setVar(
            array(
                'NAME' => $this->Input->getVar('name'),
                'TAG' => $day,
                'MONAT' => (int)$month,
                'JAHR' => $year,
                'STARTZEIT' => $starttime,
                'ENDZEIT' => $endtime,
                'IMAGEFULL' => $imageFull,
                'IMAGEHALF' => $imageHalf,
                'VALUE' => $value,
                'HINT' => $hint
            )
        );
        $this->Template->leaveBlock();
    }

    /**
     * Setzt das vorbelegte/vormarkierte Datum
     *
     * @param int $day
     * @param int $month
     * @param int $year
     */
    function setSelectedDate($day, $month, $year)
    {
        //echo 'selektiere tag: ' . $day . ' monat: ' . $month . ' jahr: ' . $year . '<br>';

        $this->Input->setVar(
            array(
                'selected_day' => $day,
                'selected_month' => $month,
                'selected_year' => $year
            )

        );
        /*
        $this -> Template -> setVar(
            array(
                'SELECTED_DAY' => $day,
                'SELECTED_MONTH' => $month,
                'SELECTED_YEAR' => $year
            )
        );
        */
    }

    function finalize()
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}