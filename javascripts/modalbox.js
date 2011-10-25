/*
 ModalBox - The pop-up window thingie with AJAX, based on prototype and script.aculo.us.

 Copyright Andrew Okonetchnikov (andrej.okonetschnikow@gmail.com), 2006
 All rights reserved.
 
 VERSION 1.4 
 Last Modified: 20/06/2006
 
 Changelog:
 
 var 1.4: (06/20/2006)
 Added: 	Core definitions rewriten. Modalbox can now be accessed thorugh Modalbox object with public methods show and hide
 Added: 	License added
 Changed:	kbdHandler method is now public, so it can be stopped from other functions
 Fixed: 	Stopping of event observing in hide method
 Fixed: 	Hiding selects for IE issue (was applied on element ID)
 Removed:	Redundant 'globalMB' global variable removed
 Removed:	Scroll window events observerving
 Removed:	Redundant effect ScalyTo
 Issue: 	IE display bug then hidding scrollbars. Document body should have zero margins
 
 var 1.3: (06/18/2006)
 Added: 	ModalBox will now get focus after opening
 Added: 	Keystrokes handler added (Tab key is looped on ModalBox and closing ModalBox by pressing Esc)
 Added: 	Window scrolling disabled (known issue: content jupms on top then opening ModalBox)
 Fixed: 	All dependent event handlers now unloads then closing ModalBox
 Fixed: 	SELECT element hiding function executes now only in MSIE
 Fixed: 	'Close' button has now href attribute to receive focus
 Fixed: 	Click on 'Close' button doesn't adds an href value to URL string
 
 ver 1.2: 
 Added: Global variable 'globalMB' added to the file. Use this variable to acces one instance of ModalBox and call methods on it
 
 ver 1.1: 
 Added: Added SELECT elements hiding for IE (should be rewriten later)
 
 ver 1.0: 
 Added: Core class description
 
 
Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * Neither the name of the David Spurr nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

* http://www.opensource.org/licenses/bsd-license.php
 
See scriptaculous.js for full scriptaculous licence

*/

if (!window.Modalbox)
	var Modalbox = new Object();

