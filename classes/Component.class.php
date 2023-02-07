<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     *
     * @var InputSession|null $Session
     */
    public ?InputSession $Session = null;

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