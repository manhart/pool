<?php
	/**
	 * -= Rapid Module Library (RML) =-
	 *
	 * classes.lib.php
	 *
	 * Include File f�r alle Graphical User Interfaces
	 *
	 * @version $Id: guis.lib.php,v 1.7 2006/12/01 11:08:45 manhart Exp $
	 * @since 2003-07-10
	 * @author Alexander Manhart <alexander.manhart@freenet.de>
	 * @link http://www.misterelsa.de
	 */

	if (!defined('PWD_TILL_GUIS')) {
	    define('PWD_TILL_GUIS', '.');
	}

	// derived from GUI_Module
	require_once(PWD_TILL_GUIS . '/gui_headerdata/gui_headerdata.class.php');
	require_once(PWD_TILL_GUIS . '/gui_customframe/gui_customframe.class.php');
	require_once(PWD_TILL_GUIS . '/gui_schema/gui_schema.class.php');
	//require_once(PWD_TILL_GUIS . '/gui_box/gui_box.class.php');
	require_once(PWD_TILL_GUIS . '/gui_marquee/gui_marquee.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dynclock/gui_dynclock.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dyntooltip/gui_dyntooltip.class.php');
	require_once(PWD_TILL_GUIS . '/gui_displayimage/gui_displayimage.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dhtmlhint/gui_dhtmlhint.class.php');
	require_once(PWD_TILL_GUIS . '/gui_shadowimage/gui_shadowimage.class.php');
	require_once(PWD_TILL_GUIS . '/gui_protectemail/gui_protectemail.class.php');

	// Controls
	require_once(PWD_TILL_GUIS . '/gui_label/gui_label.class.php');
	require_once(PWD_TILL_GUIS . '/gui_edit/gui_edit.class.php');
	require_once(PWD_TILL_GUIS . '/gui_textarea/gui_textarea.class.php');
	require_once(PWD_TILL_GUIS . '/gui_checkbox/gui_checkbox.class.php');
	require_once(PWD_TILL_GUIS . '/gui_radiobutton/gui_radiobutton.class.php');
	require_once(PWD_TILL_GUIS . '/gui_select/gui_select.class.php');
	require_once(PWD_TILL_GUIS . '/gui_splitter/gui_splitter.class.php');
	require_once(PWD_TILL_GUIS . '/gui_scrollbox/gui_scrollbox.class.php');
	require_once(PWD_TILL_GUIS . '/gui_calendar/gui_calendar.class.php');
	require_once(PWD_TILL_GUIS . '/gui_shorten/gui_shorten.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dhtmlcalendar/gui_dhtmlcalendar.class.php');
	require_once(PWD_TILL_GUIS . '/gui_pagecontrol/gui_pagecontrol.class.php');
	require_once(PWD_TILL_GUIS . '/gui_customlistview/gui_customlistview.class.php');
	require_once(PWD_TILL_GUIS . '/gui_url/gui_url.class.php');
	require_once(PWD_TILL_GUIS . '/gui_displaynumbers/gui_displaynumbers.class.php');
	require_once(PWD_TILL_GUIS . '/gui_choosy/gui_choosy.class.php');
	require_once(PWD_TILL_GUIS . '/gui_comments/gui_comments.class.php');
	require_once(PWD_TILL_GUIS . '/gui_emoticons/gui_emoticons.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dhtmldropdown/gui_dhtmldropdown.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dhtmllistbox/gui_dhtmllistbox.class.php');
	require_once(PWD_TILL_GUIS . '/gui_daybar/gui_daybar.class.php');
	require_once(PWD_TILL_GUIS . '/gui_selezione/gui_selezione.class.php');
	// require_once(PWD_TILL_GUIS . '/gui_selectionlist/gui_selectionlist.class.php');

	// DB Controls (using DAO Object Model)
	require_once(PWD_TILL_GUIS . '/gui_label/gui_dblabel.class.php');
	require_once(PWD_TILL_GUIS . '/gui_edit/gui_dbedit.class.php');
	require_once(PWD_TILL_GUIS . '/gui_button/gui_button.class.php');
	require_once(PWD_TILL_GUIS . '/gui_textarea/gui_dbtextarea.class.php');
	require_once(PWD_TILL_GUIS . '/gui_select/gui_dbselect.class.php');
	require_once(PWD_TILL_GUIS . '/gui_select/gui_dblookupselect.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dbcount/gui_dbcount.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dhtmldropdown/gui_dbdhtmldropdown.class.php');
	require_once(PWD_TILL_GUIS . '/gui_dbgrid/gui_dbgrid.class.php');

	// Session Controls
	require_once(PWD_TILL_GUIS . '/gui_label/gui_sesslabel.class.php');
	require_once(PWD_TILL_GUIS . '/gui_label/gui_dblookuplabel.class.php');
	require_once(PWD_TILL_GUIS . '/gui_label/gui_formatdatelabel.class.php');
	require_once(PWD_TILL_GUIS . '/gui_label/gui_now.class.php');
	require_once(PWD_TILL_GUIS . '/gui_record/gui_record.class.php');

	// DHtmlX
	require_once(PWD_TILL_GUIS.'/gui_dhtmlxgridexport/gui_dhtmlxgridexport.class.php');
?>