<?php
defined('ABSPATH') || exit;

add_action('wp_footer', 'playbrick_builder_panel_output', 100);

function playbrick_builder_panel_string_list( $items ) {
	if ( ! is_array( $items ) ) return [];

	$out  = [];
	$seen = [];
	foreach ( $items as $item ) {
		if ( ! is_string( $item ) ) continue;

		$item = trim( $item );
		if ( $item === '' || isset( $seen[ $item ] ) ) continue;

		$seen[ $item ] = true;
		$out[]         = $item;
	}

	return $out;
}

function playbrick_builder_panel_dynamic_tailwind_utilities() {
	if ( ! function_exists( 'get_option' ) || ! defined( 'ABSPATH' ) ) return [];

	$settings = get_option( 'playbrick_settings', [] );
	if ( ! is_array( $settings ) ) $settings = [];

	$playground_path = ! empty( $settings['playground_path'] ) ? $settings['playground_path'] : playbrick_default_playground_path();
	$playground_path = rtrim( (string) $playground_path, '/\\' );
	$path            = $playground_path . '/.playbrick/tailwind-utilities.json';

	if ( ! is_readable( $path ) ) return [];

	$data = json_decode( file_get_contents( $path ), true );
	if ( ! is_array( $data ) ) return [];

	$items = isset( $data['tailwindUtilities'] ) && is_array( $data['tailwindUtilities'] ) ? $data['tailwindUtilities'] : $data;

	return playbrick_builder_panel_string_list( $items );
}

function playbrick_is_bricks_builder_request() {
	return ! is_admin()
		&& isset($_GET['bricks'])
		&& $_GET['bricks'] === 'run'
		&& current_user_can('manage_options');
}

function playbrick_builder_panel_css_completions() {
	$path = PLAYBRICK_DIR . 'assets/css-completions.json';
	if (!is_readable($path)) return [ 'properties' => [], 'values' => [], 'commonValues' => [], 'tailwindUtilities' => [] ];

	$data = json_decode(file_get_contents($path), true);
	if (!is_array($data)) return [ 'properties' => [], 'values' => [], 'commonValues' => [], 'tailwindUtilities' => [] ];

	$tailwind_utilities = playbrick_builder_panel_string_list(
		array_merge(
			isset($data['tailwindUtilities']) && is_array($data['tailwindUtilities']) ? $data['tailwindUtilities'] : [],
			playbrick_builder_panel_dynamic_tailwind_utilities()
		)
	);

	return [
		'properties'        => playbrick_builder_panel_string_list( $data['properties'] ?? [] ),
		'values'            => isset($data['values']) && is_array($data['values']) ? $data['values'] : [],
		'commonValues'      => playbrick_builder_panel_string_list( $data['commonValues'] ?? [] ),
		'tailwindUtilities' => $tailwind_utilities,
	];
}

