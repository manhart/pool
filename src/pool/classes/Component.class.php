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
	* @author Alexander Manhart <alexander.manhart@freenet.de>
	* @link http://www.misterelsa.de
	* @package pool
	*/

	if(!defined('CLASS_COMPONENT')) {
		/**
		 * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
		 * @ignore
		 */
		define('CLASS_COMPONENT',			1);

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
		 * @author Alexander Manhart <alexander.manhart@freenet.de>
		 * @access public
		 * @package pool
		 **/
		class Component extends PoolObject
		{
			/**
			 * Eigentuemer vom Typ Component
			 *
	 		 * @var Component $Owner
			 * @access public
			 */
			var $Owner=null;

			/**
			 * Array bestehend aus Objekten vom Typ Component
			 *
			 * @var array $Components
			 * @access public
			 */
			var $Components=Array();

			/**
			 * Eindeutiger Komponentenname
			 *
			 * @var string $Name
			 * @access private
			 */
			var $Name='';

			/**
			 * Array als Zaehler, gewaehrleistet eindeutige Komponentennamen (fortlaufend)
			 *
			 * @var array $Counter
			 * @access private
			 */
			var $Counter=array();

			/**
			 * Webanwendung
			 *
			 * @var Weblication $Weblication
			 * @access public
			 */
			var $Weblication=null;

			/**
			 * Session
			 *
			 * @var ISession $Session
			 * @access public
			 */
			var $Session=null;

			/**
			 * Der Konstruktor erhaelt als Parameter den Eigentuemer dieses Objekts. Der Eigent�mer muss vom Typ Component abgeleitet sein.
			 *
			 * @access public
			 * @param Component $Owner Der Eigentuemer erwartet ein Objekt vom Typ Component. Es kann auch unser NULL Objekt Nil verwendet werden, falls kein Eigent�mer feststeht.
			 */
			function __construct(&$Owner)
			{
				if (@is_a($Owner, 'component')) {
					$this->Owner = &$Owner;
					$Owner->insertComponent($this);

					// for direct access to weblication!
					if (is_a($Owner, 'weblication')) {
					    $this->Weblication = &$Owner;
						if (is_a($this->Weblication->Session, 'input')) {
							$this->Session = & $this->Weblication->Session;
						}
					}
	            }

				$this->Name = $this->getUniqueName();
			}

			/**
			 * Erzeugt eine neue Klasse des selben Typs (wir ueberschreiben Object::createNew).
			 *
			 * @access public
			 * @param Component $Owner Objekt vom Typ Component
			 * @return Component Neue Klasse
			 * @see PoolObject::createNew()
			 */
			function &createNew()
			{
				$ClassName = $this->getClassName();
				$new_obj = new $ClassName($this->Owner);
				return $new_obj;
			}

			/**
			 * Erzeugt einen eindeutigen Komponentennamen
			 *
			 * @access public
			 * @return string Eindeutiger Name
			 */
			function getUniqueName()
			{
				if ($this -> Owner != null) {
					$ClassName = $this -> getClassName();
					if (isset($this -> Owner -> Counter[$ClassName])) {
					    $sufix = ($this -> Owner -> Counter[$ClassName] + 1);
					}
					else {
						$sufix = 1;
					}
					$new_name = $ClassName . $sufix;
					$this -> Owner -> Counter[$ClassName] = $sufix;
				}
				else {
					$new_name = $this -> getClassName();
				}

				return $new_name;
			}

			/**
			 * Setzt den Namen der Komponente.
			 *
			 * Vor dem Setzen wird der Name noch validiert.
			 * Existiert der Name bereits, wird false zurueck gegeben.
			 *
			 * @access public
			 * @param string $new_name Neuer Name f�r die Komponente
			 * @return bool Erfolgsstatus
			 */
			function setName($new_name)
			{
				if ($this->validateName($new_name)) {
				    $this->Name = $new_name;
					return true;
				}
				return false;
			}

			/**
			 * Liefert den Namen der Komponente zur�ck.
			 *
			 * @access public
			 * @return string Name der Komponente
			 */
			function getName()
			{
				return $this->Name;
			}

			/**
			 * Gibt den Eigentuemer der Komponente zur�ck.
			 *
			 * @access public
			 * @return Component|null Eigent�mer-Instanz vom Typ Component oder null
			 */
			function &getOwner()
			{
				return $this->Owner;
			}

			/**
			 * Gibt das Objekt Weblication zurueck, falls der Eigentuemer vom Typ Weblication ist.
			 *
			 * @see Weblication
			 * @return Weblication|null Weblication
			 **/
			function & getWeblication()
			{
				if (is_a($this -> Owner, 'weblication')){
					return $this -> Owner;
				}
				else {
					return null;
				}
			}


			/**
			 * Ueberprueft, ob der Komponentennamen beim Eigentuemer bereits existiert.
			 *
			 * @access public
			 * @param string $NewName Zu ueberpruefender Komponentenname
			 * @return bool Erfolgsstatus
			 */
			function validateName($NewName)
			{
				if ($this -> Owner != null){
					if($this -> Owner -> findComponent($NewName)) {
						return false;
					}
				}
				return true;
			}

			/**
			 * Sucht in der eigenen Komponentenliste nach einer Komponente mit dem als Parameter uebergebenen Namen.
			 *
			 * @access public
			 * @param string $varName Zu suchender Name
			 * @return Component|null Komponente
			 */
			function &findComponent($varName)
			{
				$result = false;
				if ($varName != ''){
					for ($i = 0; $i < count($this->Components); $i++){
						if (strcasecmp($this->Components[$i]->Name, $varName) == 0) {
							$result = &$this->Components[$i];
							break;
						}
					}
				}
	         	return $result;
			}

			/**
			 * Ermittelt die Anzahl der Komponenten im Container.
			 *
			 * @access public
			 * @return integer Anzahl der Komponenten
			 */
			function getComponentCount()
			{
			    return count($this -> Components);
			}

			/**
			 * Fuegt dem internen Container eine weitere Komponente hinzu.
			 *
			 * @access public
			 * @param Component Einzuf�gende Komponente
			 */
			function insertComponent(&$Component)
			{
				$this->Components[] = &$Component;
			}

			/**
			 * Entfernt eine Komponente aus dem internen Container.
			 *
			 * @access public
			 * @param Component $Component Zu entfernende Komponente vom Typ Component
			 */
			function removeComponent(& $Component)
			{
				$new_Components = Array();

				// Rebuild Components
				for ($i = 0; $i < count($this -> Components); $i++) {
					if ($Component != $this -> Components[$i]) {
					    $new_Components[] = &$this->Components[$i];
					}
				}

				$this -> Components = $new_Components;
			}

			/**
			 * Leert Komponentencontainer.
			 *
			 * @access public
			 */
			function clear()
			{
				unset($this -> Components);
				$this -> Components = Array();
			}

			function destroy()
			{
				parent::destroy();

				unset($this -> Components);
				unset($this -> Weblication);
				unset($this -> Counter);
				unset($this -> Name);
			}
		}
	}
?>