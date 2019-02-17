/**
 * -= selezione.js =-
 *
 * Based on GUI_Selezione
 *
 * $Log$
 *
 *
 * @version $Id: jquery.selezione.js 21259 2012-03-28 09:39:10Z manhart $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2012-03-26
 * @author Alexander Manhart <alexander.manhart@wochenblatt.de>
 * @link http://www.softidea.de
 */

	Selezione = function() {
//		this.init(); // call super::init

	};
	jQuery.extend(Selezione.prototype,  {
//	var Selezione = Class.create({
		/**
		 * Simple constructor
		 */
		init: function() {
			this.rows = new Array();
			this.SelectedRow = null;
			this.SelectedRowIndex = null;
			this.OldStyle = '';
			this.ignoreSelectedRow = false;
			this.name = '';
			this.onCreate();
		},
		onCreate: function() {},
		addRow: function(PK, Row) {
			if(isEmpty(PK)) alert('PK is wrong');
			Row['ROW'] = document.getElementById('row'+PK); // store tr object to row
			Row['URL_SELEZIONE_KEY'] = PK;
			this.rows[PK] = Row;
		},
		getListElementById: function(id) {
			return jQuery('#'+id+this.SelectedRowIndex);
		},
		_mouseOut: function(Sender) {
			if(this.SelectedRow == Sender && !this.ignoreSelectedRow) return false;
			if(Sender != null) this.mouseOut(Sender);
		},
		mouseOut: function(Sender) {
		},
		_mouseOver: function(Sender) {
			if(this.SelectedRow == Sender && !this.ignoreSelectedRow) return false;
			if(Sender != null) this.mouseOver(Sender);
		},
		mouseOver: function(Sender) {
		},
		_rowClick: function(Sender, PK) {
			var SelectedRow = this.SelectedRow;

			if(this.SelectedRow != Sender) {
				this.SelectedRow = Sender;
				this.SelectedRowIndex = PK;
			}
			this._mouseOut(SelectedRow);

			var Row = this.rows[PK];
			var Buf = {};
			for(var key in Row) {
				Buf[key] = encodeURIComponent(Row[key]);
			}
			Buf[REQUEST_PARAM_MODULENAME] = this.name; // needed for ajax request
			Buf['PK'] = PK;
			this.add(Sender, Buf);
		},
		_rowClickSelection: function(Sender, i) {

		},
		await: function(Row) {
		},
		add: function(Sender, Row) {
			this.await(Row);
			RequestPOOL(null, 'add', Row, true, jQuery.proxy(this._forwardSelectionList, this));
		},
		remove: function(Sender, i) {
			this.await(this.rows[i]);
			this.SelectedRowIndex = i;
			var params = {'URL_SELEZIONE_KEY':i};
			params[REQUEST_PARAM_MODULENAME] = this.name;
			RequestPOOL(null, 'remove1', params, true, jQuery.proxy(this._forwardSelectionList, this));
		},
		_forwardSelectionList: function(Result) {
			this.renderSelectionList(unescape(Result['SelectionList']), Result['action'], Result['countSelectionList']);
		},
		renderSelectionList: function(content, action, count) {
			jQuery('#'+this.name+'SelectionList').html(content);

			// Datenabfrage z.B.
			// alert(this.rows[this.SelectedRowIndex]['ausgabe']);
		},
		fetchList: function(modulename, customMethod, params) {
			this.await(null);
			params['componentName'] = modulename;
			params['customMethod'] = customMethod;
			params[REQUEST_PARAM_MODULENAME] = this.name;
			RequestPOOL(null, 'renderList', params, true, jQuery.proxy(this._forwardList, this));
		},
		_forwardList: function(Result) {
			if(Result['ErrorMsg']) {
				alert(unescape(Result['ErrorMsg']));
				return;
			}
			var content = unescape(Result['List']);

			var js = unescape(Result['JsList']);
			eval(js);

			var count = Result['countList'];
			this.renderList(content, count);
		},
		renderList: function(content, count) {
			jQuery('#'+this.name+'List').html(content);
		}
	});