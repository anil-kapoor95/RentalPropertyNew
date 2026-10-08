/* Theme colour picker (vanilla JS — no jQuery dependency, safe under noConflict). */
(function () {
	"use strict";
	var root = document.getElementById('pjThemeColors');
	if (!root) { return; }

	var KEYS = ['brand', 'accent', 'text', 'bg', 'price', 'pagebg'];
	var state = { brand:'#b45309', accent:'#f3f4f6', text:'#111827', bg:'#ffffff', price:'#b45309', pagebg:'#ffffff', font:'', palette:'0' };

	/* ---- colour maths (mirrors pjColorHelper) ---- */
	function clamp(v){ return Math.max(0, Math.min(255, Math.round(v))); }
	function toRgb(hex){ hex = (hex||'').replace('#',''); if (hex.length===3){hex=hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];} return {r:parseInt(hex.substr(0,2),16),g:parseInt(hex.substr(2,2),16),b:parseInt(hex.substr(4,2),16)}; }
	function toHex(r,g,b){ return '#'+[r,g,b].map(function(x){return ('0'+clamp(x).toString(16)).slice(-2);}).join(''); }
	function darken(hex,p){ var c=toRgb(hex),f=1-p/100; return toHex(c.r*f,c.g*f,c.b*f); }
	function lighten(hex,p){ var c=toRgb(hex),f=p/100; return toHex(c.r+(255-c.r)*f,c.g+(255-c.g)*f,c.b+(255-c.b)*f); }
	function mixWhite(hex,p){ return lighten(hex,p); }
	function isDark(hex){ var c=toRgb(hex); return (0.299*c.r+0.587*c.g+0.114*c.b)/255 < 0.62; }
	function contrast(hex){ return isDark(hex) ? '#ffffff' : '#000000'; }
	function isValidHex(hex){ return /^#[0-9a-fA-F]{6}$/.test(hex||''); }
	function rgba(hex,a){ var c=toRgb(hex); return 'rgba('+c.r+', '+c.g+', '+c.b+', '+a+')'; }
	function isLightAccent(hex){ var c=toRgb(hex); return (0.299*c.r+0.587*c.g+0.114*c.b)/255 > 0.82; }
	function randHex(){ return '#'+Math.floor(Math.random()*16777215).toString(16).padStart(6,'0'); }
	function randHue(){ var h=Math.floor(Math.random()*360), s=45+Math.floor(Math.random()*35), l=32+Math.floor(Math.random()*20); return hslToHex(h,s,l); }
	function hslToHex(h,s,l){ s/=100;l/=100; var k=function(n){return (n+h/30)%12;}, a=s*Math.min(l,1-l);
		var f=function(n){var col=l-a*Math.max(-1,Math.min(k(n)-3,Math.min(9-k(n),1)));return clamp(255*col);};
		return toHex(f(0),f(8),f(4)); }

	/* ---- build the :root variable block (mirrors pjColorHelper) ---- */
	function cssVars(){
		var b=state.brand,a=state.accent,t=state.text,bg=state.bg,pr=state.price,pg=state.pagebg;
		var actionAccent=isLightAccent(a)?b:a;
		var subtle=(bg==='#ffffff'||bg==='#fff')?mixWhite(b,97):lighten(bg,3);
		var css=':root{';
		css+='--brandColor:'+b+';--brandColorDark:'+darken(b,18)+';--brandColorLight:'+lighten(b,35)+';--brandColorUltraLight:'+mixWhite(b,92)+';--brandContrast:'+contrast(b)+';';
		css+='--accentColor:'+a+';--accentColorDark:'+darken(a,15)+';--accentColorLight:'+lighten(a,30)+';--actionAccent:'+actionAccent+';--actionAccentDark:'+darken(actionAccent,15)+';';
		css+='--textPrimary:'+t+';--textSecondary:'+lighten(t,25)+';--textMuted:'+lighten(t,45)+';';
		css+='--surfaceBase:'+bg+';--surfaceElevated:'+bg+';--surfaceSubtle:'+subtle+';';
		css+='--borderColor:'+mixWhite(b,85)+';--borderColorHover:'+mixWhite(b,75)+';';
		css+='--priceColor:'+pr+';--pageBackground:'+pg+';--panelTint:'+pg+';';
		css+='--shadowSm:0 1px 3px '+rgba(b,0.08)+';--shadowMd:0 4px 12px '+rgba(b,0.10)+';--shadowLg:0 8px 24px '+rgba(b,0.14)+';--shadowHover:0 8px 30px '+rgba(b,0.18)+';';
		if(state.font){ css+="--themeFontFamily:'"+state.font+"', system-ui, sans-serif;"; }
		css+='}';
		if(state.font){ css="@import url('https://fonts.googleapis.com/css2?family="+state.font.replace(/ /g,'+')+":wght@400;500;600;700&display=swap');\n"+css; }
		return css;
	}

	/* ---- inject the live theme into the preview iframe ---- */
	function applyPreview(){
		var frame=document.getElementById('pjtcFrame');
		if(!frame){ return; }
		try{
			var doc=frame.contentDocument||(frame.contentWindow&&frame.contentWindow.document);
			if(!doc||!doc.documentElement){ return; }
			var css=cssVars();
			if(doc.head){
				var style=doc.getElementById('pjThemeLive');
				if(!style){ style=doc.createElement('style'); style.id='pjThemeLive'; }
				doc.head.appendChild(style); /* keep it last so it wins the cascade */
				style.textContent=css;
			}
			/* also set each var inline on <html> — inline beats any :root stylesheet rule */
			var m=css.match(/:root\{([^}]*)\}/);
			if(m){
				m[1].split(';').forEach(function(decl){
					var i=decl.indexOf(':');
					if(i>0){
						var name=decl.slice(0,i).trim(), val=decl.slice(i+1).trim();
						if(name.indexOf('--')===0){ doc.documentElement.style.setProperty(name, val); }
					}
				});
			}
		}catch(e){ /* cross-origin or not ready yet */ }
	}

	/* ---- sync inputs <-> state ---- */
	function setColor(key, hex, fromPalette){
		if (!isValidHex(hex)) { return; }
		hex = hex.toLowerCase();
		state[key] = hex;
		var picker = root.querySelector('input[data-role="picker"][data-key="'+key+'"]');
		var hexIn  = root.querySelector('input[data-role="hex"][data-key="'+key+'"]');
		if (picker) { picker.value = hex; }
		if (hexIn && document.activeElement !== hexIn) { hexIn.value = hex; }
		if (!fromPalette) { clearSelectedPalette(); }
		applyPreview();
	}
	function clearSelectedPalette(){
		var sel = root.querySelector('.pjpal.sel');
		if (sel) { sel.classList.remove('sel'); }
		state.palette = '';
		document.getElementById('pjtcPalette').value = '';
	}

	/* ---- wire colour inputs ---- */
	root.querySelectorAll('input[data-role="picker"]').forEach(function(inp){
		inp.addEventListener('input', function(){ setColor(inp.getAttribute('data-key'), inp.value, false); });
	});
	root.querySelectorAll('input[data-role="hex"]').forEach(function(inp){
		inp.addEventListener('input', function(){ var v=inp.value.trim(); if(v[0]!=='#'){v='#'+v;} if(isValidHex(v)){ setColor(inp.getAttribute('data-key'), v, false); } });
	});
	var fontIn = document.getElementById('pjtcFont');
	if (fontIn){ fontIn.addEventListener('input', function(){ state.font = fontIn.value.replace(/[^a-zA-Z0-9 ]/g,''); applyPreview(); }); }

	/* ---- palettes ---- */
	root.querySelectorAll('.pjpal').forEach(function(card){
		card.addEventListener('click', function(){
			root.querySelectorAll('.pjpal').forEach(function(c){ c.classList.remove('sel'); });
			card.classList.add('sel');
			state.palette = card.getAttribute('data-id');
			document.getElementById('pjtcPalette').value = state.palette;
			setColor('brand',  card.getAttribute('data-brand'),  true);
			setColor('accent', card.getAttribute('data-accent'), true);
			setColor('text',   card.getAttribute('data-text'),   true);
			setColor('bg',     card.getAttribute('data-bg'),     true);
			setColor('price',  card.getAttribute('data-price'),  true);
			setColor('pagebg', card.getAttribute('data-pagebg'), true);
			applyPreview();
		});
	});

	/* ---- surprise me ---- */
	document.getElementById('pjtcRandom').addEventListener('click', function(){
		clearSelectedPalette();
		var brand = randHue();
		setColor('brand', brand, false);
		setColor('accent', randHue(), false);
		setColor('text', '#1a1a2e', false);
		setColor('bg', '#ffffff', false);
		setColor('price', brand, false);
		setColor('pagebg', lighten(brand, 92), false);
		if (document.getElementById('pjtcIncludeFont').checked && fontIn){
			var fonts=['Inter','Poppins','Roboto','Nunito','Rubik','Work Sans','Manrope','Lato'];
			fontIn.value = fonts[Math.floor(Math.random()*fonts.length)];
			state.font = fontIn.value;
		}
		applyPreview();
	});

	/* ---- init from current values ---- */
	KEYS.forEach(function(k){ var inp=root.querySelector('input[data-role="hex"][data-key="'+k+'"]'); if(inp&&isValidHex(inp.value)){ state[k]=inp.value.toLowerCase(); } });
	if (fontIn){ state.font = fontIn.value.replace(/[^a-zA-Z0-9 ]/g,''); }
	state.palette = document.getElementById('pjtcPalette').value || '';
	var _frame = document.getElementById('pjtcFrame');
	if (_frame) { _frame.addEventListener('load', applyPreview);
	var _viewSel=document.getElementById('pjtcView');
	if(_viewSel){ _viewSel.addEventListener('change', function(){ _frame.src=_viewSel.value; }); } }
	var _reload = document.getElementById('pjtcReload');
	if (_reload && _frame) { _reload.addEventListener('click', function(){ _frame.contentWindow.location.reload(); }); }
	applyPreview();

	/* ---- save ---- */
	document.getElementById('pjtcSave').addEventListener('click', function(){
		var btn=this; btn.disabled=true;
		var data = new URLSearchParams();
		data.append('o_brand_color', state.brand);
		data.append('o_accent_color', state.accent);
		data.append('o_text_color', state.text);
		data.append('o_background_color', state.bg);
		data.append('o_price_color', state.price);
		data.append('o_page_background_color', state.pagebg);
		data.append('o_font_family', state.font || '');
		data.append('o_color_palette', state.palette || '');
		data.append('csrf_token', root.getAttribute('data-csrf') || '');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', root.getAttribute('data-save-url'), true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.setRequestHeader('X-CSRF-Token', root.getAttribute('data-csrf') || '');
		xhr.onreadystatechange = function(){
			if (xhr.readyState === 4){
				btn.disabled=false;
				var ok=false;
				try { ok = (JSON.parse(xhr.responseText).status === 'OK'); } catch(e){}
				if (ok){
					var s=document.getElementById('pjtcSaved'); s.classList.add('on');
					setTimeout(function(){ s.classList.remove('on'); }, 2000);
				} else {
					alert('Could not save the colours. Please try again.');
				}
			}
		};
		xhr.send(data.toString());
	});
})();
