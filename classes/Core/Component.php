<?php declare(strict_types = 1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core;

use pool\classes\Core\Input\Input;
use pool\classes\Core\Input\Session;
use ReflectionClass;
use ReflectionException;

/**
 * Core class for POOL components. Provides unique names for all components.
 *
 * @package pool\classes\Core
 * @since 2003-07-10
 */
class Component extends PoolObject
{
    /**
     * Weblication
     *
     * @var Weblication|null $Weblication
     */
    public ?Weblication $Weblication = null;

    /**
     * Session
     *
     * @var Session|null $Session
     */
    public ?Session $Session = null;

    /**
     * Owner of this component
     *
     * @var Component|null $Owner
     */
    protected ?Component $Owner;

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
        $this->Owner = $Owner;
        if($Owner) {
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
     * Add component to internal container
     *
     * @param Component $Component Component to add
     */
    protected function insertComponent(Component $Component): static
    {
        $this->components[] = $Component;
        return $this;
    }

    /**
     * Creates an unique name for this component
     *
     * @return string
     */
    protected function getUniqueName(): string
    {
        $className = $this->getClassName();

        if($this->Owner) {
            $counter = ($this->Owner->uniqueNameCounter[$className] ?? 0) + 1;
            $this->Owner->uniqueNameCounter[$className] = $counter;
            $className = "$className$counter";
        }

        return $className;
    }

    /**
     * Returns the name of the component
     *
     * @return string name of component
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Specifies a unique name for the component. The name can only consist of alphanumeric characters (A-Z, a-z, 0-9), underscores (_), points (.), and
     * hyphens (-). The name must start with a letter. The name cannot contain spaces. The name is case-sensitive. The name is validated for uniqueness.
     * If the name is not unique, the component is not renamed.
     *
     * @param string $new_name new name for component
     * @return Component
     */
    public function setName(string $new_name): static
    {
        if($this->name != $new_name) {
            if($this->validateName($new_name)) {
                $this->name = $new_name;
            }
        }
        return $this;
    }

    /**
     * Checks a name for uniqueness
     *
     * @param string $NewName
     * @return bool true if name is not unique
     */
    private function validateName(string $NewName): bool
    {
        return !$this->Owner || !$this->Owner->findComponent($NewName);
    }

    /**
     * Returns the component with the given name
     *
     * @param string $name Search for a Component with name
     * @return Component|null Component
     */
    public function findComponent(string $name): ?Component
    {
        foreach($this->components as $Component) {
            if(strcasecmp($Component->getName(), $name) === 0) {
                return $Component;
            }
        }
        return null;
    }

    /**
     * Returns the directory of the class
     *
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
     * Returns the fully qualified class name
     *
     * @return string fully qualified class name
     */
    public static function theClass(): string
    {
        return static::class;
    }

    /**
     * Returns the Owner of this component
     *
     * @return Component Owner of this component
     */
    public function getOwner(): Component
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
     * Returns the number of components
     *
     * @return int number of components
     */
    public function getComponentCount(): int
    {
        return count($this->components);
    }

    /**
     * Removes a component from the internal container
     *
     * @param Component $Component Component to remove
     */
    public function removeComponent(Component $Component): static
    {
        $this->components = array_diff($this->components, [$Component]);
        return $this;
    }
}