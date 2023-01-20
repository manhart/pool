pool_layers = new Object(); 
pool_layers.EQ = new Array();

function equalizeLayers(n) { 
	var k,l; 
	var list = (n==null) ? pool_layers.EQ : arguments; 
	for (k=0; k<list.length; k++) {
		l=getLayer(list[k]); 
		if(l) equalizeLayer(l); 
	} 
	if (n==null) pool_layers.EQ = new Array(); 
}

function getLayer(spec, base) { 
	if (!is.ns4) return getIt(spec); 
	var j=0, temp=null; 
	if (!base) {
		base=document; 
	}
	if (base.layers[spec]) {
		return base.layers[spec]; 
	}
	for (j=0; j<base.layers.length; j++) { 
		temp = getLayer(spec,base.layers[j].document); 
		if (temp) return temp; 
	} 
	return null; 
}

function getIt(id) { 
	if (is.ie) return document.all[id]; 
	if (is.ns6) return document.getElementById(id); 
}

function equalizeLayer(layer) { 
	if (pool_layers[layer.id]) return;
	
	buf_hidden = (is.ns4) ? 'hide' : 'hidden'; 
	buf_visible = (is.ns4) ? 'show' : 'visible';

	layer.getTop = new Function("return(parseInt(this.style.top))");
	layer.getLeft = new Function("return(parseInt(this.style.left))");
	layer.getHeight = new Function("if (is.ie) return this.scrollHeight; if (is.ns4) return this.document.height; if (is.ns6) return this.offsetHeight;");
	layer.setClip = new Function ("l","t","r","b","if (is.ns4) {this.clip.left=l; this.clip.top=t; this.clip.right=r; this.clip.bottom=b;} else { this.style.clip='rect('+t+' '+r+' '+b+' '+l+')'; }");
	layer.rewrite = new Function ("html","if (is.ie||is.ns6) this.innerHTML=html; if (is.ns4) {this.document.write(html); this.document.close();}");
	layer.setVisibility = new Function("n","this.style.visibility=(n) ? buf_visible : buf_hidden; if (is.ns6) {this.style.zIndex=(n)?this.z:eval(this.z)-1;}");

	pool_layers[layer.id] = layer; 
	
	if (is.ns4) {
		layer.style=layer; 
	}
	if (is.ns6) { 
		layer.z=layer.style.zIndex; 
		if(layer.style.visibility == buf_hidden) {
			layer.setVisibility(0); 
		}
	} 
}