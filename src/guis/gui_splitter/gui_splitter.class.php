<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_splitter.class.php
 *
 * Der Splitter hat zwei Modi. Der Erste teilt Listen alphabetisch, der Zweite numerisch auf.
 * Beide Modi sind kombinierbar!
 *
 * @version $id: gui_splitter.class.php,v 1.7 2003/11/17 09:25:30 manhart exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004-02-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_Splitter
 *
 * Navigationsleiste fuer Listen zum seitenweise Blaettern oder alphabetische Auswahl.
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_splitter.class.php,v 1.16 2007/07/05 07:14:13 manhart Exp $
 * @access public
 **/
class GUI_Splitter extends GUI_Module
{
    //@var string Puffervarible fuer die Ausgabe in finalize
    //@access private
    var $splitter = '';

    /**
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer vom Typ Component
     **/
    function __construct(& $Owner)
    {
        parent::__construct($Owner);
    }

    /**
     * Standardwerte:
     * type=digits|chars|page selbsterkl�rend, seitenweise ueber Seitenzahlen oder alphabetisch Trennung oder Seitennavigation
     * "digits" stellt eine Navigation mit Seitenzahlen dar (like Suchmaschinen)
     * "chars" stellt eine Navigation mit alphabetischer Gliederung oder beliebigen Zeichen dar (z.B. ABC)
     * "page" stelle eine einfach seitenweise Navigation her: vorherige Seite, naechste Seite und Erste Seite sowie letzte Seite
     *
     * query="" wenn etwas an die Url angehaengt werden soll, kann dies ueber diesen Parameter hinzugefuegt werden
     *
     * Type "digits" betreffend:
     * numRecords=1000 Anzahl der Datensaetze
     * maxRecordsPerPage=10 Anzahl der Datensaetze pro Seite
     * splitterPos=0 Aktuelle Startposition
     * hasDropDown=true DropDown Kaestchen aktivieren
     * createDropDown=true erstellt per select ein DropDown Kaestchen
     * maxDigits=5 Maximale Anzahl Seiten die angezeigt werden sollen (vergroessert oder verkleinert die Leiste)
     * dropdownstyleclass="" Style fuer das DropDown Steuerelement
     * digits_urlParam=Aendert Standard URL Parameter zur Uebergabe der Position (Default: splitterPos)
     * digits_first="Erste Seite" Text fuer die erste Seite
     * digits_last="Letzte Seite" Text fuer die letzte Seite
     * digits_separator="..." Trenner
     * text.parenthesis_opened="[" Umklammerung der Seitenzahlen (auf)
     * text.parenthesis_closed="]" Umklammerung der Seitenzahlen (zu)
     * text.parenthesis_current_opened="&gt;" Umklammerung der aktiven Seitenzahl (auf)
     * text.parenthesis_current_closed="&lt;" Umklammerung der aktiven Seitenzahl (zu)
     *
     * Type "chars" betreffend:
     * splitter="A;B;C;D;E;F;G;H;I;J;K;L;M;N;O;P;Q;R;S;T;U;V;W;X;Y;Z;0-9;Sonderzeichen" dargestellte Zeichenauswahl
     * splitter.trans="A;B;C;D;E;F;G;H;I;J;K;L;M;N;O;P;Q;R;S;T;U;V;W;X;Y;Z;0,1,2,3,4,5,6,7,8,9;!,",#,$,%,\',(,),[,],{,},*,+,-,.,/,\,:,<,>,=,?,@,^,_,�,~,|" beim Klick auf den Splitter werden diese Zeichen an das Script fuer die jeweilige Abfrage uebertragen
     * splitterChar="" angeklicktes Zeichen
     * chars.text.separator="|" angezeigter Zeichentrenner
     *
     * Type "page" betreffend:
     * page_urlParam=Aendert Standard URL Parameter zur Uebergabe der Position (Default: splitterPos)
     * page_next=Beschriftung/Image fuer "naechste Seite"
     * page_prior=Beschriftung/Image fuer "vorherige Seite"
     * page_first=Beschriftung/Image fuer "Erste Seite"
     * page_last=Beschriftung/Image fuer "Letzte Seite"
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar('type', 'digits');
        $this -> Defaults -> addVar('query', '');

        #### Parameter fuer Type: digits
        $this -> Defaults -> addVar('numRecords', 1000);
        $this -> Defaults -> addVar('maxRecordsPerPage', 10);
        $this -> Defaults -> addVar('splitterPos', 0);
        $this -> Defaults -> addVar('hasDropDown', true);
        $this -> Defaults -> addVar('createDropDown', true);
        $this -> Defaults -> addVar('maxDigits', 5);
        $this -> Defaults -> addVar('dropdownstyleclass', '');

        $this -> Defaults -> addVar('digits_urlParam', 'splitterPos');
        $this -> Defaults -> addVar('digits_urlAttribute', array());
        $this -> Defaults -> addVar('digits_first', 'Erste&nbsp;Seite');
        $this -> Defaults -> addVar('digits_last', 'Letzte&nbsp;Seite');
        $this -> Defaults -> addVar('digits_separator', '...');
        $this -> Defaults -> addVar('text.parenthesis_opened', '[');
        $this -> Defaults -> addVar('text.parenthesis_closed', ']');
        $this -> Defaults -> addVar('text_space', ' ');
        $this -> Defaults -> addVar('text.parenthesis_current_opened', '&gt;');
        $this -> Defaults -> addVar('text.parenthesis_current_closed', '&lt;');

        #### Parameter fuer Type: chars
        $this -> Defaults -> addVar('splitter', 'A;B;C;D;E;F;G;H;I;J;K;L;M;N;O;P;Q;R;S;T;U;V;W;X;Y;Z;0-9;Sonderzeichen');
        $this -> Defaults -> addVar('splitter.trans', 'A;B;C;D;E;F;G;H;I;J;K;L;M;N;O;P;Q;R;S;T;U;V;W;X;Y;Z;0,1,2,3,4,5,6,7,8,9;' .
                                                      '!,",#,$,\',\%,(,),[,],{,},*,+,-,.,/,\,:,<,>,=,?,@,^,\_,�,~,|');
        $this -> Defaults -> addVar('splitter.active', '1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1');
        $this -> Defaults -> addVar('chars.url.param', 'splitterChar');
        $this -> Defaults -> addVar('splitterChar', '');
        $this -> Defaults -> addVar('chars.text.separator', ' | ');
        $this -> Defaults -> addVar('chars.style.splitter', '');
        $this -> Defaults -> addVar('chars.style.separator', '');
        $this->Defaults->addVar('formatdigits', '%01d');

        #### Parameter fuer Type: page
        $this->Defaults->addVar('page_urlParam', 'splitterPos');
        $this->Defaults->addVar('page_next', 'N�chste&nbsp;Seite');
        $this->Defaults->addVar('page_prior', 'Vorherige&nbsp;Seite');
        $this->Defaults->addVar('page_first', 'Erste&nbsp;Seite');
        $this->Defaults->addVar('page_last', 'Letzte&nbsp;Seite');
        $this->Defaults->addVar('page_next_disabled', '');
        $this->Defaults->addVar('page_prior_disabled', '');
        $this->Defaults->addVar('page_first_disabled', '');
        $this->Defaults->addVar('page_last_disabled', '');
        $this->Defaults->addVar('page_separator', '&nbsp;');
        $this->Defaults->addVar(
            array(
                'page_next_href' => null,
                'page_prior_href' => null,
                'page_first_href' => null,
                'page_last_href' => null
            )
        );
        $this->Defaults->addVar('jsClick', '');

        parent::init(I_GET);
    }

    /**
     * Lade Templates Typ bezogen
     *
     * @access public
     **/
    function loadFiles()
    {
        switch($this -> Input -> getVar('type')){
            case 'digits':
                $file = $this -> Weblication -> findTemplate('tpl_splitter_digits.html', $this -> getClassName(), true);
                $this -> Template -> setFilePath('stdout', $file);
                break;

            case 'page':
                $file = $this -> Weblication -> findTemplate('tpl_splitter_page.html', $this -> getClassName(), true);
                $this -> Template -> setFilePath('stdout', $file);
                break;
        } // switch
    }

