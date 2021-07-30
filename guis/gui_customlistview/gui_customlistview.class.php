<?php
	/**
	 * -= PHP Object Oriented Library (POOL) =-
	 *
	 * $HeadURL: svn://develop1/intranet/lib/trunk/pool/guis/gui_customlistview/gui_customlistview.class.php $
	 *
	 * Graphical User Interface zur Listendarstellung.
	 *
	 * @version $Id: gui_customlistview.class.php 37657 2019-03-20 16:46:08Z manhart $
	 * @version $Revision: 37657 $
	 * @version $Author: manhart $
	 * @version $Date: 2019-03-20 17:46:08 +0100 (Mi, 20 Mrz 2019) $
	 *
	 * @since 2004-02-11
	 * @author alexander manhart <alexander.manhart@freenet.de>
	 * @link http://www.misterelsa.de
	 */

	/**
	 * Graphical User Interface zur Listendarstellung.
	 *
	 * @package pool
	 * @author manhart
	 * @version $Id: gui_customlistview.class.php 37657 2019-03-20 16:46:08Z manhart $
	 * @access public
	 **/
	class GUI_CustomListView extends GUI_Module
	{
		var $column='';
		var $sort='';

		/**
		 * Initialisiert Standardwerte.
		 *
		 * @access public
		 **/
		function init($superglobals=I_REQUEST)
		{
			$this -> Defaults -> addVar(
				array(
					'id' => 					$this -> getName(),
					'name' =>					$this -> getName(),

					#### Sortierung Optionen:
					'enable_sorting' =>			1,
					'use_own_sort_routine' =>	0,
					'sortfield' =>				null,
					'default_sortfield' =>		null,
					'suffix_sorted' =>			'',

					#### Anzuzeigende Liste im Format array(1 => array(['feld'] => 'value'))
					'list' =>					array()
				)
			);
			$this -> Defaults -> addVar('showheader', 1);
			$this -> Defaults -> addVar('colseparator', ';');
			$this -> Defaults -> addVar('rowseparator', '\n');
			$this -> Defaults -> addVar('columns', 'abc;efc');
			$this -> Defaults -> addVar('coltitles', 'Reiter 1; Reiter 2');
			$this -> Defaults -> addVar('colwidths', '');
			$this -> Defaults -> addVar('colalign', '');
			$this -> Defaults -> addVar('colshorten', '');
			$this -> Defaults -> addVar('numrows', 0);
			$this -> Defaults -> addVar('fromrow', 0);

			parent :: init($superglobals);
		}

		/**
		 * GUI_CustomListView::loadFiles()
		 *
		 * @return
		 **/
		function loadFiles()
		{
			$file = $this -> Weblication -> findTemplate('tpl_customlistview.html', 'gui_customlistview', true);
			$this -> Template -> setFilePath('stdout', $file);
		}

		/**
		 * GUI_CustomListView::prepare()
		 *
		 * @return
		 **/
		function prepare ()
		{
			$Template = & $this -> Template;
			$Session = & $this -> Session;
			$Input = & $this -> Input;

			$id = $Input -> getVar('id');
			$name = $Input -> getVar('name');

			// id mit name (sowie umgekehrt) abgleichen
			if ($name != $this -> getName() and $id == $this -> getName()) {
			    $id = $name;
			}
			if ($id != $this -> getName() and $name == $this -> getName()) {
			    $name = $id;
			}

			$sortfield = $Input -> getVar('sortfield');
			if (!$sortfield) {
				$sortfield = $Input -> getVar('default_sortfield');
			}

			$list = $Input->getVar('list');

			$columns = $Input->getVar('columns');
			$colseparator = $Input->getVar('colseparator');
			$ayColumns = explode($colseparator, $columns);

			$colwidths = $Input -> getVar('colwidths');
			$ayColwidths = explode($colseparator, $colwidths);

			$colalign = $Input -> getVar('colalign');
			$ayColalign = explode($colseparator, $colalign);

			$colshorten = $Input -> getVar('colshorten');
			$ayColshorten = explode($colseparator, $colshorten);

			// Header (Kopfzeile)
			if ($Input -> getVar('showheader') == 1) {
				$ayColtitles = explode($colseparator, $Input -> getVar('coltitles'));

				$Template -> newBlock('header');
				$i=0;
				foreach ($ayColtitles as $title) {
					$activeColumn = $ayColumns[$i];
					$Url_asc = new Url();
					$Url_asc -> setParam('sortfield', $ayColumns[$i]);
					$Url_asc -> setParam('order_' . $ayColumns[$i], 'ASC');
					if ($Input -> getVar('sortfield') == $ayColumns[$i]) {
					    if ($Input -> getVar('order_' . $ayColumns[$i]) == 'ASC') {
					    	$Url_asc -> setParam('order_' . $ayColumns[$i], 'DESC');
					    }
					}

					$Template -> newBlock('hcol');
					if ($title) {
						if ($Input -> getVar('enable_sorting') == 1) {
							if ($activeColumn == $sortfield and count($list)) {
							    $var_title = $title . $Input -> getVar('suffix_sorted');
							}
							else {
								$var_title = $title;
							}
							$href = $Url_asc -> getUrl();
						}
						else {
							$var_title = $title;
							$href = '';
						}
					}
					else {
						$var_title = '&nbsp;';
					}
					$Template -> setVar(
						array(
							'title' => $var_title,
							'href' => $href
						)
					);
					if ($ayColwidths[$i]) {
						$Template -> setVar('width', $ayColwidths[$i]);
					}
					unset($Url);
					$i++;
				}
			}


			if ($Input -> getVar('use_own_sort_routine') == 1 and is_array($list)) {
				$this -> column = $Input -> getVar('sortfield');
				if (empty($this -> column)) {
					$this -> column = $Input -> getVar('default_sortfield');
				}
				$this -> order = $Input -> getVar('order_'.$this->column);
				usort($list, array($this, 'sort'));
				$Input -> setVar('list', $list);
			}
			$GUI_Shorten = new GUI_Shorten($this->getOwner());
			if (is_array($list) and count($list) > 0) {
				for ($r = $Input -> getVar('fromrow'); $r < count($list); $r++) {
					$Template->newBlock('row');
					$Template->setVar('colspan', count($ayColumns));
					$record = $list[$r];

					$this->doRow($record);
					$Template->useBlock('row');

					$this->beforeCol($record);
					#echo pray($record);
					for ($c = 0; $c < count($ayColumns); $c++) {
						$Template -> newBlock('col');
						$shorten = ($ayColshorten[$c] > 0) ? $ayColshorten[$c] : false;
						$value = $record[$ayColumns[$c]];
						if($shorten) {
					//		$value = shorten($value, $shorten);
							$GUI_Shorten -> Input -> setVar(
								array(
									'text' => $value,
									'len' => $shorten,
									'hint' => 1,
									'url' => $record['shortenUrl']
								)
							);
							$GUI_Shorten -> prepare();
							$value = $GUI_Shorten -> finalize();
						}
						$Template->setVar('value', $value);
						if ($ayColwidths[$c]) {
						    $Template -> setVar('width', $ayColwidths[$c]);
						}
						else {
							$Template -> setVar('width', '');
						}
						$align = ($ayColalign[$c] != '') ? $ayColalign[$c] : 'left';
						$Template -> setVar('align', $align);
						$this -> doCol($record, $ayColumns[$c]);
					}
					$this->afterCol();

					if ($Input -> getVar('numrows') > 0) {
						if ($r >= $Input -> getVar('numrows') + $Input -> getVar('fromrow') - 1) {
						    break;
						}
					}
				}
			}
		}

		function sort($a, $b)
		{
			if ($this -> order == 'DESC') {
				return (strnatcasecmp($a[$this -> column], $b[$this -> column]) * -1);
			}
			else {
				return strnatcasecmp($a[$this -> column], $b[$this -> column]);
				//return ($a[$this -> column] > $b[$this -> column] ? 1 : -1);
			}
		}

		/**
		 * GUI_CustomListView::doRow()
		 *
		 * Sobald eine Zeile geschrieben wird, wird doRow aufgerufen (virtuelle Methode).
		 *
		 * @abstract
		 * @param array $record Ein Datensatz
		 * @access public
		 **/
		function doRow($record)
		{
			$this -> Template -> setVar('onclick', $this -> Input -> getVar('onclick'));
		}

		function doCol($record, $column)
		{
		}

		function beforeCol($record)
		{
		}

		function afterCol()
		{
		}

		/**
		 * GUI_CustomListView::finalize()
		 *
		 * Parst Template und gibt Ergebnis zurueck.
		 *
		 * @access public
		 * @param string $tpl_handle Zu parsendes Template Handle
		 * @return
		 **/
		function finalize($tpl_handle='stdout')
		{
			$this -> Template -> parse($tpl_handle);
			return $this -> Template -> getContent($tpl_handle);
		}
	}
?>