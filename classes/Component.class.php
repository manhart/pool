<?php
/**
 * POOL (PHP Object Oriented Library): Component ist der gemeinsame Vorfahr aller Komponenten.
 *
 * Letzte aenderung am: $Date: 2006/12/01 09:08:54 $
 *
 * @version $Id: Component.class.php,v 1.7 2006/12/01 09:08:54 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link http://www.misterelsa.de
 * @package pool
 */

/**
 * Component ist die Basisklasse fuer alle Komponenten. Komponenten sind persistente Objekte.
 *
 * Komponenten sind persistente Objekte, die ueber das folgende Verhalten verfuegen:
 * - Eigentuemerschaft: Sie koennen als Eigentuemer andere Komponenten verwalten. Z.B. wenn die Komponente A der
 * - Eigentuemer der Komponente B ist, dann ist A fuer die Freigabe von B verantwortlich, wenn A freigegeben wird.
 * - Eindeutige Objektnamen
 *
 * Eine Komponente (= Eigentuemer) dient als Container vieler anderer Komponenten.
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @package pool
 **/
class Component extends PoolObject
{
    /**
     * Eigentuemer vom Typ Component
     *
     * @var Component|null $Owner
     */
    protected ?Component $Owner = null;

    /**
     * contains all components
     *
     * @var array<Component> $components
     */
    private array $components = [];

    /**
     * Unique Name
     *
     * @var string $name
     */
    private string $name;

    /**
     * Array als Zaehler, gewaehrleistet eindeutige Komponentennamen (fortlaufend)
     *
     * @var array $uniqueNameCounter
     */
    private array $uniqueNameCounter = [];

    /**
     * Webanwendung
     *
     * @var Weblication|null $Weblication
     */
    public ?Weblication $Weblication = null;

    /**
     * Session
     * @var ISession|null $Session
     */
    public ?ISession $Session = null;

    /**
     * @var string directory that contains the class
     */
    private string $classDirectory;

    /**
     * Der Konstruktor erhaelt als Parameter den Eigentuemer dieses Objekts. Der Eigent�mer muss vom Typ Component abgeleitet sein.
     *
     * @param Component|null $Owner Der Eigentuemer erwartet ein Objekt vom Typ Component.
     */
    public function __construct(?Component $Owner)
    {
        if($Owner instanceof Component) {
            $this->Owner = $Owner;
            $Owner->insertComponent($this);

            // for direct access to weblication!
            if($Owner instanceof Weblication) {
                $this->Weblication = $Owner;
                if($this->Weblication->Session instanceof Input) {
                    $this->Session = $this->Weblication->Session;
                }
            }
        }

        $this->name = $this->getUniqueName();
        return parent::__construct();
    }

    /**
     * Erzeugt einen eindeutigen Komponentennamen
     *
     * @return string Eindeutiger Name
     */
    protected function getUniqueName(): string
    {
        if($this->Owner != null) {
            $className = $this->getClassName();

            $counter = $this->Owner->uniqueNameCounter[$className] ?? 0;
            $counter++;
            $this->Owner->uniqueNameCounter[$className] = $counter;

            $new_name = $className . $counter;
        }
        else {
            $new_name = $this->getClassName();
        }

        return $new_name;
    }

    /**
     * Setzt den Namen der Komponente.
     *
     * Vor dem Setzen wird der Name noch validiert.
     * Existiert der Name bereits, wird false zurueck gegeben.
     *
     * @param string $new_name new name for component
     * @return Component
     */
    public function setName(string $new_name): Component
    {
        if($this->name != $new_name) {
            if($this->validateName($new_name)) {
                $this->name = $new_name;
            }
        }
        return $this;
    }

    /**
     * Liefert den Namen der Komponente zur�ck.
     *
     * @return string Name der Komponente
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string name of class using the trait
     */
    public static function theClass(): string
    {
        return static::class;
    }

    /**
     * @return string directory of the class
     * @throws ReflectionException
     */
    public function getClassDirectory(): string
    {
        if(!isset($this->classDirectory)) {
            $this->classDirectory = dirname((new ReflectionClass(self::theClass()))->getFileName());
        }
        return $this->classDirectory;
    }

    /**
     * Gibt den Eigentuemer der Komponente zur�ck.
     *
     * @return Component|null Eigent�mer-Instanz vom Typ Component oder null
     */
    public function getOwner(): ?Component
    {
        return $this->Owner;
    }

    /**
     * Gibt das Objekt Weblication zurueck, falls der Eigentuemer vom Typ Weblication ist.
     *
     * @return Weblication|null Weblication
     **@see Weblication
     */
    public function getWeblication(): ?Weblication
    {
        if($this->Owner instanceof Weblication) {
            return $this->Owner;
        }
        else {
            return null;
        }
    }

    /**
     * Ueberprueft, ob der Komponentennamen beim Eigentuemer bereits existiert.
     *
     * @param string $NewName Zu ueberpruefender Komponentenname
     * @return bool Erfolgsstatus
     */
    private function validateName(string $NewName): bool
    {
        if($this->Owner != null) {
            if($this->Owner->findComponent($NewName)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sucht in der eigenen Komponentenliste nach einer Komponente mit dem als Parameter uebergebenen Namen.
     *
     * @param string $name Search for a Component with name
     * @return Component|null Component
     */
    function findComponent(string $name): ?Component
    {
        foreach($this->components as $Component) {
            if(strcasecmp($Component->getName(), $name) == 0) {
                return $Component;
            }
        }
        return null;
    }

    /**
     * Ermittelt die Anzahl der Komponenten im Container.
     *
     * @return integer Anzahl der Komponenten
     */
    public function getComponentCount(): int
    {
        return count($this->components);
    }

    /**
     * Fuegt dem internen Container eine weitere Komponente hinzu.
     *
     * @param Component $Component Einzuf�gende Komponente
     */
    public function insertComponent(Component $Component)
    {
        $this->components[] = $Component;
    }

    /**
     * Entfernt eine Komponente aus dem internen Container.
     *
     * @param Component $Component Zu entfernende Komponente vom Typ Component
     */
    public function removeComponent(Component $Component)
    {
        $new_Components = [];

        // Rebuild Components
        for($i = 0; $i < count($this->components); $i++) {
            if($Component != $this->components[$i]) {
                $new_Components[] = $this->components[$i];
            }
        }

        $this->components = $new_Components;
    }
}