    /**
     * Waehlt den Typ des Splitters, baut die Navileiste auf und puffert die Navigationsleiste.
     *
     * @access public
     **/
    function prepare ()
    {
        $Template = & $this -> Template;
        $Input = & $this -> Input;


        $numRecords = (int)$Input -> getVar('numRecords');
        $maxRecordsPerPage = (int)$Input -> getVar('maxRecordsPerPage');

        $numPages = ($numRecords > 0) ? ceil($numRecords / $maxRecordsPerPage) : 0;

        switch($Input -> getVar('type')) {
            case 'digits':
                $urlParam = $Input -> getVar('digits_urlParam');
                $splitterPos = (int)$Input -> getVar($urlParam);
                $splitterPos = $splitterPos/* - 1*/;

                //$activePage = ($numRecords > 0 and $splitterPos > 0) ? ceil(($numPages * $splitterPos) / $numRecords) : 0;
                $activePage = ceil($splitterPos / $maxRecordsPerPage);

                //echo $numPages;
                if (($activePage >= $numPages or $activePage<0) and ($activePage!=0 and $numPages!=0)) {
                    $Url=new Url();
                    $Url->modifyParam($urlParam, ($numPages > 0) ? ($activePage==$numPages?0:($numPages * $maxRecordsPerPage)-$maxRecordsPerPage) : 0);
                    $Url->restartUrl();
                }

                $this -> splitter = $this -> getDigits($numPages, $activePage);
                break;

            case 'chars':
                $this -> splitter = $this -> getChars();
                break;

                // Pagemode
            case 'page':
                $urlParam = $Input -> getVar('page_urlParam');
                $splitterPos = (int)$Input -> getVar($urlParam);
                if($splitterPos > $numRecords) {
                    $Url = new Url();
                    $Url -> modifyParam($urlParam, ($numPages > 0) ? (($numPages * $maxRecordsPerPage)-$maxRecordsPerPage) : 0);
                    $Url -> restartUrl();
                }
                $this -> splitter = $this -> getPage();
                break;

                

        }
    }

