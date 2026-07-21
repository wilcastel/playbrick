<?php
defined('ABSPATH') || exit;

add_action('wp_footer', 'playbrick_builder_panel_output', 100);

function playbrick_is_bricks_builder_request() {
	return ! is_admin()
		&& isset($_GET['bricks'])
		&& $_GET['bricks'] === 'run'
		&& current_user_can('manage_options');
}

function playbrick_builder_panel_output() {
	if (!playbrick_is_bricks_builder_request()) return;
	?>
	<style id="playbrick-builder-panel-styles">
		#playbrick-css-panel{position:fixed;left:320px;right:320px;bottom:0;height:300px;min-height:180px;max-height:80vh;z-index:1000;background:#151b22;border-top:1px solid rgba(255,255,255,.12);box-shadow:0 -16px 40px rgba(0,0,0,.35);color:#d6deeb;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;display:flex;flex-direction:column}
		#playbrick-css-panel.playbrick-hidden{display:none!important}
		#playbrick-css-panel *{box-sizing:border-box}
		#playbrick-css-resize{position:absolute;left:0;right:0;top:-5px;height:10px;cursor:ns-resize;z-index:1}
		#playbrick-css-resize:after{content:"";position:absolute;left:50%;top:3px;width:44px;height:3px;margin-left:-22px;border-radius:999px;background:rgba(255,255,255,.22)}
		#playbrick-css-topbar{height:34px;display:flex;align-items:center;gap:10px;padding:0 10px;border-bottom:1px solid rgba(255,255,255,.08);background:#111820;font-size:12px}
		#playbrick-css-title{font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
		#playbrick-css-status{margin-left:auto;color:#8a99a8;font-size:11px}
		#playbrick-css-meta{display:flex;gap:6px;align-items:center;color:#8a99a8;font-size:11px}
		.playbrick-css-pill{display:inline-flex;align-items:center;height:20px;padding:0 7px;border:1px solid rgba(255,255,255,.12);border-radius:999px;background:rgba(255,255,255,.04);color:#9fb0c2;font-size:10px;font-weight:700;letter-spacing:.02em}
		.playbrick-css-pill.is-warn{border-color:rgba(255,193,7,.28);color:#ffd479;background:rgba(255,193,7,.08)}
		.playbrick-css-btn{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#d6deeb;border-radius:4px;font-size:11px;height:24px;padding:0 8px;cursor:pointer}
		.playbrick-css-btn.is-primary{border-color:rgba(51,153,255,.45);background:rgba(51,153,255,.16);color:#b9dcff}
		.playbrick-css-btn:hover{background:rgba(255,255,255,.1);color:#fff}
		#playbrick-css-body{display:grid;grid-template-columns:1fr 1fr;min-height:0;flex:1}
		.playbrick-css-col{display:flex;flex-direction:column;min-width:0;min-height:0;border-right:1px solid rgba(255,255,255,.08)}
		.playbrick-css-col:last-child{border-right:0}
		.playbrick-css-label{height:28px;display:flex;align-items:center;padding:0 10px;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#95a3b3;background:#1c242e;border-bottom:1px solid rgba(255,255,255,.08)}
		#playbrick-generated-css,#playbrick-custom-css{flex:1;width:100%;min-height:0;margin:0;padding:12px;border:0;outline:0;background:#0f141a;color:#d6deeb;font:12px/1.55 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;tab-size:2;white-space:pre;overflow:auto}
		#playbrick-generated-css{color:#8fb7ff;background:#101720;user-select:text}
		#playbrick-custom-css{resize:none;color:#d6deeb}
		#playbrick-unsupported{display:none;max-height:54px;overflow:auto;padding:7px 10px;border-top:1px solid rgba(255,255,255,.08);background:#171f29;color:#ffd479;font:11px/1.4 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
		#playbrick-unsupported strong{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#f8d78b;margin-right:6px}
		#playbrick-css-empty{position:absolute;inset:34px 0 0;display:none;align-items:center;justify-content:center;background:#151b22;color:#95a3b3;font-size:13px;text-align:center;padding:24px}
		#playbrick-css-panel.playbrick-no-class #playbrick-css-empty{display:flex}
		#playbrick-css-panel.playbrick-no-class #playbrick-css-body{visibility:hidden}
	</style>
	<div id="playbrick-css-panel" class="playbrick-hidden playbrick-no-class">
		<div id="playbrick-css-topbar">
			<span id="playbrick-css-title">PlayBrick CSS</span>
			<span id="playbrick-css-meta">
				<span class="playbrick-css-pill" id="playbrick-supported-count">0 visual</span>
				<span class="playbrick-css-pill is-warn" id="playbrick-unsupported-count">0 unsupported</span>
			</span>
			<button type="button" class="playbrick-css-btn" id="playbrick-css-copy">Copy generated</button>
			<button type="button" class="playbrick-css-btn" id="playbrick-css-copy-declarations">Copy declarations</button>
			<button type="button" class="playbrick-css-btn is-primary" id="playbrick-css-apply-visual">Apply to visual</button>
			<button type="button" class="playbrick-css-btn" id="playbrick-css-smaller">Smaller</button>
			<button type="button" class="playbrick-css-btn" id="playbrick-css-bigger">Bigger</button>
			<button type="button" class="playbrick-css-btn" id="playbrick-css-refresh">Refresh</button>
			<button type="button" class="playbrick-css-btn" id="playbrick-css-close">Close</button>
			<span id="playbrick-css-status">Select a Bricks global class</span>
		</div>
		<div id="playbrick-css-body">
			<div class="playbrick-css-col">
				<div class="playbrick-css-label">Generated from Bricks controls (read-only)</div>
				<pre id="playbrick-generated-css"></pre>
				<div id="playbrick-unsupported"></div>
			</div>
			<div class="playbrick-css-col">
				<div class="playbrick-css-label">Custom CSS (_cssCustom)</div>
				<textarea id="playbrick-custom-css" spellcheck="false"></textarea>
			</div>
		</div>
		<div id="playbrick-css-empty">Select a global class in Bricks to inspect and edit its CSS.</div>
		<div id="playbrick-css-resize" title="Drag to resize"></div>
	</div>
	<script id="playbrick-builder-panel-script">
	(function(){
		'use strict';
		if (window.location.href.indexOf('brickspreview=true') !== -1) return;

		var panel=document.getElementById('playbrick-css-panel');
		var title=document.getElementById('playbrick-css-title');
		var status=document.getElementById('playbrick-css-status');
		var generated=document.getElementById('playbrick-generated-css');
		var custom=document.getElementById('playbrick-custom-css');
		var copyBtn=document.getElementById('playbrick-css-copy');
		var copyDeclarationsBtn=document.getElementById('playbrick-css-copy-declarations');
		var applyVisualBtn=document.getElementById('playbrick-css-apply-visual');
		var smallerBtn=document.getElementById('playbrick-css-smaller');
		var biggerBtn=document.getElementById('playbrick-css-bigger');
		var closeBtn=document.getElementById('playbrick-css-close');
		var refreshBtn=document.getElementById('playbrick-css-refresh');
		var resizeHandle=document.getElementById('playbrick-css-resize');
		var supportedCount=document.getElementById('playbrick-supported-count');
		var unsupportedCount=document.getElementById('playbrick-unsupported-count');
		var unsupportedEl=document.getElementById('playbrick-unsupported');
		var currentClassId=null;
		var lastSignature='';
		var statusHoldUntil=0;
		var writeTimer=null;
		var isEditing=false;

		if(!panel||!generated||!custom) return;

		function clamp(value,min,max){return Math.max(min,Math.min(max,value));}
		function panelMaxHeight(){return Math.max(240,Math.floor(window.innerHeight*0.8));}
		function setPanelHeight(value){
			var height=clamp(parseInt(value,10)||300,180,panelMaxHeight());
			panel.style.height=height+'px';
			try{window.localStorage.setItem('playbrickCssPanelHeight',String(height));}catch(e){}
		}
		try{setPanelHeight(window.localStorage.getItem('playbrickCssPanelHeight')||300);}catch(e){setPanelHeight(300);}
		function setStatus(message,holdMs){status.textContent=message;statusHoldUntil=holdMs?Date.now()+holdMs:0;}
		function syncStatus(message){if(Date.now()>statusHoldUntil) status.textContent=message;}

		function getState(){
			try{var app=document.querySelector('[data-v-app]');return app&&app.__vue_app__&&app.__vue_app__.config.globalProperties.$_state||null;}catch(e){return null;}
		}

		function getActiveClass(){var state=getState();try{return state&&state.activeClass||null;}catch(e){return null;}}
		function getBreakpoint(){var state=getState();try{return state&&state.breakpointActive||'desktop';}catch(e){return 'desktop';}}
		function cssKey(){var bp=getBreakpoint();return !bp||bp==='desktop'?'_cssCustom':'_cssCustom:'+bp;}

		function cleanClassName(name){return String(name||'').replace(/^\./,'').trim();}
		function selectorFor(cls){return '.'+cleanClassName(cls.name||'');}
		function indent(lines){return lines.map(function(line){return '  '+line;}).join('\n');}

		function valueToCss(value){
			if(value===null||typeof value==='undefined'||value==='') return '';
			if(typeof value==='number') return String(value);
			if(typeof value==='string') return value;
			if(typeof value==='object'){
				if(value.raw) return value.raw;
				if(value.hex) return value.hex;
				if(value.rgb) return value.rgb;
				if(value.hsl) return value.hsl;
				if(value.value) return valueToCss(value.value);
			}
			return '';
		}

		function maybeUnit(value){
			if(value===null||typeof value==='undefined'||value==='') return '';
			if(typeof value==='number') return value+'px';
			return valueToCss(value);
		}

		function spacingToDecls(property, value){
			var out=[];
			if(typeof value==='string') return [property+': '+value+';'];
			if(!value||typeof value!=='object') return out;
			['top','right','bottom','left'].forEach(function(side){
				var sideValue=maybeUnit(value[side]);
				if(sideValue) out.push(property+'-'+side+': '+sideValue+';');
			});
			return out;
		}

		function backgroundToDecls(value){
			var out=[];
			if(typeof value==='string') return ['background: '+value+';'];
			if(!value||typeof value!=='object') return out;
			var color=valueToCss(value.color||value.backgroundColor||value['background-color']);
			if(color) out.push('background-color: '+color+';');
			var image=value.image||value.url||value.backgroundImage;
			if(image){
				var imageValue=typeof image==='object'?(image.url||image.full||image.src||''):image;
				if(imageValue) out.push('background-image: url("'+imageValue+'");');
			}
			return out;
		}

		function typographyToDecls(value){
			var out=[];
			if(!value||typeof value!=='object') return out;
			var map={
				'font-family':'font-family','font-size':'font-size','font-weight':'font-weight','line-height':'line-height','letter-spacing':'letter-spacing','text-align':'text-align','text-transform':'text-transform','font-style':'font-style','text-decoration':'text-decoration'
			};
			Object.keys(map).forEach(function(key){var val=maybeUnit(value[key]);if(val) out.push(map[key]+': '+val+';');});
			var color=valueToCss(value.color);
			if(color) out.push('color: '+color+';');
			return out;
		}

		function borderBoxToDecls(property,value){
			var out=[];
			if(typeof value==='string') return [property+': '+value+';'];
			if(!value||typeof value!=='object') return out;
			['top','right','bottom','left'].forEach(function(side){
				var sideValue=maybeUnit(value[side]);
				if(sideValue) out.push(property+'-'+side+': '+sideValue+';');
			});
			return out;
		}

		function borderToDecls(value){
			var out=[];
			if(!value||typeof value!=='object') return out;
			out=out.concat(borderBoxToDecls('border-width',value.width));
			var style=valueToCss(value.style);
			if(style) out.push('border-style: '+style+';');
			var color=valueToCss(value.color);
			if(color) out.push('border-color: '+color+';');
			out=out.concat(borderBoxToDecls('border-radius',value.radius));
			return out;
		}

		function boxShadowToDecls(value){
			if(!value||typeof value!=='object') return [];
			var values=value.values||value;
			var parts=[];
			if(value.inset||values.inset) parts.push('inset');
			['offsetX','offsetY','blur','spread'].forEach(function(key){var val=maybeUnit(values[key]);if(val) parts.push(val);});
			var color=valueToCss(value.color||values.color);
			if(color) parts.push(color);
			return parts.length>=2?['box-shadow: '+parts.join(' ')+';']:[];
		}

		function simpleDecl(property, value){var val=maybeUnit(value);return val?[property+': '+val+';']:[];}
		function settingKey(base){var bp=getBreakpoint();return !bp||bp==='desktop'?base:base+':'+bp;}
		function ensureObject(obj,key){if(!obj[key]||typeof obj[key]!=='object'||Array.isArray(obj[key])) obj[key]={};return obj[key];}
		function colorSetting(value){value=String(value||'').trim();if(!value) return '';if(value.indexOf('var(')===0||/^[a-z-]+$/i.test(value)) return {raw:value};if(value.charAt(0)==='#') return {hex:value};return {raw:value};}
		function splitBox(value){var parts=String(value||'').trim().split(/\s+/).filter(Boolean);if(!parts.length) return null;return {top:parts[0],right:parts[1]||parts[0],bottom:parts[2]||parts[0],left:parts[3]||parts[1]||parts[0]};}
		function extractColorToken(value){
			var text=String(value||'');
			var match=text.match(/(?:rgba?|hsla?)\([^)]*\)|#[0-9a-f]{3,8}\b|\b(?:transparent|inherit|currentcolor|black|white|red|green|blue|yellow|orange|purple|pink|gray|grey)\b/ig);
			if(!match||!match.length) return {color:'',rest:text};
			var color=match[match.length-1];
			return {color:color,rest:text.replace(color,' ').trim()};
		}
		function splitBorder(value){
			var result={};
			var extracted=extractColorToken(value);
			if(extracted.color) result.color=colorSetting(extracted.color);
			String(extracted.rest||'').trim().split(/\s+/).filter(Boolean).forEach(function(part){
				if(['none','solid','dashed','dotted','double','groove','ridge','inset','outset'].indexOf(part.toLowerCase())!==-1){result.style=part.toLowerCase();return;}
				if(/^-?[\d.]+(?:px|em|rem|%|vw|vh|vmin|vmax|ch|ex|pt|pc|cm|mm|in)?$/i.test(part)||/^(calc|clamp|min|max)\(/.test(part)){result.width=part;return;}
			});
			return Object.keys(result).length?result:null;
		}
		function splitShadow(value){
			var extracted=extractColorToken(value);
			var parts=String(extracted.rest||'').trim().split(/\s+/).filter(Boolean);
			var values=[];
			var inset=false;
			parts.forEach(function(part){
				if(part.toLowerCase()==='inset'){inset=true;return;}
				if(/^-?[\d.]+(?:px|em|rem|%|vw|vh|vmin|vmax|ch|ex|pt|pc|cm|mm|in)?$/i.test(part)||/^(calc|var|clamp|min|max)\(/.test(part)){values.push(part);return;}
			});
			if(values.length<2) return null;
			return {inset:inset,values:{offsetX:values[0],offsetY:values[1],blur:values[2]||'0px',spread:values[3]||'0px'},color:colorSetting(extracted.color||'rgba(0,0,0,0.2)')};
		}

		function isIgnoredSetting(base){return base.indexOf('_cssCustom')===0||base==='_cssGlobalClasses'||base==='_cssClasses'||base==='_cssGlobalClassesProps'||base==='_cssGlobalClassesPropsReplace';}

		function isEmptySetting(value){
			if(value===null||typeof value==='undefined'||value==='') return true;
			if(Array.isArray(value)) return !value.length;
			if(typeof value==='object') return !Object.keys(value).length;
			return false;
		}

		function declarationsFromSettings(settings){
			var out=[];
			var supported=[];
			var unsupported=[];
			if(!settings||typeof settings!=='object') return out;
			var bp=getBreakpoint();
			Object.keys(settings).forEach(function(key){
				if(isEmptySetting(settings[key])) return;
				var parts=key.split(':');
				var base=parts[0];
				if(isIgnoredSetting(base)) return;
				var keyBp=parts.length>1?parts[1]:'desktop';
				if((bp||'desktop')!==keyBp) return;
				var value=settings[key];
				var before=out.length;
				if(base==='_background') out=out.concat(backgroundToDecls(value));
				else if(base==='_padding') out=out.concat(spacingToDecls('padding',value));
				else if(base==='_margin') out=out.concat(spacingToDecls('margin',value));
				else if(base==='_typography') out=out.concat(typographyToDecls(value));
				else if(base==='_border') out=out.concat(borderToDecls(value));
				else if(base==='_boxShadow') out=out.concat(boxShadowToDecls(value));
				else if(base==='_width') out=out.concat(simpleDecl('width',value));
				else if(base==='_widthMin') out=out.concat(simpleDecl('min-width',value));
				else if(base==='_widthMax') out=out.concat(simpleDecl('max-width',value));
				else if(base==='_height') out=out.concat(simpleDecl('height',value));
				else if(base==='_heightMin') out=out.concat(simpleDecl('min-height',value));
				else if(base==='_heightMax') out=out.concat(simpleDecl('max-height',value));
				else if(base==='_display') out=out.concat(simpleDecl('display',value));
				else if(base==='_position') out=out.concat(simpleDecl('position',value));
				else if(base==='_top') out=out.concat(simpleDecl('top',value));
				else if(base==='_right') out=out.concat(simpleDecl('right',value));
				else if(base==='_bottom') out=out.concat(simpleDecl('bottom',value));
				else if(base==='_left') out=out.concat(simpleDecl('left',value));
				else if(base==='_zIndex') out=out.concat(simpleDecl('z-index',value));
				else if(base==='_overflow') out=out.concat(simpleDecl('overflow',value));
				else if(base==='_opacity') out=out.concat(simpleDecl('opacity',value));
				else if(base==='_flexDirection') out=out.concat(simpleDecl('flex-direction',value));
				else if(base==='_justifyContent') out=out.concat(simpleDecl('justify-content',value));
				else if(base==='_alignItems') out=out.concat(simpleDecl('align-items',value));
				else if(base==='_gap') out=out.concat(simpleDecl('gap',value));
				if(out.length>before) supported.push(base); else unsupported.push(key);
			});
			return {decls:out.filter(function(v,i,a){return a.indexOf(v)===i;}),supported:supported.filter(function(v,i,a){return a.indexOf(v)===i;}),unsupported:unsupported.filter(function(v,i,a){return a.indexOf(v)===i;})};
		}

		function generatedCssFor(cls){
			if(!cls||!cls.name) return '';
			var result=declarationsFromSettings(cls.settings||{});
			if(!result.decls.length) return selectorFor(cls)+' {\n  /* No supported visual controls detected yet. */\n}';
			return selectorFor(cls)+' {\n'+indent(result.decls)+'\n}';
		}

		function declarationsOnlyFromCss(css){
			var out=[];
			var re=/[^{}]+\{([^{}]*)\}/g;
			var match;
			while((match=re.exec(String(css||'')))){
				parseDeclarations(match[1]).forEach(function(decl){out.push(decl.prop+': '+decl.value+';');});
			}
			if(!out.length){
				parseDeclarations(css).forEach(function(decl){out.push(decl.prop+': '+decl.value+';');});
			}
			return out.join('\n');
		}

		function updateDiagnostics(cls){
			var result=declarationsFromSettings(cls&&cls.settings||{});
			if(supportedCount) supportedCount.textContent=result.supported.length+' visual';
			if(unsupportedCount) unsupportedCount.textContent=result.unsupported.length+' unsupported';
			if(!unsupportedEl) return;
			if(result.unsupported.length){
				unsupportedEl.style.display='block';
				unsupportedEl.innerHTML='<strong>Unsupported yet:</strong> '+result.unsupported.map(function(key){return key.replace(/[&<>]/g,function(ch){return {'&':'&amp;','<':'&lt;','>':'&gt;'}[ch];});}).join(', ');
			}else{
				unsupportedEl.style.display='none';
				unsupportedEl.textContent='';
			}
		}

		function normalizeCustomCss(cls,value){
			value=String(value||'').trim();
			if(!value) return '';
			if(value.indexOf('{')!==-1) return value;
			return selectorFor(cls)+' {\n'+indent(value.split('\n').filter(Boolean))+'\n}';
		}

		function applyPreview(cls,value){
			var css=normalizeCustomCss(cls,value);
			var style=document.getElementById('playbrick-custom-css-preview');
			if(!style){style=document.createElement('style');style.id='playbrick-custom-css-preview';document.head.appendChild(style);}
			style.textContent=css;
			var frames=document.getElementsByTagName('iframe');
			for(var i=0;i<frames.length;i++){
				try{var doc=frames[i].contentDocument;if(!doc) continue;var frameStyle=doc.getElementById('playbrick-custom-css-preview');if(!frameStyle){frameStyle=doc.createElement('style');frameStyle.id='playbrick-custom-css-preview';doc.head.appendChild(frameStyle);}frameStyle.textContent=css;}catch(e){}
			}
		}

		function parseDeclarations(body){
			return String(body||'').split(';').map(function(part){
				var idx=part.indexOf(':');
				if(idx===-1) return null;
				var prop=part.slice(0,idx).trim().toLowerCase();
				var value=part.slice(idx+1).trim();
				return prop&&value?{prop:prop,value:value}:null;
			}).filter(Boolean);
		}

		function parseCustomCss(cls,css){
			css=String(css||'').replace(/\/\*[\s\S]*?\*\//g,'').trim();
			var selector=selectorFor(cls);
			var decls=[];
			var preserved=[];
			var fallbackBlocks=[];
			var foundBlock=false;
			var re=/([^{}]+)\{([^{}]*)\}/g;
			var match;
			while((match=re.exec(css))){
				foundBlock=true;
				var rawSelector=match[1].trim();
				var body=match[2].trim();
				var selectors=rawSelector.split(',').map(function(item){return item.trim();});
				if(selectors.indexOf(selector)!==-1||selectors.indexOf('%root%')!==-1){
					decls=decls.concat(parseDeclarations(body));
				}else{
					var blockCss=rawSelector+' {\n'+indent(body.split(';').map(function(line){return line.trim();}).filter(Boolean).map(function(line){return /;$/.test(line)?line:line+';';}))+'\n}';
					preserved.push(blockCss);
					fallbackBlocks.push({css:blockCss,decls:parseDeclarations(body)});
				}
			}
			if(foundBlock&&!decls.length&&fallbackBlocks.length===1){decls=fallbackBlocks[0].decls;preserved=[];}
			if(!foundBlock) decls=parseDeclarations(css);
			return {decls:decls,preserved:preserved};
		}

		function mapDeclaration(settings,decl){
			var prop=decl.prop;
			var value=decl.value;
			var key;
			var box;
			if(prop==='background-color'||prop==='background'){
				ensureObject(settings,settingKey('_background')).color=colorSetting(value);
				return true;
			}
			if(prop==='padding'||prop==='margin'){
				box=splitBox(value);if(!box) return false;
				settings[settingKey(prop==='padding'?'_padding':'_margin')]=box;
				return true;
			}
			if(prop.indexOf('padding-')===0||prop.indexOf('margin-')===0){
				var kind=prop.indexOf('padding-')===0?'_padding':'_margin';
				var side=prop.split('-')[1];
				if(['top','right','bottom','left'].indexOf(side)===-1) return false;
				ensureObject(settings,settingKey(kind))[side]=value;
				return true;
			}
			if(prop==='border'){
				var parsedBorder=splitBorder(value);if(!parsedBorder) return false;
				var border=ensureObject(settings,settingKey('_border'));
				if(parsedBorder.width) border.width={top:parsedBorder.width,right:parsedBorder.width,bottom:parsedBorder.width,left:parsedBorder.width};
				if(parsedBorder.style) border.style=parsedBorder.style;
				if(parsedBorder.color) border.color=parsedBorder.color;
				return true;
			}
			if(prop==='border-width'||prop==='border-radius'){
				box=splitBox(value);if(!box) return false;
				ensureObject(settings,settingKey('_border'))[prop==='border-width'?'width':'radius']=box;
				return true;
			}
			if(prop.indexOf('border-')===0&&/(?:-width|-radius)$/.test(prop)){
				var borderKey=prop.indexOf('radius')!==-1?'radius':'width';
				var sideMap={'border-top-width':'top','border-right-width':'right','border-bottom-width':'bottom','border-left-width':'left','border-top-left-radius':'top','border-top-right-radius':'right','border-bottom-right-radius':'bottom','border-bottom-left-radius':'left'};
				if(!sideMap[prop]) return false;
				ensureObject(ensureObject(settings,settingKey('_border')),borderKey)[sideMap[prop]]=value;
				return true;
			}
			if(prop==='border-style'){ensureObject(settings,settingKey('_border')).style=value;return true;}
			if(prop==='border-color'){ensureObject(settings,settingKey('_border')).color=colorSetting(value);return true;}
			if(prop==='box-shadow'){
				var shadow=splitShadow(value);if(!shadow) return false;
				settings[settingKey('_boxShadow')]=shadow;
				return true;
			}
			var direct={width:'_width','min-width':'_widthMin','max-width':'_widthMax',height:'_height','min-height':'_heightMin','max-height':'_heightMax',display:'_display',position:'_position',top:'_top',right:'_right',bottom:'_bottom',left:'_left','z-index':'_zIndex',overflow:'_overflow',opacity:'_opacity','flex-direction':'_flexDirection','justify-content':'_justifyContent','align-items':'_alignItems',gap:'_gap'};
			if(direct[prop]){settings[settingKey(direct[prop])]=value;return true;}
			var typeMap={'font-family':'font-family','font-size':'font-size','font-weight':'font-weight','line-height':'line-height','letter-spacing':'letter-spacing','text-align':'text-align','text-transform':'text-transform','font-style':'font-style','text-decoration':'text-decoration'};
			if(typeMap[prop]){ensureObject(settings,settingKey('_typography'))[typeMap[prop]]=value;return true;}
			if(prop==='color'){ensureObject(settings,settingKey('_typography')).color=colorSetting(value);return true;}
			return false;
		}

		function rebuildCustomCss(cls,unmapped,preserved){
			var parts=[];
			if(unmapped.length){parts.push(selectorFor(cls)+' {\n'+indent(unmapped.map(function(decl){return decl.prop+': '+decl.value+';';}))+'\n}');}
			return parts.concat(preserved||[]).join('\n\n');
		}

		function sync(){
			var cls=getActiveClass();
			if(!cls||!cls.id||!cls.name){panel.classList.add('playbrick-no-class');currentClassId=null;title.textContent='PlayBrick CSS';statusHoldUntil=0;status.textContent='Select a Bricks global class';generated.textContent='';updateDiagnostics(null);if(!isEditing) custom.value='';return;}
			panel.classList.remove('playbrick-no-class');
			var settings=cls.settings||{};
			var key=cssKey();
			var sig=cls.id+'|'+getBreakpoint()+'|'+JSON.stringify(settings);
			if(sig===lastSignature) return;
			lastSignature=sig;
			currentClassId=cls.id;
			title.textContent='PlayBrick CSS – .'+cleanClassName(cls.name);
			syncStatus('Breakpoint: '+getBreakpoint());
			generated.textContent=generatedCssFor(cls);
			updateDiagnostics(cls);
			if(!isEditing) custom.value=settings[key]||'';
			applyPreview(cls,settings[key]||'');
		}

		function writeCustom(){
			var cls=getActiveClass();
			if(!cls||!cls.id) return;
			if(!cls.settings) cls.settings={};
			cls.settings[cssKey()]=custom.value;
			lastSignature='';
			applyPreview(cls,custom.value);
			sync();
		}

		custom.addEventListener('focus',function(){isEditing=true;});
		custom.addEventListener('blur',function(){isEditing=false;sync();});
		custom.addEventListener('input',function(){clearTimeout(writeTimer);writeTimer=setTimeout(writeCustom,150);});
		closeBtn.addEventListener('click',function(){panel.classList.add('playbrick-hidden');});
		if(smallerBtn) smallerBtn.addEventListener('click',function(){setPanelHeight((panel.offsetHeight||300)-80);});
		if(biggerBtn) biggerBtn.addEventListener('click',function(){setPanelHeight((panel.offsetHeight||300)+80);});
		if(resizeHandle) resizeHandle.addEventListener('mousedown',function(event){
			event.preventDefault();
			function move(moveEvent){setPanelHeight(window.innerHeight-moveEvent.clientY);}
			function up(){document.removeEventListener('mousemove',move);document.removeEventListener('mouseup',up);}
			document.addEventListener('mousemove',move);
			document.addEventListener('mouseup',up);
		});
		refreshBtn.addEventListener('click',function(){statusHoldUntil=0;lastSignature='';sync();});
		function copyText(button,text){
			if(!text) return;
			function done(){var old=button.textContent;button.textContent='Copied';setTimeout(function(){button.textContent=old;},1200);}
			if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(text).then(done).catch(function(){});}else{var ta=document.createElement('textarea');ta.value=text;document.body.appendChild(ta);ta.select();try{document.execCommand('copy');done();}catch(e){}document.body.removeChild(ta);}
		}
		copyBtn.addEventListener('click',function(){copyText(copyBtn,generated.textContent||'');});
		if(copyDeclarationsBtn) copyDeclarationsBtn.addEventListener('click',function(){
			var text=declarationsOnlyFromCss(generated.textContent||'');
			if(!text){setStatus('No declarations to copy',1600);return;}
			copyText(copyDeclarationsBtn,text);
		});
		applyVisualBtn.addEventListener('click',function(){
			var cls=getActiveClass();
			if(!cls||!cls.id) return;
			if(!cls.settings) cls.settings={};
			var parsed=parseCustomCss(cls,custom.value);
			var unmapped=[];
			var mapped=0;
			parsed.decls.forEach(function(decl){if(mapDeclaration(cls.settings,decl)) mapped++; else unmapped.push(decl);});
			if(!mapped){setStatus('No supported declarations to apply',2200);return;}
			var remaining=rebuildCustomCss(cls,unmapped,parsed.preserved);
			cls.settings[cssKey()]=remaining;
			custom.value=remaining;
			isEditing=false;
			lastSignature='';
			applyPreview(cls,remaining);
			setStatus('Applied '+mapped+' declaration'+(mapped===1?'':'s')+' to visual controls'+(remaining?' - unsupported kept in custom CSS':' - custom CSS cleared'),3200);
			sync();
		});

		function insertButton(){
			var actions=document.querySelector('#bricks-panel-inner #bricks-panel-header .actions');
			if(!actions||actions.querySelector('.playbrick-css-toggle')) return;
			var li=document.createElement('li');
			li.className='playbrick-css-toggle';
			li.setAttribute('data-balloon','PlayBrick CSS');
			li.setAttribute('data-balloon-pos','bottom-right');
			li.style.cursor='pointer';
			li.innerHTML='<span class="bricks-svg-wrapper"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="bricks-svg" style="width:18px;height:18px"><path d="M4 7h16M4 12h10M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M17 10l3 2-3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
			li.addEventListener('click',function(){panel.classList.toggle('playbrick-hidden');lastSignature='';sync();});
			actions.insertBefore(li,actions.firstElementChild);
		}

		function waitForBuilder(){
			insertButton();
			if(!getState()) return setTimeout(waitForBuilder,300);
			setInterval(function(){insertButton();sync();},250);
		}

		waitForBuilder();
	}());
	</script>
	<?php
}
