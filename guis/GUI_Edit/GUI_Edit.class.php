<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_edit.class.php
 *
 * Die Klasse GUI_Edit erzeugt ein HTML Eingabefeld (<input type="text">).
 * Siehe fuer Uebergabeparameter auch in die abgeleitete Klasse GUI_FormElement!!
 *
 * @version $Id: gui_edit.class.php,v 1.3 2007/05/16 15:17:34 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 *
 * @see GUI_FormElement
 */

/**
 * GUI_Edit
 *
 * Siehe Datei fuer ausfuehrliche Beschreibung!
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_edit.class.php,v 1.3 2007/05/16 15:17:34 manhart Exp $
 * @access public
 * @see GUI_FormElement
 **/
class GUI_Edit extends GUI_InputElement
{
    use Configurable;

//    protected string $storageType = 'JSON';
//
//    use \pool\classes\Configurable;
//
    private array $inspectorProperties = [
        'placeholder' => [
            'attribute' => 'placeholder',
            'type' => 'string',
            'value' => 'placeholder',
            'element' => 'input',
            'inputType' => 'text',
            'caption' => 'Platzhalter'
        ]
    ];
//
//    private array $poolOptions = [];

    /**
     * Initialisiert Standardwerte:
     *
     * Ueberschreiben moeglich ueber GET und POST.
     *
     * Parameter:
     * - type Typ ist fest "text" (bitte diesen Parameter unberuehrt belassen!)
     * - size Bestimmt die Anzeigebreite des Elements (Standard: 20)
     *
     * @access public
     **/
    function init(?int $superglobals=I_GET|I_POST)
    {
        // $this->Defaults->addVar('placeholder', 'hirsch');
        $this->Defaults->addVar(
            array(
                'type'			=> 'text',
                'size'			=> 20,
            )
        );

        parent::init($superglobals);
    }

//    public function hasConfiguration()
//    {
//        // TODO: Implement hasConfiguration() method.
//    }
//
//    public function saveConfiguration()
//    {
//        // TODO: Implement saveConfiguration() method.
//    }
//
//    public function loadConfiguration()
//    {
//        // TODO: Implement loadConfiguration() method.
//    }

    /**
     * Laedt Template "tpl_edit.html". Ist im projekteigenen Skinordner ueberschreibbar!
     *
     * @access public
     **/
    function loadFiles()
    {
        $file = $this->Weblication->findTemplate('tpl_edit.html', __CLASS__, true);
        $this->Template->setFilePath('stdout', $file);
    }
    /**
     * Provisioning data before preparing module and there children.
     */
//    public function provision()
//    {
//        $data = $this->Input->getData();
//        unset(
//            $data['moduleName'],
//            $data['ModuleName'],
//            $data['modulename'],
//            $data['framework'],
//            $data['render']
//        );
//
//        $this->setConfiguration($data);

//        $config = [];
//        $fileName = $this->getFixedParam('loadConfigFromJSON');
//        if($fileName) {
//            $config = $this->loadConfigFromJSON($fileName);
//        }
//        $this->setConfiguration($config);
//    }

//    public function loadConfigFromJSON($fileName)
//    {
//        $file = DIR_DATA_ROOT.'/ModuleConfiguratorTemp/'.$fileName;
//        $json = file_get_contents($file);
//
//        $config = json_decode($json, JSON_OBJECT_AS_ARRAY);
//        if(json_last_error() != JSON_ERROR_NONE) {
//            return [];
//        }
//        return $config;
//    }

    public function prepare()
    {
//        echo 'placeholder: '.$this->getVar('placeholder');
//        $this->setVar($this->options);
        parent::prepare();
    }

    public function getInspectorProperties(): array
    {
        $tmp = $this->inspectorProperties + $this->getDefaultInspectorProperties();
        return $tmp;
    }

//    public function setOptions(array $options)
//    {
//        foreach($options as $key => $value) {
//            if($value === 'true' or $value === 'false') {
//                $value = string2bool($value);
//            }
//            if(isset($this->getInspectorProperties()[$key])) {
//                if($this->getInspectorProperties()[$key]['value'] != $value) {
//                    $this->configuration[$key] = $value;
//                }
//            }
//            else {
//                $this->poolOptions[$key] = $value;
//            }
//        }
//        $this->setVar($this->configuration);
//    }

    /**
     * Verarbeitet Template (Platzhalter, Bloecke, etc.) und generiert HTML Output.
     *
     * @return string HTML Output (Content)
     **/
    function finalize(): string
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }

}