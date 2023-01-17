/**
 * -= selezione.js =-
 *
 * Based on GUI_Selezione
 *
 * $Log$
 *
 *
 * @version $Id: ajax.js,v 1.4 2004/09/23 14:08:02 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2009-07-15
 * @author Alexander Manhart <alexander.manhart@wochenblatt.de>
 * @link http://www.softidea.de
 */
	var Selezione = Class.create({
		/**
		 * Simple constructor
		 */
		initialize: function() {
			this.rows = new Array();
			this.SelectedRow = null;
			this.SelectedRowIndex = null;
			this.OldStyle = '';
			this.ignoreSelectedRow = false;
			this.init(); // call super::init
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
			return $(id+this.SelectedRowIndex);
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
            // @todo AM, 18.11.2022, removed REQUEST_PARAM_MODULENAME!!
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
			RequestPOOL(null, 'add', $H(Row).toJSON().evalJSON(), true, this._forwardSelectionList.bind(this));
		},
		remove: function(Sender, i) {
			this.await(this.rows[i]);
			this.SelectedRowIndex = i;
			var params = {'URL_SELEZIONE_KEY':i};
            // @todo AM, 18.11.2022, removed REQUEST_PARAM_MODULENAME!!
			params[REQUEST_PARAM_MODULENAME] = this.name;
			RequestPOOL(null, 'remove1', params, true, this._forwardSelectionList.bind(this));
		},
		_forwardSelectionList: function(Result) {
			this.renderSelectionList(unescape(Result['SelectionList']), Result['action'], Result['countSelectionList']);
		},
		renderSelectionList: function(content, action, count) {
			$(this.name+'.SelectionList').innerHTML = content;

			// Datenabfrage z.B.
			// alert(this.rows[this.SelectedRowIndex]['ausgabe']);
		},
		fetchList: function(modulename, customMethod, params) {
			this.await(null);
			params['componentName'] = modulename;
			params['customMethod'] = customMethod;
            // @todo AM, 18.11.2022, removed REQUEST_PARAM_MODULENAME!!
			params[REQUEST_PARAM_MODULENAME] = this.name;
			RequestPOOL(null, 'renderList', params, true, this._forwardList.bind(this));
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
			$(this.name+'.List').innerHTML = content;
		}
	});