    /**
     * GUI_Splitter::getDigits()
     *
     * @access private
     * @param integer $numPages Anzahl Seiten insgesamt
     * @param integer $activePage Aktive Seite
     * @return string digitale Navileiste
     **/
    function getDigits($numPages, $activePage=0)
    {
        if ($numPages == 0) {
            return '';
        }
        $Input = & $this -> Input;
        $Template = & $this -> Template;

        $urlParam = $Input->getVar('digits_urlParam');

        $jsClick = $this->Input->getVar('jsClick');

        $url = new Url();
        $url -> modifyParam($urlParam, null);

        // Default Page Settings
        $firstpage = $Input->getVar('digits_first');
        $lastpage  = $Input->getVar('digits_last');

        $separator = $Input -> getVar('digits_separator');
        $parenthesis_opened = $Input -> getVar('text.parenthesis_opened');
        $parenthesis_closed = $Input -> getVar('text.parenthesis_closed');
        $parenthesis_current_opened = $Input -> getVar('text.parenthesis_current_opened');
        $parenthesis_current_closed = $Input -> getVar('text.parenthesis_current_closed');
        $space = $Input -> getVar('text_space');
        $urlAttributes = $Input -> getVar('digits_urlAttribute');
        $formatdigits = $Input->getVar('formatdigits');

        $hasDropDown = $Input -> getVar('hasDropDown');
        $createDropDown = $this->Input -> getVar('createDropDown');
        $maxDigits = $Input -> getVar('maxDigits');
        $maxRecordsPerPage = $Input -> getVar('maxRecordsPerPage');

        // Url Query
        $query = trim($Input -> getVar('query'));
        if ($query != '') {
            $query = explode('&', $query);
            for ($i = 0; $i < SizeOf($query); $i++) {
                $arr = explode('=', $query[$i]);
                $url->modifyParam($arr[0], $arr[1]);
            }
        }

        $Template -> setVar(
            array(
                'firstpage' => '&nbsp;',
                'lastpage' => '&nbsp;'
            )
        );
        $splitter = '';
        $i_was_there = false;

        $showAllPages=false;
        if($maxDigits == $numPages) {
            $plusmax = $maxDigits;
            $showAllPages=true;
        }
        else {
            $plusmax = round(($maxDigits - 1) / 2);
        }
        $activePage = $activePage + 1;


        if ($hasDropDown) {
            $url->modifyParam($urlParam, null);
            $href = $url->getUrl();
            $token = '?';
            if(strrpos($href, 'php') != strlen($href)-1) {
                $token = '&';
            }

            if($createDropDown) {
            if($jsClick) {
                    $onchange = $jsClick."(this.value, '$urlParam');";
            }
            else {
                    $onchange = "location.href='".$href.$token.$urlParam."=' + this.value";
            }

                $buf = "<select size=\"1\" name=\"selpage\" class=\"".$Input->getVar('dropdownstyleclass')."\" onchange=\"".$onchange."\">";
    
            $navpage = '';
            for ($p = 0; $p < $numPages; $p++) {
                $selected = '';
                if ($activePage - 1 == $p) {
                    $selected = 'selected';
                }
                $navpage .= "            <option $selected value=\"".$p * $maxRecordsPerPage."\">".sprintf($formatdigits, ($p+1))."</option>";
            }
            $buf = $parenthesis_current_opened.$buf.$navpage.'</select>'.$parenthesis_current_closed;
                $Template->setVar('middle', $buf);
            }
            else {
                $activeClass = $this->Input->getVar('digits_dd_activeClass');
                for ($p = 0; $p < $numPages; $p++) {
                    $this->Template->newBlock('pages');
                    $this->Template->setVar('pageNumber', $p);
                    $active = '';
                    if ($activePage - 1 == $p) {
                        $active = $activeClass;
                    }
                    $this->Template->setVar('pageActive', $active);
                }
            }

        }
        else {
            $buf = $parenthesis_current_opened.' '.sprintf($formatdigits, $activePage).' '.$parenthesis_current_closed;
            $Template -> setVar('middle', $buf);
        }

        for ($i = 1; $i<=$plusmax; $i++) {
            $show_prior = true;
            if($showAllPages) {
                $pageNr = $i;
                if($pageNr>=$activePage) $show_prior=false;
            }
            else {
                $pageNr = ($activePage + $i - $plusmax) - 1;
            }
            if($show_prior) {
                $Template->newBlock('prior');
                $pos = ($pageNr - 1) * $maxRecordsPerPage;
                $url->modifyParam($urlParam, $pos);
                if ($pageNr <= 0) {
                    $prior_href = '';
                    $prior_url = '';
                }
                else {
                    $pageNr = sprintf($formatdigits, $pageNr);
                    if($jsClick) {
                        $prior_href = '<span onclick="'.$jsClick.'('.$pos.')" class="cursor">'.$parenthesis_opened.$space.$pageNr.
                            $space.$parenthesis_closed.'</span>';
                    }
                    else {
                        $prior_href=$parenthesis_opened.$space.$url->getHref($pageNr, URL_TARGET_SELF, $urlAttributes).
                            $space.$parenthesis_closed;
                        $prior_url=$url->getUrl();
                    }
                }
                $Template->setVar('prior_url', $prior_url);
                $Template->setVar('prior', $prior_href);
                $Template->setVar('page_nr', $pageNr);
                $Template->setVar('pos', $pos);
            }

            $show_next=true;
            if($showAllPages) {
                $pageNr = $i;
                if($pageNr<$activePage or $i+1>$numPages) $show_next=false;
            }
            else {
                $pageNr = $activePage + $i - 1;
            }

            if($show_next) {
                $Template->newBlock('next');
                $pos = $pageNr * $maxRecordsPerPage;
                $url->modifyParam($urlParam, $pos);
                if ($pageNr >= $numPages) {
                    $next_href = ''; $next_url = '';
                }
                else {
                    $pageNr = sprintf($formatdigits, $pageNr+1);
                    if($jsClick) {
                        $next_href = '<span onclick="'.$jsClick.'('.$pos.')" class="cursor">'.$parenthesis_opened.$space.$pageNr.
                            $space.$parenthesis_closed.'</span>';
                    }
                    else {
                        $next_href = $parenthesis_opened.$space.$url->getHref($pageNr, URL_TARGET_SELF, $urlAttributes).
                            $space.$parenthesis_closed;
                    }
                    $next_url = $url->getUrl();
                }
                $Template->setVar('next_url', $next_url);
                $Template->setVar('next', $next_href);
                $Template->setVar('page_nr', $pageNr);
                $Template->setVar('pos', $pos);
            }
        }
        $Template->leaveBlock();

        // FirstPage
        if(!$showAllPages) {
            $ppp = '';
            if ($plusmax < $activePage - 1) {
                $url -> modifyParam($urlParam, 0);
                if($jsClick) {
                    $firstpage = '<span onclick="'.$jsClick.'(0)" class="cursor">'.$parenthesis_opened.$space.$firstpage.$space.$parenthesis_closed.'</span>';
                }
                else {
                    $firstpage = $parenthesis_opened.$space.$url->getHref($firstpage, URL_TARGET_SELF, $urlAttributes).$space.$parenthesis_closed;
                }
                $Template->setVar('firstpage', $firstpage);
                if ($plusmax < $activePage - 1) {
                    $ppp = $separator;
                }
            }
            $Template -> newBlock('ppp1');
            $Template -> setVar('ppp', $ppp);
            $Template -> leaveBlock();

            // LastPage
            $ppp = '';
            if ($activePage < $numPages - $plusmax) {
                $lastpageNr = ($numPages - 1) * $maxRecordsPerPage;
                $url->modifyParam($urlParam, $lastpageNr);
                if($jsClick) {
                    $lastpage = '<span onclick="'.$jsClick.'('.$lastpageNr.')" class="cursor">'.$parenthesis_opened.$space.$lastpage.$space.$parenthesis_closed.'</span>';
                }
                else {
                    $lastpage = $parenthesis_opened.$space.$url->getHref($lastpage, URL_TARGET_SELF, $urlAttributes).$space.$parenthesis_closed;
                }
                $Template->setVar('lastpage', $lastpage);
                if ($activePage < $numPages - $plusmax) {
                    $ppp = $separator;
                }
            }
            $Template -> newBlock('ppp2');
            $Template -> setVar('ppp', $ppp);
            $Template -> leaveBlock();
        }

        $Template -> parse('stdout');
        $splitter = $Template -> getContent('stdout');
        return $splitter;
    }