function playbrick_builder_panel_output() {
	if (!playbrick_is_bricks_builder_request()) return;
	$css_completions = playbrick_builder_panel_css_completions();
	$json_encode     = function_exists('wp_json_encode') ? 'wp_json_encode' : 'json_encode';
	?>
	<style id="playbrick-builder-panel-styles">
		#playbrick-css-panel{position:fixed;left:320px;right:320px;bottom:0;height:300px;min-height:180px;max-height:80vh;min-width:260px;z-index:100000;background:#151b22;border:1px solid rgba(51,153,255,.4);box-shadow:0 0 0 1px rgba(0,0,0,.4),0 -16px 40px rgba(0,0,0,.35);color:#d6deeb;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;display:flex;flex-direction:column}
		#playbrick-css-panel.playbrick-hidden{display:none!important}
		#playbrick-css-panel *{box-sizing:border-box}
		#playbrick-css-resize{position:absolute;left:0;right:0;top:-5px;height:10px;cursor:ns-resize;z-index:1}
		#playbrick-css-resize:after{content:"";position:absolute;left:50%;top:3px;width:44px;height:3px;margin-left:-22px;border-radius:999px;background:rgba(255,255,255,.22)}
		#playbrick-css-topbar{height:34px;display:flex;align-items:center;gap:10px;padding:0 10px;border-bottom:1px solid rgba(255,255,255,.08);background:#111820;font-size:12px;cursor:move;user-select:none;-webkit-user-select:none}
		#playbrick-css-title{font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
		#playbrick-css-status{margin-left:auto;color:#8a99a8;font-size:11px}
		#playbrick-css-meta{display:flex;gap:6px;align-items:center;color:#8a99a8;font-size:11px}
		.playbrick-css-pill{display:inline-flex;align-items:center;height:20px;padding:0 7px;border:1px solid rgba(255,255,255,.12);border-radius:999px;background:rgba(255,255,255,.04);color:#9fb0c2;font-size:10px;font-weight:700;letter-spacing:.02em}
		.playbrick-css-pill.is-warn{border-color:rgba(255,193,7,.28);color:#ffd479;background:rgba(255,193,7,.08)}
		.playbrick-css-btn{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#d6deeb;border-radius:4px;font-size:11px;height:24px;padding:0 8px;cursor:pointer}
		.playbrick-css-btn.is-primary{border-color:rgba(51,153,255,.45);background:rgba(51,153,255,.16);color:#b9dcff}
		.playbrick-css-btn:hover{background:rgba(255,255,255,.1);color:#fff}
		.playbrick-css-btn-icon{width:24px;padding:0;font-size:13px;line-height:1;text-align:center}
		#playbrick-css-body{display:grid;grid-template-columns:1fr 1fr;min-height:0;flex:1}
		.playbrick-css-col{display:flex;flex-direction:column;min-width:0;min-height:0;border-right:1px solid rgba(255,255,255,.08)}
		.playbrick-css-col:last-child{border-right:0}
		.playbrick-css-label{height:28px;display:flex;align-items:center;padding:0 10px;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#95a3b3;background:#1c242e;border-bottom:1px solid rgba(255,255,255,.08)}
		#playbrick-utilities-row{padding:6px 10px;border-bottom:1px solid rgba(255,255,255,.08);background:#141b24}
		#playbrick-utilities-input{display:block;width:100%;min-height:26px;max-height:110px;padding:5px 8px;border:1px solid rgba(255,255,255,.14);border-radius:4px;background:#0f141a;color:#d6deeb;font:12px/1.4 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;outline:0;resize:none;white-space:pre-wrap;word-break:break-word;overflow-y:auto}
		#playbrick-utilities-input:focus{border-color:rgba(51,153,255,.45)}
		#playbrick-generated-css,#playbrick-custom-css{flex:1;width:100%;min-height:0;margin:0;padding:12px;border:0;outline:0;background:#0f141a;color:#d6deeb;font:12px/1.55 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;tab-size:2;white-space:pre;overflow:auto}
		#playbrick-generated-css{color:#8fb7ff;background:#101720;user-select:text}
		#playbrick-custom-css{resize:none;color:#d6deeb;white-space:pre-wrap;word-break:break-word;overflow-x:hidden}
		#playbrick-css-autocomplete{position:absolute;display:none;z-index:1002;width:280px;max-height:190px;overflow:auto;background:#111820;border:1px solid rgba(255,255,255,.16);border-radius:6px;box-shadow:0 12px 32px rgba(0,0,0,.4);padding:4px;color:#d6deeb;font:12px/1.4 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
		.playbrick-css-suggestion{display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;padding:5px 7px;border:0;border-radius:4px;background:transparent;color:#d6deeb;text-align:left;font:inherit;cursor:pointer}
		.playbrick-css-suggestion:hover,.playbrick-css-suggestion.is-active{background:rgba(51,153,255,.18);color:#fff}
		.playbrick-css-suggestion-type{color:#8a99a8;font:10px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;text-transform:uppercase;letter-spacing:.04em}
		#playbrick-unsupported{display:none;max-height:54px;overflow:auto;padding:7px 10px;border-top:1px solid rgba(255,255,255,.08);background:#171f29;color:#ffd479;font:11px/1.4 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
		#playbrick-unsupported strong{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#f8d78b;margin-right:6px}
		#playbrick-css-empty{position:absolute;inset:34px 0 0;display:none;align-items:center;justify-content:center;background:#151b22;color:#95a3b3;font-size:13px;text-align:center;padding:24px}
		#playbrick-css-panel.playbrick-no-class #playbrick-css-empty{display:flex}
		#playbrick-css-panel.playbrick-no-class #playbrick-css-body{visibility:hidden}
		.playbrick-css-group{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
		.playbrick-css-resize-h{position:absolute;top:0;bottom:0;width:10px;cursor:ew-resize;z-index:1}
		.playbrick-css-resize-h:after{content:"";position:absolute;top:50%;left:3px;width:3px;height:44px;margin-top:-22px;border-radius:999px;background:rgba(255,255,255,.22)}
		#playbrick-css-resize-h-left{left:-5px}
		#playbrick-css-resize-h-right{right:-5px}
		#playbrick-css-panel.playbrick-narrow #playbrick-css-topbar{height:auto;flex-direction:column;align-items:stretch;padding:8px 10px}
		#playbrick-css-panel.playbrick-narrow .playbrick-css-group{width:100%}
		#playbrick-css-panel.playbrick-narrow #playbrick-css-status{margin-left:0}
		#playbrick-css-panel.playbrick-narrow #playbrick-css-body{grid-template-columns:1fr;grid-template-rows:1fr 1fr}
		#playbrick-css-panel.playbrick-narrow .playbrick-css-col{border-right:0;border-bottom:1px solid rgba(255,255,255,.08)}
		#playbrick-css-panel.playbrick-narrow .playbrick-css-col:last-child{border-bottom:0}
	</style>
	<div id="playbrick-css-panel" class="playbrick-hidden playbrick-no-class">
		<div id="playbrick-css-topbar">
			<span id="playbrick-css-title">PlayBrick CSS</span>
			<span class="playbrick-css-group playbrick-css-group-inspect">
				<span id="playbrick-css-meta">
					<span class="playbrick-css-pill" id="playbrick-supported-count">0 visual</span>
					<span class="playbrick-css-pill is-warn" id="playbrick-unsupported-count">0 unsupported</span>
				</span>
				<button type="button" class="playbrick-css-btn playbrick-css-btn-icon" id="playbrick-css-copy" title="Copy generated CSS">📋</button>
				<button type="button" class="playbrick-css-btn playbrick-css-btn-icon" id="playbrick-css-copy-declarations" title="Copy declarations only">📄</button>
				<button type="button" class="playbrick-css-btn playbrick-css-btn-icon is-primary" id="playbrick-css-apply-visual" title="Apply custom CSS to visual controls">🪄</button>
			</span>
			<span class="playbrick-css-group playbrick-css-group-general">
				<button type="button" class="playbrick-css-btn playbrick-css-btn-icon" id="playbrick-css-clear-custom" title="Clear custom">🧹</button>
				<button type="button" class="playbrick-css-btn playbrick-css-btn-icon" id="playbrick-css-smaller" title="Smaller">−</button>
				<button type="button" class="playbrick-css-btn playbrick-css-btn-icon" id="playbrick-css-bigger" title="Bigger">+</button>
				<button type="button" class="playbrick-css-btn playbrick-css-btn-icon" id="playbrick-css-refresh" title="Refresh">↻</button>
				<button type="button" class="playbrick-css-btn playbrick-css-btn-icon" id="playbrick-css-close" title="Close">×</button>
			</span>
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
				<div id="playbrick-utilities-row">
					<textarea id="playbrick-utilities-input" rows="1" autocomplete="off" spellcheck="false" placeholder="Tailwind utilities (element: add classes, global: @apply)…"></textarea>
				</div>
				<textarea id="playbrick-custom-css" spellcheck="false"></textarea>
			</div>
		</div>
		<div id="playbrick-css-empty">Select a Bricks global class or active element to inspect and edit its CSS.</div>
		<div id="playbrick-css-resize" title="Drag to resize height"></div>
		<div id="playbrick-css-resize-h-left" class="playbrick-css-resize-h" title="Drag to resize width"></div>
		<div id="playbrick-css-resize-h-right" class="playbrick-css-resize-h" title="Drag to resize width"></div>
		<div id="playbrick-css-autocomplete" role="listbox"></div>
	</div>
	<script type="application/json" id="playbrick-css-completions-data"><?php echo $json_encode($css_completions); ?></script>
	<script id="playbrick-builder-panel-script">
	(function(){
		'use strict';
		if (window.location.href.indexOf('brickspreview=true') !== -1) return;

		var panel=document.getElementById('playbrick-css-panel');
		var topbar=document.getElementById('playbrick-css-topbar');
		var title=document.getElementById('playbrick-css-title');
		var status=document.getElementById('playbrick-css-status');
		var generated=document.getElementById('playbrick-generated-css');
		var custom=document.getElementById('playbrick-custom-css');
		var utilitiesInput=document.getElementById('playbrick-utilities-input');
		var autocomplete=document.getElementById('playbrick-css-autocomplete');
		var completionsData=document.getElementById('playbrick-css-completions-data');
		var copyBtn=document.getElementById('playbrick-css-copy');
		var copyDeclarationsBtn=document.getElementById('playbrick-css-copy-declarations');
		var applyVisualBtn=document.getElementById('playbrick-css-apply-visual');
		var clearCustomBtn=document.getElementById('playbrick-css-clear-custom');
		var smallerBtn=document.getElementById('playbrick-css-smaller');
		var biggerBtn=document.getElementById('playbrick-css-bigger');
		var closeBtn=document.getElementById('playbrick-css-close');
		var refreshBtn=document.getElementById('playbrick-css-refresh');
		var resizeHandle=document.getElementById('playbrick-css-resize');
		var resizeHandleLeft=document.getElementById('playbrick-css-resize-h-left');
		var resizeHandleRight=document.getElementById('playbrick-css-resize-h-right');
		var supportedCount=document.getElementById('playbrick-supported-count');
		var unsupportedCount=document.getElementById('playbrick-unsupported-count');
		var unsupportedEl=document.getElementById('playbrick-unsupported');
		var currentClassId=null;
		var lastSignature='';
		var statusHoldUntil=0;
		var autocompleteItems=[];
		var autocompleteIndex=0;
		var autocompleteRange=null;
		var writeTimer=null;
		var isEditing=false;
		var isEditingUtilities=false;
		var utilitiesWriteTimer=null;
		var lastTargetKey='';

		if(!panel||!generated||!custom) return;

		function activeEditable(){
			var el=document.activeElement;
			return (el===custom||el===utilitiesInput)?el:null;
		}

		function clamp(value,min,max){return Math.max(min,Math.min(max,value));}
		function panelMaxHeight(){return Math.max(240,Math.floor(window.innerHeight*0.8));}
		function setPanelHeight(value){
			var height=clamp(parseInt(value,10)||300,180,panelMaxHeight());
			panel.style.height=height+'px';
			try{window.localStorage.setItem('playbrickCssPanelHeight',String(height));}catch(e){}
		}
		function setPanelPosition(left,top){
			var width=parseInt(panel.style.width,10)||panel.offsetWidth||480;
			var height=parseInt(panel.style.height,10)||panel.offsetHeight||300;
			left=clamp(left,0,Math.max(0,window.innerWidth-width));
			top=clamp(top,0,Math.max(0,window.innerHeight-height));
			panel.style.left=left+'px';
			panel.style.top=top+'px';
			panel.style.right='auto';
			panel.style.bottom='auto';
			try{window.localStorage.setItem('playbrickCssPanelPos',JSON.stringify({left:left,top:top,width:width}));}catch(e){}
		}
		function updateNarrowState(){panel.classList.toggle('playbrick-narrow',(panel.offsetWidth||0)<480);}
		function restorePanelPosition(){
			var saved=null;
			try{saved=JSON.parse(window.localStorage.getItem('playbrickCssPanelPos')||'null');}catch(e){saved=null;}
			if(!saved||typeof saved.left!=='number'||typeof saved.top!=='number') return;
			var width=saved.width||(window.innerWidth-640);
			var height=parseInt(panel.style.height,10)||300;
			panel.style.width=width+'px';
			panel.style.left=clamp(saved.left,0,Math.max(0,window.innerWidth-width))+'px';
			panel.style.top=clamp(saved.top,0,Math.max(0,window.innerHeight-height))+'px';
			panel.style.right='auto';
			panel.style.bottom='auto';
			updateNarrowState();
		}
		try{setPanelHeight(window.localStorage.getItem('playbrickCssPanelHeight')||300);}catch(e){setPanelHeight(300);}
		restorePanelPosition();
		updateNarrowState();
		function setStatus(message,holdMs){status.textContent=message;statusHoldUntil=holdMs?Date.now()+holdMs:0;}
		function syncStatus(message){if(Date.now()>statusHoldUntil) status.textContent=message;}
		function readCompletions(){try{return completionsData?JSON.parse(completionsData.textContent||'{}'):{};}catch(e){return {};}}
		var cssCompletions=readCompletions();

		function getState(){
			try{var app=document.querySelector('[data-v-app]');return app&&app.__vue_app__&&app.__vue_app__.config.globalProperties.$_state||null;}catch(e){return null;}
		}

		function validElementId(id){return typeof id==='string'&&/^[A-Za-z0-9]{6}$/.test(id);}
		function getActiveClass(){var state=getState();try{return state&&state.activeClass&&state.activeClass.id&&state.activeClass.name?state.activeClass:null;}catch(e){return null;}}
		function getActiveElement(){
			var state=getState();
			if(!state) return null;
			try{
				var direct=state.activeElement;
				if(direct&&validElementId(direct.id)) return direct;
				var activeId=state.activeId;
				if(!validElementId(activeId)) return null;
				var content=state.content;
				if(Array.isArray(content)) return content.filter(function(item){return item&&item.id===activeId;})[0]||null;
				if(content&&typeof content==='object'&&content[activeId]) return content[activeId];
			}catch(e){}
			return null;
		}
		function getTarget(){
			var cls=getActiveClass();
			if(cls) return {mode:'class',id:cls.id,name:cls.name,settings:cls.settings||{},object:cls};
			var element=getActiveElement();
			if(element&&validElementId(element.id)) return {mode:'element',id:element.id,name:element.name||'',settings:element.settings||{},object:element};
			return null;
		}
		function getBreakpoint(){var state=getState();try{return state&&state.breakpointActive||'desktop';}catch(e){return 'desktop';}}
		function cssKey(){var bp=getBreakpoint();return !bp||bp==='desktop'?'_cssCustom':'_cssCustom:'+bp;}

		function cleanClassName(name){return String(name||'').replace(/^\./,'').trim();}
		function selectorFor(target){
			if(target&&target.mode==='element'){
				// Standalone elements use #brxe-{id}; component context may expose a component marker.
				var component=target.object&&(target.object.isComponent||target.object.componentId||target.object._componentId);
				return (component?'.brxe-':'#brxe-')+target.id;
			}
			return '.'+cleanClassName(target&&target.name||'');
		}
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
			var position=backgroundPositionToCss(value);
			if(position) out.push('background-position: '+position+';');
			var size=backgroundSizeToCss(value);
			if(size) out.push('background-size: '+size+';');
			['repeat','attachment'].forEach(function(key){var val=valueToCss(value[key]);if(val) out.push('background-'+key+': '+val+';');});
			var blendMode=valueToCss(value.blendMode||value['blend-mode']||value['background-blend-mode']);
			if(blendMode) out.push('background-blend-mode: '+blendMode+';');
			return out;
		}

		function backgroundPositionToCss(value){
			if(!value||typeof value!=='object') return '';
			var position=valueToCss(value.position||value['background-position']);
			if(position==='custom') return [valueToCss(value.positionX)||'center',valueToCss(value.positionY)||'center'].join(' ');
			return position;
		}

		function backgroundSizeToCss(value){
			if(!value||typeof value!=='object') return '';
			var size=valueToCss(value.size||value['background-size']);
			if(size==='custom') return valueToCss(value.custom)||'';
			return size;
		}

		function gradientToDecls(value){
			var css=gradientToCss(value);
			return css?['background-image: '+css+';']:[];
		}

		function gradientToCss(value){
			if(!value||typeof value!=='object'||!Array.isArray(value.colors)||!value.colors.length) return '';
			if(value.applyTo&&value.applyTo!=='background') return '';
			var type=value.gradientType||'linear';
			if(type!=='linear') return '';
			var fn=(value.repeat?'repeating-':'')+type+'-gradient';
			var parts=[];
			if(type==='linear'&&typeof value.angle!=='undefined'&&value.angle!==null&&value.angle!=='') parts.push(value.angle+'deg');
			var colors=value.colors.map(function(item){
				var color=valueToCss(item&&item.color||item);
				if(!color) return '';
				var stop=item&&typeof item==='object'&&item.stop!==undefined&&item.stop!==''?String(item.stop):'';
				if(stop&&/^[-\d.]+$/.test(stop)) stop+='%';
				return stop?color+' '+stop:color;
			}).filter(Boolean);
			if(colors.length===1) colors.push(colors[0]);
			if(colors.length<2) return '';
			return fn+'('+parts.concat(colors).join(', ')+')';
		}

		function typographyToDecls(value){
			var out=[];
			if(!value||typeof value!=='object') return out;
			var map={
				'font-family':'font-family','font-size':'font-size','font-weight':'font-weight','line-height':'line-height','letter-spacing':'letter-spacing','text-align':'text-align','text-transform':'text-transform','font-style':'font-style','font-variation-settings':'font-variation-settings','white-space':'white-space','text-wrap':'text-wrap','text-decoration':'text-decoration'
			};
			Object.keys(map).forEach(function(key){var val=maybeUnit(value[key]);if(val) out.push(map[key]+': '+val+';');});
			out=out.concat(textShadowToDecls(value['text-shadow']));
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

		function textShadowToDecls(value){
			if(!value) return [];
			if(typeof value==='string') return value?['text-shadow: '+value+';']:[];
			if(typeof value!=='object') return [];
			var values=value.values||value;
			var parts=[];
			['offsetX','offsetY','blur'].forEach(function(key){var val=maybeUnit(values[key]);parts.push(val||'0');});
			var color=valueToCss(value.color||values.color);
			parts.push(color||'transparent');
			return ['text-shadow: '+parts.join(' ')+';'];
		}

		function aspectRatioValue(value){
			return String(valueToCss(value)||'').trim().replace(/\s*:\s*/g,' / ').replace(/\s*\/\s*/g,' / ');
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
		function splitTextShadow(value){
			value=String(value||'').trim();
			if(!value) return null;
			if(value.toLowerCase()==='none') return {values:{offsetX:'0',offsetY:'0',blur:'0'},color:colorSetting('transparent')};
			var shadow=splitShadow(value);
			if(!shadow) return null;
			return {values:{offsetX:shadow.values.offsetX,offsetY:shadow.values.offsetY,blur:shadow.values.blur},color:shadow.color};
		}
		function splitBackgroundPosition(value){
			value=String(value||'').trim().replace(/\s+/g,' ');
			if(!value) return null;
			var presets=['top left','top center','top right','center left','center center','center right','bottom left','bottom center','bottom right'];
			if(presets.indexOf(value)!==-1) return {position:value};
			var parts=value.split(' ');
			return {position:'custom',positionX:parts[0]||'center',positionY:parts.slice(1).join(' ')||'center'};
		}
		function splitBackgroundSize(value){
			value=String(value||'').trim();
			if(!value) return null;
			if(['auto','cover','contain'].indexOf(value)!==-1) return {size:value};
			return {size:'custom',custom:value};
		}
		function splitCssList(value){
			var out=[];
			var current='';
			var depth=0;
			String(value||'').split('').forEach(function(ch){
				if(ch==='(') depth++;
				if(ch===')') depth=Math.max(0,depth-1);
				if(ch===','&&depth===0){out.push(current.trim());current='';return;}
				current+=ch;
			});
			if(current.trim()) out.push(current.trim());
			return out;
		}
		function splitLinearGradient(value){
			var text=String(value||'').trim();
			var match=text.match(/^(repeating-)?linear-gradient\((.*)\)$/i);
			if(!match) return null;
			var parts=splitCssList(match[2]);
			if(parts.length<2) return null;
			var gradient={applyTo:'background',gradientType:'linear',colors:[]};
			if(match[1]) gradient.repeat=true;
			var first=parts[0].toLowerCase();
			if(/^-?\d+(?:\.\d+)?deg$/.test(first)){
				gradient.angle=parseFloat(first);
				parts.shift();
			}
			parts.forEach(function(part){
				var extracted=extractColorToken(part);
				if(!extracted.color) return;
				var stop=String(extracted.rest||'').trim().split(/\s+/)[0]||'';
				gradient.colors.push({color:colorSetting(extracted.color),stop:stop.replace(/%$/,'')});
			});
			return gradient.colors.length>=2?gradient:null;
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
				else if(base==='_gradient') out=out.concat(gradientToDecls(value));
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
				else if(base==='_aspectRatio') out=out.concat(simpleDecl('aspect-ratio',aspectRatioValue(value)));
				else if(base==='_display') out=out.concat(simpleDecl('display',value));
				else if(base==='_position') out=out.concat(simpleDecl('position',value));
				else if(base==='_top') out=out.concat(simpleDecl('top',value));
				else if(base==='_right') out=out.concat(simpleDecl('right',value));
				else if(base==='_bottom') out=out.concat(simpleDecl('bottom',value));
				else if(base==='_left') out=out.concat(simpleDecl('left',value));
				else if(base==='_zIndex') out=out.concat(simpleDecl('z-index',value));
				else if(base==='_order') out=out.concat(simpleDecl('order',value));
				else if(base==='_visibility') out=out.concat(simpleDecl('visibility',value));
				else if(base==='_overflow') out=out.concat(simpleDecl('overflow',value));
				else if(base==='_opacity') out=out.concat(simpleDecl('opacity',value));
				else if(base==='_cursor') out=out.concat(simpleDecl('cursor',value));
				else if(base==='_isolation') out=out.concat(simpleDecl('isolation',value));
				else if(base==='_mixBlendMode') out=out.concat(simpleDecl('mix-blend-mode',value));
				else if(base==='_pointerEvents') out=out.concat(simpleDecl('pointer-events',value));
				else if(base==='_perspective') out=out.concat(simpleDecl('perspective',value));
				else if(base==='_perspectiveOrigin') out=out.concat(simpleDecl('perspective-origin',value));
				else if(base==='_flexDirection') out=out.concat(simpleDecl('flex-direction',value));
				else if(base==='_alignSelf') out=out.concat(simpleDecl('align-self',value));
				else if(base==='_justifyContent') out=out.concat(simpleDecl('justify-content',value));
				else if(base==='_alignItems') out=out.concat(simpleDecl('align-items',value));
				else if(base==='_gap') out=out.concat(simpleDecl('gap',value));
				else if(base==='_gridItemJustifySelf') out=out.concat(simpleDecl('justify-self',value));
				else if(base==='_flexGrow') out=out.concat(simpleDecl('flex-grow',value));
				else if(base==='_flexShrink') out=out.concat(simpleDecl('flex-shrink',value));
				else if(base==='_flexBasis') out=out.concat(simpleDecl('flex-basis',value));
				if(out.length>before) supported.push(base); else unsupported.push(key);
			});
			return {decls:out.filter(function(v,i,a){return a.indexOf(v)===i;}),supported:supported.filter(function(v,i,a){return a.indexOf(v)===i;}),unsupported:unsupported.filter(function(v,i,a){return a.indexOf(v)===i;})};
		}

		function generatedCssFor(target){
			if(!target) return '';
			var result=declarationsFromSettings(target.settings||{});
			if(!result.decls.length) return selectorFor(target)+' {\n  /* No supported visual controls detected yet. */\n}';
			return selectorFor(target)+' {\n'+indent(result.decls)+'\n}';
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

		function normalizeCustomCss(target,value){
			value=String(value||'').trim();
			if(!value) return '';
			if(value.indexOf('{')!==-1) return value;
			return selectorFor(target)+' {\n'+indent(value.split('\n').filter(Boolean))+'\n}';
		}

		function applyPreview(target,value){
			var css=normalizeCustomCss(target,value);
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

		function currentAutocompleteContext(){
			var field=activeEditable()||custom;
			if(field===utilitiesInput){
				var uPos=field.selectionStart||0;
				var uBefore=(field.value||'').slice(0,uPos);
				var uMatch=uBefore.match(/([^\s]*)$/);
				var uQuery=uMatch?uMatch[1]:'';
				return {mode:'tailwind',from:uPos-uQuery.length,to:uPos,query:uQuery.toLowerCase(),property:''};
			}
			var pos=custom.selectionStart||0;
			var text=custom.value||'';
			var lineStart=text.lastIndexOf('\n',pos-1)+1;
			var line=text.slice(lineStart,pos);
			var segment=line.slice(Math.max(line.lastIndexOf(';')+1,line.lastIndexOf('{')+1));
			var applyMatch=segment.match(/@apply\s+([^;{}]*)$/);
			if(applyMatch){
				var utilities=applyMatch[1];
				var utilityMatch=utilities.match(/([^\s]*)$/);
				var utilityText=utilityMatch?utilityMatch[1]:'';
				return {mode:'tailwind',from:pos-utilityText.length,to:pos,query:utilityText.toLowerCase(),property:''};
			}
			var colon=segment.indexOf(':');
			if(colon===-1){
				var propMatch=segment.match(/([a-z-]*)$/i);
				var propText=propMatch?propMatch[1]:'';
				return {mode:'property',from:pos-propText.length,to:pos,query:propText.toLowerCase(),property:''};
			}
			var property=segment.slice(0,colon).trim().toLowerCase();
			var valuePart=segment.slice(colon+1);
			var propertyValues=cssCompletions.values&&cssCompletions.values[property];
			if(propertyValues&&propertyValues.length){
				var leading=valuePart.match(/^\s*/)[0].length;
				var valueText=valuePart.slice(leading);
				return {mode:'value',from:pos-valuePart.length+leading,to:pos,query:valueText.trim().toLowerCase(),property:property};
			}
			var valueMatch=valuePart.match(/([^\s;]*)$/);
			var valueText=valueMatch?valueMatch[1]:'';
			return {mode:'value',from:pos-valueText.length,to:pos,query:valueText.toLowerCase(),property:property};
		}

		function completionOptions(context,explicit){
			var source=context.mode==='tailwind'?(cssCompletions.tailwindUtilities||[]):(context.mode==='property'?(cssCompletions.properties||[]):((cssCompletions.values&&cssCompletions.values[context.property])||[]).concat(cssCompletions.commonValues||[]));
			var seen={};
			var options=source.filter(function(item){
				item=String(item||'');
				if(!item||seen[item]) return false;
				seen[item]=true;
				return explicit||!context.query||item.toLowerCase().indexOf(context.query)===0;
			});
			return options.slice(0,24).map(function(label){return {label:label,type:context.mode};});
		}

		function positionAutocomplete(){
			if(!autocomplete) return;
			var field=activeEditable()||custom;
			var rect=field.getBoundingClientRect();
			var panelRect=panel.getBoundingClientRect();
			var before=(field.value||'').slice(0,field.selectionStart||0).split('\n');
			var lineHeight=18.6;
			var charWidth=7.2;
			var top=rect.top-panelRect.top+12+((before.length-1)*lineHeight)-field.scrollTop+lineHeight;
			var left=rect.left-panelRect.left+12+(before[before.length-1].length*charWidth)-field.scrollLeft;
			top=Math.max(rect.top-panelRect.top+8,Math.min(top,rect.bottom-panelRect.top-20));
			left=Math.max(rect.left-panelRect.left+8,Math.min(left,rect.right-panelRect.left-290));
			autocomplete.style.top=top+'px';
			autocomplete.style.left=left+'px';
		}

		function renderAutocomplete(){
			if(!autocomplete) return;
			autocomplete.innerHTML=autocompleteItems.map(function(item,index){return '<button type="button" class="playbrick-css-suggestion'+(index===autocompleteIndex?' is-active':'')+'" data-index="'+index+'"><span>'+item.label.replace(/[&<>]/g,function(ch){return {'&':'&amp;','<':'&lt;','>':'&gt;'}[ch];})+'</span><span class="playbrick-css-suggestion-type">'+item.type+'</span></button>';}).join('');
			positionAutocomplete();
			autocomplete.style.display=autocompleteItems.length?'block':'none';
		}

		function updateAutocomplete(explicit){
			if(!activeEditable()){hideAutocomplete();return;}
			var context=currentAutocompleteContext();
			var hasPropertyValues=context.mode==='value'&&cssCompletions.values&&cssCompletions.values[context.property]&&cssCompletions.values[context.property].length;
			if(!explicit&&context.query.length<1&&!hasPropertyValues&&context.mode!=='tailwind'){hideAutocomplete();return;}
			autocompleteRange={from:context.from,to:context.to,mode:context.mode};
			autocompleteItems=completionOptions(context,explicit);
			autocompleteIndex=0;
			renderAutocomplete();
		}

		function hideAutocomplete(){if(autocomplete) autocomplete.style.display='none';autocompleteItems=[];autocompleteRange=null;}
		function autocompleteVisible(){return autocomplete&&autocomplete.style.display==='block'&&autocompleteItems.length;}
		function moveAutocomplete(delta){autocompleteIndex=(autocompleteIndex+delta+autocompleteItems.length)%autocompleteItems.length;renderAutocomplete();}
		function applyAutocomplete(index){
			if(!autocompleteRange||!autocompleteItems[index]) return;
			var field=activeEditable()||custom;
			var item=autocompleteItems[index];
			var insert=item.label+(autocompleteRange.mode==='property'?': ':(autocompleteRange.mode==='tailwind'?' ':''));
			var before=field.value.slice(0,autocompleteRange.from);
			var after=field.value.slice(autocompleteRange.to);
			field.value=before+insert+after;
			var cursor=before.length+insert.length;
			field.selectionStart=cursor;
			field.selectionEnd=cursor;
			hideAutocomplete();
			field.dispatchEvent(new Event('input',{bubbles:true}));
		}

		function parseCustomCss(target,css){
			css=String(css||'').replace(/\/\*[\s\S]*?\*\//g,'').trim();
			var selector=selectorFor(target);
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
			if(prop==='background'){
				var backgroundGradient=splitLinearGradient(value);
				if(backgroundGradient){settings[settingKey('_gradient')]=backgroundGradient;return true;}
				ensureObject(settings,settingKey('_background')).color=colorSetting(value);
				return true;
			}
			if(prop==='background-color'){
				ensureObject(settings,settingKey('_background')).color=colorSetting(value);
				return true;
			}
			if(prop==='background-image'){
				var gradient=splitLinearGradient(value);
				if(gradient){settings[settingKey('_gradient')]=gradient;return true;}
				var imageMatch=String(value||'').match(/url\((['"]?)(.*?)\1\)/i);
				if(!imageMatch||!imageMatch[2]) return false;
				ensureObject(settings,settingKey('_background')).image={url:imageMatch[2]};
				return true;
			}
			if(prop==='background-position'){
				var position=splitBackgroundPosition(value);if(!position) return false;
				var bgPosition=ensureObject(settings,settingKey('_background'));
				Object.keys(position).forEach(function(key){bgPosition[key]=position[key];});
				return true;
			}
			if(prop==='background-size'){
				var size=splitBackgroundSize(value);if(!size) return false;
				var bgSize=ensureObject(settings,settingKey('_background'));
				Object.keys(size).forEach(function(key){bgSize[key]=size[key];});
				return true;
			}
			if(prop==='background-repeat'){ensureObject(settings,settingKey('_background')).repeat=value;return true;}
			if(prop==='background-attachment'){ensureObject(settings,settingKey('_background')).attachment=value;return true;}
			if(prop==='background-blend-mode'){ensureObject(settings,settingKey('_background')).blendMode=value;return true;}
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
			if(prop==='text-shadow'){
				var textShadow=splitTextShadow(value);if(!textShadow) return false;
				ensureObject(settings,settingKey('_typography'))['text-shadow']=textShadow;
				return true;
			}
			if(prop==='aspect-ratio'){
				settings[settingKey('_aspectRatio')]=aspectRatioValue(value);
				return true;
			}
			var direct={width:'_width','min-width':'_widthMin','max-width':'_widthMax',height:'_height','min-height':'_heightMin','max-height':'_heightMax',display:'_display',position:'_position',top:'_top',right:'_right',bottom:'_bottom',left:'_left','z-index':'_zIndex',order:'_order',visibility:'_visibility',overflow:'_overflow',opacity:'_opacity',cursor:'_cursor',isolation:'_isolation','mix-blend-mode':'_mixBlendMode','pointer-events':'_pointerEvents',perspective:'_perspective','perspective-origin':'_perspectiveOrigin','flex-direction':'_flexDirection','align-self':'_alignSelf','justify-content':'_justifyContent','align-items':'_alignItems',gap:'_gap','justify-self':'_gridItemJustifySelf','flex-grow':'_flexGrow','flex-shrink':'_flexShrink','flex-basis':'_flexBasis'};
			if(direct[prop]){settings[settingKey(direct[prop])]=value;return true;}
			var typeMap={'font-family':'font-family','font-size':'font-size','font-weight':'font-weight','line-height':'line-height','letter-spacing':'letter-spacing','text-align':'text-align','text-transform':'text-transform','font-style':'font-style','font-variation-settings':'font-variation-settings','white-space':'white-space','text-wrap':'text-wrap','text-decoration':'text-decoration'};
			if(typeMap[prop]){ensureObject(settings,settingKey('_typography'))[typeMap[prop]]=value;return true;}
			if(prop==='color'){ensureObject(settings,settingKey('_typography')).color=colorSetting(value);return true;}
			return false;
		}

		function rebuildCustomCss(target,unmapped,preserved){
			var parts=[];
			if(unmapped.length){parts.push(selectorFor(target)+' {\n'+indent(unmapped.map(function(decl){return decl.prop+': '+decl.value+';';}))+'\n}');}
			return parts.concat(preserved||[]).join('\n\n');
		}

		function extractUtilities(value){
			var match=String(value||'').match(/@apply\s+([^;\n]*)/i);
			return match?match[1].trim():'';
		}

		function upsertApplyLine(value,utilities){
			value=String(value||'');
			var line='@apply '+utilities.trim()+';';
			if(/@apply\s+[^;\n]*;?/i.test(value)) return value.replace(/@apply\s+[^;\n]*;?/i,line);
			return value?(value.replace(/\s*$/,'')+'\n'+line):line;
		}

		function removeApplyLine(value){
			return String(value||'').replace(/@apply\s+[^;\n]*;?\n?/i,'').replace(/\n{3,}/g,'\n\n').trim();
		}

		function autosizeUtilities(){
			if(!utilitiesInput) return;
			utilitiesInput.style.height='auto';
			utilitiesInput.style.height=Math.min(utilitiesInput.scrollHeight,110)+'px';
		}

		function syncUtilitiesField(){
			if(isEditingUtilities||!utilitiesInput) return;
			var target=getTarget();
			utilitiesInput.value=target&&target.mode==='element'?normalizeUtilityList(target.settings&&target.settings._cssClasses||'').join(' '):extractUtilities(custom.value);
			autosizeUtilities();
		}

		function sync(){
			var target=getTarget();
			if(!target){panel.classList.add('playbrick-no-class');currentClassId=null;lastSignature='';lastTargetKey='';title.textContent='PlayBrick CSS';statusHoldUntil=0;status.textContent='Select a Bricks global class or active element';generated.textContent='';updateDiagnostics(null);if(!isEditing) custom.value='';syncUtilitiesField();return;}
			panel.classList.remove('playbrick-no-class');
			var settings=target.settings||{};
			var key=cssKey();
			var targetKey=target.mode+'|'+target.id+'|'+getBreakpoint();
			if(lastTargetKey&&lastTargetKey!==targetKey){isEditing=false;isEditingUtilities=false;hideAutocomplete();}
			lastTargetKey=targetKey;
			var sig=target.mode+'|'+target.id+'|'+getBreakpoint()+'|'+JSON.stringify(settings);
			if(sig===lastSignature) return;
			lastSignature=sig;
			currentClassId=target.id;
			title.textContent=target.mode==='element'?'PlayBrick CSS – '+selectorFor(target):'PlayBrick CSS – .'+cleanClassName(target.name);
			syncStatus((target.mode==='element'?'Element '+selectorFor(target):'Breakpoint')+': '+getBreakpoint());
			generated.textContent=generatedCssFor(target);
			updateDiagnostics(target);
			if(!isEditing) custom.value=settings[key]||'';
			syncUtilitiesField();
			applyPreview(target,settings[key]||'');
		}

		function writeCustom(){
			var target=getTarget();
			if(!target) return;
			if(!target.object.settings) target.object.settings={};
			var value=target.mode==='element'?normalizeCustomCss(target,custom.value):custom.value;
			target.object.settings[cssKey()]=value;
			if(target.mode==='element'&&!isEditing) custom.value=value;
			lastSignature='';
			applyPreview(target,value);
			sync();
		}

		function normalizeUtilityList(value){return String(value||'').trim().split(/\s+/).filter(Boolean).filter(function(item,index,list){return list.indexOf(item)===index;});}
		function writeElementUtilities(element,value){
			if(!element) return '';
			if(!element.settings||typeof element.settings!=='object') element.settings={};
			var normalized=normalizeUtilityList(value).join(' ');
			element.settings._cssClasses=normalized;
			return normalized;
		}
		function writeUtilities(){
			var target=getTarget();
			if(!target) return;
			if(target.mode==='element'){
				writeElementUtilities(target.object,utilitiesInput.value);
				setStatus('Utilities updated for '+selectorFor(target),2200);
				lastSignature='';sync();
				return;
			}
			var utilities=utilitiesInput.value.trim();
			custom.value=utilities?upsertApplyLine(custom.value,utilities):removeApplyLine(custom.value);
			writeCustom();
		}

		function handleAutocompleteKeydown(event){
			if(autocompleteVisible()){
				if(event.key==='ArrowDown'){event.preventDefault();moveAutocomplete(1);return;}
				if(event.key==='ArrowUp'){event.preventDefault();moveAutocomplete(-1);return;}
				if(event.key==='Enter'||event.key==='Tab'){event.preventDefault();applyAutocomplete(autocompleteIndex);return;}
				if(event.key==='Escape'){event.preventDefault();hideAutocomplete();return;}
			}
			if(event.ctrlKey&&event.key===' '){event.preventDefault();updateAutocomplete(true);}
		}

		custom.addEventListener('focus',function(){isEditing=true;});
		custom.addEventListener('blur',function(){setTimeout(hideAutocomplete,120);isEditing=false;sync();});
		custom.addEventListener('input',function(){updateAutocomplete(false);clearTimeout(writeTimer);writeTimer=setTimeout(writeCustom,150);});
		custom.addEventListener('click',function(){updateAutocomplete(false);});
		custom.addEventListener('keydown',handleAutocompleteKeydown);
		if(utilitiesInput){
			utilitiesInput.addEventListener('focus',function(){isEditingUtilities=true;});
			utilitiesInput.addEventListener('blur',function(){setTimeout(hideAutocomplete,120);isEditingUtilities=false;syncUtilitiesField();});
			utilitiesInput.addEventListener('input',function(){autosizeUtilities();updateAutocomplete(false);clearTimeout(utilitiesWriteTimer);utilitiesWriteTimer=setTimeout(writeUtilities,150);});
			utilitiesInput.addEventListener('click',function(){updateAutocomplete(false);});
			utilitiesInput.addEventListener('keydown',handleAutocompleteKeydown);
		}
		if(autocomplete) autocomplete.addEventListener('mousedown',function(event){
			var button=event.target.closest&&event.target.closest('.playbrick-css-suggestion');
			if(!button) return;
			event.preventDefault();
			applyAutocomplete(parseInt(button.getAttribute('data-index'),10)||0);
		});
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
		function beginWidthResize(handle,fromLeft){
			if(!handle) return;
			handle.addEventListener('mousedown',function(event){
				event.preventDefault();
				var rect=panel.getBoundingClientRect();
				var startLeft=rect.left;
				var startRight=rect.right;
				var maxWidth=Math.max(320,window.innerWidth-40);
				function move(moveEvent){
					var width=fromLeft?(startRight-moveEvent.clientX):(moveEvent.clientX-startLeft);
					width=clamp(width,260,maxWidth);
					if(fromLeft) panel.style.left=(startRight-width)+'px';
					panel.style.right='auto';
					panel.style.width=width+'px';
					updateNarrowState();
					try{window.localStorage.setItem('playbrickCssPanelPos',JSON.stringify({left:panel.offsetLeft,top:panel.offsetTop,width:width}));}catch(e){}
				}
				function up(){document.removeEventListener('mousemove',move);document.removeEventListener('mouseup',up);}
				document.addEventListener('mousemove',move);
				document.addEventListener('mouseup',up);
			});
		}
		beginWidthResize(resizeHandleLeft,true);
		beginWidthResize(resizeHandleRight,false);
		if(topbar) topbar.addEventListener('mousedown',function(event){
			if(event.target.closest&&event.target.closest('.playbrick-css-btn')) return;
			if(panel.classList.contains('playbrick-hidden')) return;
			event.preventDefault();
			var startLeft=panel.offsetLeft;
			var startTop=panel.offsetTop;
			var startX=event.clientX;
			var startY=event.clientY;
			panel.style.width=panel.offsetWidth+'px';
			function move(moveEvent){setPanelPosition(startLeft+(moveEvent.clientX-startX),startTop+(moveEvent.clientY-startY));}
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
		if(clearCustomBtn) clearCustomBtn.addEventListener('click',function(){
			var target=getTarget();
			if(!target) return;
			if(!target.object.settings) target.object.settings={};
			target.object.settings[cssKey()]='';
			if(target.mode==='element') target.object.settings._cssClasses='';
			custom.value='';
			isEditing=false;
			lastSignature='';
			applyPreview(target,'');
			setStatus((target.mode==='element'?'Custom CSS and element classes cleared for '+selectorFor(target):'Custom CSS cleared for global class')+' at '+getBreakpoint(),2200);
			sync();
		});
		applyVisualBtn.addEventListener('click',function(){
			var target=getTarget();
			if(!target) return;
			if(!target.object.settings) target.object.settings={};
			var parsed=parseCustomCss(target,custom.value);
			var unmapped=[];
			var mapped=0;
			parsed.decls.forEach(function(decl){if(mapDeclaration(target.object.settings,decl)) mapped++; else unmapped.push(decl);});
			if(!mapped){setStatus(/@apply\s+/.test(custom.value||'')?'Tailwind @apply stays in custom CSS - save Bricks and let watch rebuild':'No supported declarations to apply',3200);return;}
			var remaining=rebuildCustomCss(target,unmapped,parsed.preserved);
			target.object.settings[cssKey()]=remaining;
			custom.value=remaining;
			isEditing=false;
			lastSignature='';
			applyPreview(target,remaining);
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