Modalbox.Methods = {
	
	setOptions: function(options) {
		this.options = {
			overlayClose: true, // Close modal box by clicking on overlay
			width: 400,
			height: 400
		};
		Object.extend(this.options, options || {});
	},
	
	_init: function() {
		// Define there page content starts (first element after body)
		this.pageContent = document.body.childNodes[0];
		
		//Create the overlay
		this.MBoverlay = document.createElement("div");
		this.MBoverlay.id = "MB_overlay";
		
		
		this.hide = this.hide.bindAsEventListener(this);
		
		if(this.options.overlayClose)
			Event.observe(this.MBoverlay, "click", this.hide );
		
		document.body.insertBefore(this.MBoverlay, document.body.firstChild);
		
		//Create the window
		this.MBwrapper = document.createElement("div");
		this.MBwrapper.id = "MB_wrapper";
		
		this.MBwindow = document.createElement("div");
		this.MBwindow.id = "MB_window";
		this.MBwindow.style.display = "none";
		
		this.MBheader = document.createElement("div");
		this.MBheader.id = "MB_header";
		
		this.MBframe = document.createElement("div");
		this.MBframe.id = "MB_frame";
		
		this.MBcontent = document.createElement("div");
		this.MBcontent.id = "MB_content";
		
		this.MBloading = document.createElement("div");
		this.MBloading.id = "MB_loading";
		this.MBloading.appendChild(document.createTextNode("Loading..."));
		
		this.MBcaption = document.createElement("div");
		this.MBcaption.id = "MB_caption";
		
		this.MBclose = document.createElement("A");
		this.MBclose.title = "Close window";
		this.MBclose.id = "MB_close";
		this.MBclose.href = "#";
		this.MBclose.innerHTML = "&times;";
		
		Event.observe(this.MBclose, "click", this.hide );
		
		this.MBheader.appendChild(this.MBcaption);
		this.MBheader.appendChild(this.MBclose);
		this.MBframe.appendChild(this.MBheader);
		this.MBcontent.appendChild(this.MBloading);
		this.MBframe.appendChild(this.MBcontent);
		this.MBwindow.appendChild(this.MBframe);
		this.MBwrapper.appendChild(this.MBwindow);
		
		this._insertAfter(this.MBoverlay, this.MBwrapper, this.pageContent);
		
		this.isInitialized = true;
	},
	
	show: function(title, url, options) {
		this.title = title;
		this.url = url;
		this.setOptions(options);
		
		if(!this.isInitialized)
			this._init();
		
		// Initial scrolling position of the window. To be used for remove scrolling effect during ModalBox appearing
		this.initScrollX = window.pageXOffset || document.body.scrollLeft || document.documentElement.scrollLeft;
		this.initScrollY = window.pageYOffset || document.body.scrollTop || document.documentElement.scrollTop;
		
		if (navigator.appVersion.match(/\bMSIE\b/))
		{
			document.body.style.position = "relative";
			document.body.style.height = document.documentElement.clientHeight + "px";
			document.body.style.width = document.documentElement.clientWidth + "px";
		}
		document.body.style.overflow = 'hidden';
	
		if(!this.MBcaption.childNodes)
			this.MBcaption.appendChild(document.createTextNode(title));
		else
			this.MBcaption.innerHTML = title;
		
		if(this.MBwindow.style.display == "none") {  // First modal box appearing
			this._toggleSelects();
			this._setOverlay();
			this._setWidth();
			this._setPosition();
			Effect.SlideDown(this.MBwindow, {duration:0.75, afterFinish: this.loadContent.bind(this) } );
		} else {
			this.currentDims = [this.MBwindow.offsetWidth, this.MBwindow.offsetHeight];
			new Effect.ScaleBy(this.MBwindow, 
							   (this.options.width - this.currentDims[0]), //New width calculation
							   (this.options.height - this.currentDims[1]), //New height calculation
								{afterFinish: this._loadAfterResize.bind(this), 
								beforeStart: function(effect) { effect.element.firstChild.childNodes[1].innerHTML = "Loading..."; effect.element.firstChild.childNodes[1].style.height = "auto"; } 
			});
		}
		
		this._setWidthAndPosition = this._setWidthAndPosition.bindAsEventListener(this);
		this.kbdHandler = this.kbdHandler.bindAsEventListener(this);
		
		Event.observe(window, "resize", this._setWidthAndPosition );
		Event.observe(document, "keypress", this.kbdHandler );
	},
	
	hide: function(argument) {
		if(argument) Event.stop(argument); // If an event given as an argument, stop this event
		Effect.SlideUp(this.MBwindow, {duration:0.35, afterFinish: this._deinit.bind(this) } );
	},
	
	loadContent: function ()
	{
		var url = this.url;
		var MBcontent = this.MBcontent;
		var self = this;
		
		var myAjax = new Ajax.Request( url, { method: 'get', onComplete: 
			function(originalRequest) {
				MBcontent.innerHTML = originalRequest.responseText;
				originalRequest.responseText.evalScripts();
				// Seeting focus on first 'focusable' element in content (input, select, textarea, link or button)
				self.moveFocus();
			} 
		});
	},
	
	moveFocus: function() {
		// Move focus on content area
		this.MBcontent.focus();
		
		// If the ModalBox frame containes form elements or links, first of them will bi focused after loading content
		// Trying to find focusable element in loaded content
		this.focusEl = $$("#MB_content input", "#MB_content textarea", "#MB_content select", "#MB_content button", "#MB_content a", "#MB_close").first();
		// Set focus on the first element
		this.focusEl.focus();
	},
	
	_loadAfterResize: function() {
		this._setWidth();
		this._setPosition();
		this.loadContent();
	},
	
	kbdHandler: function(e) {
		switch(e.keyCode)
		{
			case Event.KEY_TAB:
				// Find last 'focusable' element in ModalBox content to catch event on it. If no elements found, uses close ModalBox button
				this.lastFocusEl = $$("#MB_close", "#MB_content input", "#MB_content textarea", "#MB_content select", "#MB_content button", "#MB_content a").last();
				if(Event.element(e) == this.lastFocusEl)
				{
					Event.stop(e);
					this.moveFocus();
				}
			break;
			
			case Event.KEY_ESC:
				this.hide(e);
			break;
		}
	},
	
	_deinit: function()
	{	
		this._toggleSelects();
		Event.stopObserving(this.MBclose, "click", this.hide );
		if(this.options.overlayClose)
			Event.stopObserving(this.MBoverlay, "click", this.hide );
		Event.stopObserving(window, "resize", this._setWidthAndPosition );
		Event.stopObserving(document, "keypress", this.kbdHandler );
		
		Effect.toggle(this.MBoverlay, 'appear', {duration: 0.35, afterFinish: this._removeElements.bind(this) });
	},
	
	_removeElements: function () {
		if (navigator.appVersion.match(/\bMSIE\b/))
		{
			document.body.style.position = "";
			document.body.style.height = "";
			document.body.style.width = "";
		}
		document.body.style.overflow = "auto";
		window.scrollTo(this.initScrollX, this.initScrollY);
		Element.remove(this.MBoverlay);
		Element.remove(this.MBwrapper);
		this.isInitialized = false;
	},
	
	_setOverlay: function () {
		var array_page_size = this._getWindowSize();
		if((navigator.userAgent.toLowerCase().indexOf("firefox") != -1))
			this.MBoverlay.style.width = "100%";
		else
			this.MBoverlay.style.width = array_page_size[0] + "px";
		
		var max_height = Math.max(this._getScrollTop() + array_page_size[1], this._getScrollTop() + this.options.height + 30);
		this.MBoverlay.style.height = max_height + "px";
	},
	
	_setWidth: function () {
		var array_page_size = this._getWindowSize();
		
		//Set size
		this.MBwrapper.style.width = this.options.width + 10 +"px";
		this.MBwindow.style.width = this.options.width + "px";
		
		this.MBwrapper.style.height = this.options.height + "px";
		this.MBwindow.style.height = this.options.height + "px";
		this.MBcontent.style.height = this.options.height - 42 + "px";
	},
	
	_setPosition: function () {
		var array_page_size = this._getWindowSize();
		this.MBwrapper.style.left = ((array_page_size[0] - this.MBwrapper.offsetWidth) / 2 ) + "px";
		this.MBwindow.style.left = "0px";
		this.MBwrapper.style.top = this._getScrollTop() + "px";
	},
	
	_setWidthAndPosition: function () {
		this._setOverlay();
		this._setPosition();
	},
	
	_getWindowSize: function (){
		var window_width, window_height;
		if (self.innerHeight) {	// all except Explorer
			window_width = self.innerWidth;
			window_height = self.innerHeight;
		} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
			window_width = document.documentElement.clientWidth;
			window_height = document.documentElement.clientHeight;
		} else if (document.body) { // other Explorers
			window_width = document.body.clientWidth;
			window_height = document.body.clientHeight;
		}
		return [window_width, window_height];
	},
	
	_insertAfter: function(parentNodeLink, insertion, insertionNodeReference) {
		return parentNodeLink.parentNode.insertBefore(insertion, insertionNodeReference);
	},
	
	_getScrollTop: function () {
		//From: http://www.quirksmode.org/js/doctypes.html
		var theTop;
		if (document.documentElement && document.documentElement.scrollTop)
			theTop = document.documentElement.scrollTop;
		else if (document.body)
			theTop = document.body.scrollTop;
		return theTop;
	},
	
	// For IE browsers -- hiding all SELECT elements
	_toggleSelects: function() {
		if (navigator.appVersion.match(/\bMSIE\b/))
		{
			var selectsList = this.pageContent.getElementsByTagName("select");
			var selects = $A(selectsList);
			selects.each( function(select) { 
				select.style.visibility = (select.style.visibility == "") ? "hidden" : "";
			});
		}
	}
}