    /**
     * GUI_Splitter::getChars()
     *
     * @access private
     * @return string Alphabetische Navileiste
     **/
    function getChars()
    {
        $Input = & $this -> Input;

        $url = new Url();

        $splitter = $Input -> getVar('splitter');
        $splitter_trans = $Input -> getVar('splitter.trans');
        $splitter_active = $Input -> getVar('splitter.active');	// AHO 17.03.04
        $separator = $Input -> getVar('chars.text.separator');

        $styleseparator = $Input -> getVar('chars.style.separator');
        if ($styleseparator) {
            $separator = '<span class="'.$styleseparator.'">'.$separator.'</span>';
        }
        $stylesplitter = $Input -> getVar('chars.style.splitter');

        $splitterArr = explode(';', $splitter);
        $splitter_transArr = explode(';', $splitter_trans);
        $splitter_activeArr = explode(';', $splitter_active);	// AHO 17.03.04

        if (count($splitterArr) != count($splitter_transArr)) {
            $this -> raiseError(__FILE__, __LINE__,
                'Laengen der Parameter "splitter" und "splitter.trans" stimmen nicht �berein! Modulabbruch!');
            return '';
        }

        $splitterChar = $Input -> getVar($Input -> getVar('chars.url.param'));
        $splitterChar = empty($splitterChar) ? $splitter_transArr[0] : $splitterChar;

        // Url Query
        $query = trim($Input -> getVar('query'));
        if ($query != '') {
            $query = explode('&', $query);
            for ($i = 0; $i < SizeOf($query); $i++) {
                $arr = explode('=', $query[$i]);
                $url -> modifyParam($arr[0], $arr[1]);
            }
        }

        $splitter = '';
        $numsplits = count($splitterArr);
        for ($i = 0; $i < $numsplits; $i++) {
            $bHref=false;
            if ($splitterChar != $splitter_transArr[$i]) {
                // Erweiterung AHO (Andreas Horvath) 17.03.04
                if ($splitter_activeArr[$i] == 1) {
                    $bHref=true;
                }
                // Ende AHO
            }
            else {
                $bHref=true;
            }

            if(!$bHref) {
                $splitter .= '<span class="'.$stylesplitter.'">'.$splitterArr[$i].'</span>';
            }
            else {
                $url -> modifyParam($Input -> getVar('chars.url.param'), $splitter_transArr[$i]);
                $splitter .= $url -> getHref('<span class="'.$stylesplitter.'">'.$splitterArr[$i].'</span>');
            }


            if ($i < ($numsplits - 1)) {
                $splitter .= $separator;
            }
        }

        return $splitter;
    }

