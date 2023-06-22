<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core;

use InputSession;
use ReflectionClass;
use ReflectionException;

/**
 * Core class for POOL components. Provides unique names for all components.
 * @package pool\classes\Core
 * @since 2003-07-10
 */
class Component extends PoolObject
{
    /**
     * Owner of this component
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
     * guarantees unique component names (continuously)
     *
     * @var array $uniqueNameCounter
     */
    private array $uniqueNameCounter = [];

    /**
     * Weblication
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
     * Can have an owner of type Component
     *
     * @param Component|null $Owner Owner of this component
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
     * creates an unique name for this component
     *
     * @return string
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
     * returns the name of the component
     *
     * @return string name of component
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
     * @see Weblication
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
     * checks a name for uniqueness
     *
     * @param string $NewName
     * @return bool true if name is not unique
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
     * returns the component with the given name
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
     * returns the number of components
     *
     * @return int number of components
     */
    public function getComponentCount(): int
    {
        return count($this->components);
    }

    /**
     * add component to internal container
     *
     * @param Component $Component Component to add
     */
    public function insertComponent(Component $Component)
    {
        $this->components[] = $Component;
    }

    /**
     * removes a component from the internal container
     *
     * @param Component $Component Component to remove
     */
    public function removeComponent(Component $Component)
    {
        $new_Components = [];

        // Rebuild Components
        for($i = 0; $i < count($this->components); $i++) {
            if($Component !== $this->components[$i]) {
                $new_Components[] = $this->components[$i];
            }
        }

        $this->components = $new_Components;
    }
}