	/* ====================== */
	/* === DropDown Layer === */
	/* ====================== */

	var pool_layer_onoff	= null;
	var pool_selLayer		= null;
	var pool_curLayer		= null;

	function pool_onLayer() {
		pool_layer_onoff=1;
	}

	function pool_offLayer() {
		pool_layer_onoff=0;
	}

	function openDropdownList(layer, atobject, offsetX, offsetY, width, height) {
		equalizeLayer(document.getElementById(layer));

		var borderWidth = parseInt(pool_layers[layer].style.borderWidth);
		// Defaults, falls nur layer uebergeben wird
		if (typeof (width) != "undefined") {
			width = atobject.offsetWidth-2*(borderWidth);
			if(pool_layers[layer].style.overflow == 'auto') width += 17; // Scrollbarbreite
			pool_layers[layer].style.width = width + 'px';
		}
		if (typeof (height) != "undefined") {
			pool_layers[layer].style.height = parseInt(height) + 'px';
		}
		if (typeof (offsetX) == "undefined") {
			offsetX = 0;
		}
		if (typeof (offsetY) == "undefined") {
			offsetY = 0;
		}

		if (typeof(atobject) == 'object') {
			posX = findPosX(atobject) + offsetX;
			posY = findPosY(atobject) + offsetY + atobject.offsetHeight;
		}
		else {
			posX = MousePosition.mousePosX;
			posY = MousePosition.mousePosY;
		}
		if (pool_curLayer) {
			pool_layers[pool_curLayer].setVisibility(0);
			pool_curLayer=null;
		}
		else {
			//alert(posX);
			//posX += borderWidth;
			pool_layers[layer].style.left=posX+'px';
			pool_layers[layer].style.top=posY+'px';
			pool_layers[layer].setVisibility(1);
			pool_curLayer=layer;
		}
	}

	function closeDropdownLayer(force) {
		if (pool_curLayer && pool_layer_onoff!=1) {
			openDropdownList(pool_curLayer);
		}
		if (force) {
			openDropdownList(pool_curLayer);
		}
	}

/*	function showLayer(layer) {
		if (pool_selLayer) {
			pool_layers[pool_selLayer].setVisibility(0);
		}
		pool_layers[layer].setVisibility(1);
		openDropdownList(pool_curLayer);
		pool_selLayer=layer;
	}*/