    /**
     * Liefert weitenweise Bl�tterung
     *
     * @return string
     */
    function getPage()
    {
        /* @var $Template Template */
        $Template = & $this -> Template;

        /* @var $Input Input */
        $Input = & $this -> Input;

        $splitter = '';

        $page_first = $Input -> getVar('page_first');
        $page_last = $Input -> getVar('page_last');
        $page_next = $Input -> getVar('page_next');
        $page_prior = $Input -> getVar('page_prior');
        $page_first_disabled = $Input -> getVar('page_first_disabled');
        $page_last_disabled = $Input -> getVar('page_last_disabled');
        $page_next_disabled = $Input -> getVar('page_next_disabled');
        $page_prior_disabled = $Input -> getVar('page_prior_disabled');
        $page_separator = $Input -> getVar('page_separator');
        $page_urlParam = $Input -> getVar('page_urlParam');

        $splitterPos = (int)$Input -> getVar($page_urlParam);
        $numRecords = $Input -> getVar('numRecords');
        $maxRecordsPerPage = $Input -> getVar('maxRecordsPerPage');

        $Url = new Url();

        // Url Query
        $query = trim($Input -> getVar('query'));
        if ($query != '') {
            $query = explode('&', $query);
            for ($i = 0; $i < SizeOf($query); $i++) {
                $arr = explode('=', $query[$i]);
                $Url -> modifyParam($arr[0], $arr[1]);
            }
        }


        #### first page
        $Url -> modifyParam($page_urlParam, 0);
        if ($splitterPos != 0) {
            $page_first_href = $Input -> getVar('page_first_href');
            if ($page_first_href) {
                $page_first_href = str_replace('{SPLITTERPOS}', 0, $page_first_href);
                $firstpage = '<a href="' . $page_first_href . '" title="' . $this -> Defaults -> getVar('page_first') . '">' .
                    $page_first . '</a>';
            }
            else {
                $firstpage = $Url -> getHref($page_first);
            }
        }
        else {
            $firstpage = ($page_first_disabled) ? $page_first_disabled : $page_first;
        }

        #### prior page
        $splitter .= $page_separator;
        if (0 <= $splitterPos - $maxRecordsPerPage) {
            $page_prior_href = $Input -> getVar('page_prior_href');
            if ($page_prior_href) {
                $page_prior_href = str_replace('{SPLITTERPOS}', ($splitterPos - $maxRecordsPerPage), $page_prior_href);
                $priorpage = '<a href="' . $page_prior_href . '" title="' . $this -> Defaults -> getVar('page_prior') . '">' .
                    $page_prior . '</a>'; //$page_prior_href;
            }
            else {
                $Url -> modifyParam($page_urlParam, $splitterPos - $maxRecordsPerPage);
                $priorpage = $Url -> getHref($page_prior);
            }
        }
        else {
            $priorpage = ($page_prior_disabled) ? $page_prior_disabled : $page_prior;
        }

        #### next page
        if ($numRecords > $splitterPos + $maxRecordsPerPage) {
            $page_next_href = $Input -> getVar('page_next_href');
            if ($page_next_href) {
                $page_next_href = str_replace('{SPLITTERPOS}', ($splitterPos + $maxRecordsPerPage), $page_next_href);
                $nextpage = '<a href="' . $page_next_href . '" title="' . $this -> Defaults -> getVar('page_next') . '">' .
                    $page_next . '</a>';
            }
            else {
                $Url -> modifyParam($page_urlParam, $splitterPos + $maxRecordsPerPage);
                $nextpage = $Url -> getHref($page_next);
            }
        }
        else {
            $nextpage = ($page_next_disabled) ? $page_next_disabled : $page_next;
        }

        #### last page
        $lastPos = floor($numRecords / $maxRecordsPerPage) * $maxRecordsPerPage;
        if ($lastPos == $numRecords) {
            $lastPos -= $maxRecordsPerPage;
        }

        if (($splitterPos != $lastPos and $splitterPos < $lastPos)) {
            $page_last_href = $Input -> getVar('page_last_href');
            if ($page_last_href) {
                $page_last_href = str_replace('{SPLITTERPOS}', $lastPos, $page_last_href);
                $lastpage = '<a href="' . $page_last_href . '" title="' . $this -> Defaults -> getVar('page_last') . '">' .
                    $page_last . '</a>';
            }
            else {
                $Url -> modifyParam($page_urlParam, $lastPos);
                $lastpage = $Url -> getHref($page_last);
            }
        }
        else {
            $lastpage = ($page_last_disabled) ? $page_last_disabled : $page_last;
        }
        $Template -> setVar(
            array(
                'firstpage' => $firstpage,
                'lastpage' => $lastpage,
                'priorpage' => $priorpage,
                'nextpage' => $nextpage,
                'separator' => $page_separator
            )
        );
        $Template -> parse('stdout');
        $splitter = $Template -> getContent('stdout');
        return $splitter;
    }

    /**
     * Gibt die Daten zurueck
     *
     * @access public
     * @return string Splitter
     **/
    function finalize()
    {
        return $this -> splitter;
    }
}