Object.extend(Modalbox, Modalbox.Methods);

Effect.ScaleBy = Class.create();
Object.extend(Object.extend(Effect.ScaleBy.prototype, Effect.Base.prototype), {
  initialize: function(element, byWidth, byHeight) {
    this.element = $(element)
    var options = Object.extend({
	  scaleFromTop: true,
      scaleMode: 'box',        // 'box' or 'contents' or {} with provided values
      scaleByWidth: byWidth,
	  scaleByHeight: byHeight
    }, arguments[3] || {});
    this.start(options);
  },
  setup: function() {
    this.elementPositioning = this.element.getStyle('position');
      
    this.originalTop  = this.element.offsetTop;
    this.originalLeft = this.element.offsetLeft;
	
    this.dims = null;
    if(this.options.scaleMode=='box')
      this.dims = [this.element.offsetHeight, this.element.offsetWidth];
	 if(/^content/.test(this.options.scaleMode))
      this.dims = [this.element.scrollHeight, this.element.scrollWidth];
    if(!this.dims)
      this.dims = [this.options.scaleMode.originalHeight,
                   this.options.scaleMode.originalWidth];
	  
	this.deltaY = this.options.scaleByHeight;
	this.deltaX = this.options.scaleByWidth;
  },
  update: function(position) {
    var currentHeight = this.dims[0] + (this.deltaY * position);
	var currentWidth = this.dims[1] + (this.deltaX * position);
	
    this.setDimensions(currentHeight, currentWidth);
  },

  setDimensions: function(height, width) {
    var d = {};
    d.width = width + 'px';
    d.height = height + 'px';
    
	var topd  = (height - this.dims[0])/2;
	var leftd = (width  - this.dims[1])/2;
	if(this.elementPositioning == 'absolute') {
		if(!this.options.scaleFromTop) d.top = this.originalTop-topd + 'px';
		d.left = this.originalLeft-leftd + 'px';
	} else {
		if(!this.options.scaleFromTop) d.top = -topd + 'px';
		d.left = -leftd + 'px';
	}
    this.element.setStyle(d);
  }
});