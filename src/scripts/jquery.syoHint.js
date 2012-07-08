/*!
 * jQuery syoHint v1.0.10
 * https://github.com/Syonet/jQuery-syo-Hint
 */
(function($, window, document, undefined) {
	
	var $window = $(window),
		$document = $(document);
	
	$.fn.autoHint = function(obj) {
		var instance = $window.data("syoHint"),
			mouseIn = false,
			_hide = function() {
				instance.setContent("").hide();
			},
			_show = function(e, title) {
				instance.setContent( title.replaceAll("\n", "<br />") ).show(e);
			};
		
		if(!instance) {
			instance = $window.syoHint(obj);
		}
		
		return $(this).on("mousemove.syo-hint-event-move", function(e) {
			
			if( mouseIn === true && $(e.target).closest("[title]").length === 0 ) {
				mouseIn = false;
				_hide();
			}
			
		}).on( "mousemove.syo-hint-event", "[title]", function(e) {
			var attrTitle,
				$this = $(this),
				title = $this.data("title");
			
			mouseIn = true;
			
			$this.one("click.syo-hint-check", function() {
				if($this.is(":hidden")) {
					$this.trigger("mouseleave.syo-hint-event");
				}
			});
			
			if( !title || ((attrTitle = $this.attr("title")) && title !== attrTitle) ) {
				title = attrTitle || $this.attr("title");
				$this.attr( "title", "" );
				$this.data( "title", title );
			}
			
			if(title) {
				_show(e, title);
			}
		}).on( "click.syo-hint-event", "[title]", function(e) {
			if( mouseIn === true ) {
				$(this).trigger("mousemove.syo-hint-event");
			}
		}).on( "mouseleave.syo-hint-event", "[title]", function() {
			var $this = $(this),
				title = $this.data("title");
			
			mouseIn = false;
			
			if(title) {
				$this.attr( "title", title );
				$this.removeData("title");
			}
			
			$this.unbind("click.syo-hint-check");
			
			_hide();
		});
	};
	
	$.fn.hint = function( msg, args ) {
		var instance,
			$this = this,
			documentData = $document.data("syoHint"),
			options = $.extend( { eventType: "live" }, args );
		
		if(!documentData) {
			instance = $document.syoHint(args);
		} else {
			instance = documentData;
		}
		
		$this[options.eventType]( "mouseenter", function(e) {
			instance.setContent(msg).show(e);
		})[options.eventType]( "mouseleave", function(e) {
			instance.setContent('').hide();
		});
	return instance;
	};
	
	$.fn.syoHint = function(args) {
		var instance,
			$selector = this,
			options = {
				hintContent: undefined,
				content: undefined,
				onShow: undefined,
				timeout: undefined,
				type: 0,
				position: "bottom right"
			};
		
		if( $.isPlainObject(args) ) {
			$.extend( options, args );
		} else if( args !== undefined ) {
			options.content = args;
		}
		
		options.hintContent = options.hintContent || options.elemento;
		
		if( options.type === 0 ) {
			options.timeout = 0;
		}
		
		$selector.each(function( index, domElement ) {
			var _instance,
				$t = $(domElement);
			
			if( _instance = $t.data("syoHint") ) {
				_instance.destroy();
				$t.removeData("syoHint");
			}
		});
		
		if( options.type === 2 ) {
			$selector.each(function( index, domElement ) {
				var $this,
					tag = domElement.nodeName.toUpperCase();
				
				if(tag != "INPUT" && tag != "TEXTAREA" && tag != "SELECT") {
					$this = $(domElement);
					$this.data( "old_cursor", $this.css("cursor") );
					$this.css( "cursor", "pointer" );
				}
			});
		}
		
		instance = new syoHint( $selector, options );
		$selector.data( "syoHint", instance );
		
	return instance.start();
	};
	
	function syoHint( $selector, options ) {
		var TIMEOUT,
			CURSOR_X = 0,
			CURSOR_Y = 0,
			UID = geraUID("hint_"),
			EVENT_NAMESPACE = "syoHint",
			click_origin = "mouse",
			instance = this;
		
		function option( prop, val ) {
			var ret = undefined;
			if( typeof prop === "string" ) {
				if( val === undefined ) {
					ret = options[prop];
				} else {
					ret = options[prop] = val;
				}
			}
		return ret;
		};
		
		function getStructure() {
			return $( "<div />", {
				id: UID,
				css: {
					position: "absolute",
					border: "1px solid gray",
					backgroundColor: "#f7f6f6",
					color: "#282828",
					padding: "3px",
					display: "table-cell",
					verticalAlign: "middle",
					fontWeight: "normal",
					zIndex: 20000,
					borderRadius: "3px",
					boxShadow: "0 0 3px rgb(160, 160, 160)"
				}
			}).hide().prependTo(document.body);
		};
		
		function actionPerformedClearTimeout(e) {
			if(TIMEOUT) {
				clearTimeout(TIMEOUT);
			}
		};
		
		function actionPerformedHide(e) {
			var timeout = option("timeout");
			if( typeof timeout === "number" ) {
				Hide(timeout);
			}
		};
		
		function actionPerformedHideNow(e) {
			var $target = $(e.target),
				$handlers = $selector.add( option("hintContent") );
			
			//Cannot close the hint if clicked anywhere inside the structure or in the selector
			if( $target.closest($handlers).length === 0 ) {
				Hide();
			}
		};
		
		function Hide(timeout) {
			var $hintStructure = option("hintContent");
			if( $hintStructure && $hintStructure.length ) {
				if( timeout && timeout > 0 ) {
					TIMEOUT = setTimeout( Hide, timeout );
				} else {
					$hintStructure.hide();
				}
			}
		};
		
		function actionPerformedKeyDown(e) {
			click_origin = "keyboard";
		}
		
		function updateCursor(e) {
			var offset;
			
			if( click_origin == "keyboard" ) {
				offset = $(this).offset();
				CURSOR_X = Math.floor( offset.left );
				CURSOR_Y = Math.floor( offset.top );
				click_origin = "mouse"; //reset the origin
			} else {
				CURSOR_X = e.pageX;
				CURSOR_Y = e.pageY;
			}
		};
		
		//content property can accept many types
		function checkContent( content, e ) {
			var ret;
			if( $.isFunction(content) ) {
				content = content.call( instance, e );
				if(( content instanceof jQuery && content.closest(document.body).length === 0 )) {
					ret = content;
				} else if( typeof content === "string" ) {
					ret = content;
				} else {
					ret = undefined;
				}
			} else if( typeof content === "string" ) {
				ret = content;
			}
		return ret;
		};
		
		function assignPosition(position) {
			var cssObj,
				$document = $(document),
				documentHeight = $document.height(),
				documentWidth = $document.width(),
				position = option("position").split(" "),
				$hintStructure = option("hintContent"),
				hintWidth = Math.round( $hintStructure.width() || 0 ),
				hintOuterWidth = Math.round( $hintStructure.outerWidth() || 0 ),
				hintOuterHeight = Math.round( $hintStructure.outerHeight() || 0 ),
				defs_Y = {
					"middle": - (hintOuterHeight / 2),
					"top": - (hintOuterHeight + 15),
					"middle": - (hintOuterHeight / 2),
					"bottom": 15
				},
				defs_X = {
					"center": - (hintWidth / 2),
					"left": - (hintOuterWidth + 15),
					"center": - (hintWidth / 2),
					"right": 15
				};
			
			if( !(position[0] in defs_Y) ) {
				position[1] = position[0];
				position[0] = "bottom";
			}
			
			if( !(position[1] in defs_X) ) {
				position[1] = "right";
			}
			
			cssObj = getCSSObj( position, hintOuterHeight, hintOuterWidth, defs_X, defs_Y, documentHeight, documentWidth );
			
			$hintStructure.css(cssObj);
		};
		
		function getCSSObj( position, hintOuterHeight, hintOuterWidth, defs_X, defs_Y, documentHeight, documentWidth ) {
			var ret,
				leftPosition = assignPosition_X( position[1], hintOuterWidth, defs_X, documentWidth ),
				topPosition = assignPosition_Y( position[0], hintOuterHeight, defs_Y, documentHeight );
			
			/*
			 * If the hint is within the range of the mouse pointer it means the 'middle' position has been set along with 'center' position,
			 * if that happens calculate the leftPosition again but ignoring the 'center' position
			 */
			if( leftPosition <= CURSOR_X && (leftPosition + hintOuterWidth) >= CURSOR_X ) {
				if( topPosition <= CURSOR_Y && (topPosition + hintOuterHeight) >= CURSOR_Y ) {
					leftPosition = assignPosition_X( position[1], hintOuterWidth, defs_X, documentWidth, ["center"] );
				}
			}
			
			ret = {
				left: leftPosition,
				top: topPosition
			};
		
		return ret;
		};
		
		function assignPosition_X( position, hintOuterWidth, defs_X, documentWidth, lastPositions ) {
			var prop, len,
				leftValue = CURSOR_X + defs_X[position],
				rightLimit = documentWidth - hintOuterWidth,
				res = leftValue;
			
			lastPositions = lastPositions || [];
			len = 0;
			
			//get the total number of properties to compare with the current lastPositions
			for( prop in defs_X ) {
				len++;
			}
			
			if( lastPositions.length < len ) {
				if( leftValue >= rightLimit || leftValue <= 0 ) {
					$.each (defs_X, function(prop, val) {
						if( prop != position && !lastPositions.contains(prop) ) {
							lastPositions.push(position);
							res = assignPosition_X( prop, hintOuterWidth, defs_X, documentWidth, lastPositions );
							return false;
						}
					});
				}
			}
		return res;
		};
		
		function assignPosition_Y( position, hintOuterHeight, defs_Y, documentHeight, lastPositions ) {
			var prop, len,
				topValue = CURSOR_Y + defs_Y[position],
				bottomLimit = documentHeight - hintOuterHeight,
				res = topValue;
			
			if( CURSOR_Y > documentHeight ) {
				res = bottomLimit;
			} else if( CURSOR_Y < 0 ) {
				res = 0;
			} else {
				lastPositions = lastPositions || [];
				len = 0;
				
				//get the total number of properties to compare with the current lastPositions
				for( prop in defs_Y ) {
					len++;
				}
				
				if( lastPositions.length < len ) {
					
					if( topValue >= bottomLimit || topValue <= 0 ) {
						$.each( defs_Y, function( prop, val ) {
							
							//test recursively other positions in the same orientation
							if( prop != position && !lastPositions.contains(prop) ) {
								lastPositions.push(position);
								res = assignPosition_Y( prop, hintOuterHeight, defs_Y, documentHeight, lastPositions );
								return false;
							}
							
						});
					}
				
				}
			}
		return res;
		};
		
		function actionPerformedShow(e) {
			var content, onShow, fnContent,
				type = option("type"),
				$hintStructure = option("hintContent"),
				isHidden = $hintStructure.is(":hidden");
			
			if( type === 0 || isHidden ) {
				content = option("content");
				onShow = option("onShow");
				
				if( $hintStructure.contents().length === 0 ) {
					if( content instanceof jQuery && content.length > 0 ) {
						
						//Consider only the first element in the matched set
						content = option( "content", content.slice(0, 1) );
						
						$hintStructure.append( content.contents().clone(true, true) );
						
						//Clear the original content element to avoid duplication
						content.empty();
					} else if( (fnContent = checkContent( content, e )) !== undefined ) {
						$hintStructure.append(fnContent);
					}
				} else if( (fnContent = checkContent( content, e )) !== undefined ) {
					$hintStructure.empty().append(fnContent);
				}
				
				if( ($.isFunction(onShow) && onShow() === false) || !$hintStructure.contents().length ) {
					$hintStructure.hide();
				} else {
					updateCursor.call( this, e );
					assignPosition();
					
					if(isHidden) {
						$hintStructure.stop( true, true ).css({ opacity: 0 }).show().animate({ opacity: 1 }, "fast");
					}
				}
			}
		return instance;
		};
		
		instance.start = function() {
			var $hintStructure, $handlers, type;
			if( $selector.length ) {
				$hintStructure = option("hintContent") || option( "hintContent", getStructure() );
				$handlers = $hintStructure.add($selector);
				type = option("type");
				
				if( type === 2 ) {
					$selector.bind( "click." + EVENT_NAMESPACE, actionPerformedShow );
					$selector.bind( "keydown." + EVENT_NAMESPACE, actionPerformedKeyDown );
					$(document).bind( "click." + EVENT_NAMESPACE, actionPerformedHideNow );
				} else {
					$selector.bind( "mousemove." + EVENT_NAMESPACE, actionPerformedShow );
				}
				
				$handlers.bind( "mouseenter." + EVENT_NAMESPACE, actionPerformedClearTimeout );
				$handlers.bind( "mouseleave." + EVENT_NAMESPACE, actionPerformedHide );
			}
		return instance;
		};
		
		instance.destroy = function() {
			var $hintStructure = option("hintContent");
			if( $hintStructure && $hintStructure.length ) {
				$hintStructure.remove();
			}
			$selector.removeData("syoHint").unbind(".syoHint")
			.each(function() {
				var $this = $(this);
				$this.css( "cursor", $this.data("old_cursor") );
				$this.removeData("old_cursor");
			});
		return instance;
		};
		instance.hide = function() {
			Hide();
			return instance;
		};
		instance.show = actionPerformedShow;
		
		instance.setPosition = function(position) {
			option( "position", position );
			return instance;
		};
		instance.setContent = function(content) {
			option( "content", content );
			return instance;
		};
		instance.setStyle = function(style, val) {
			var $hintStructure = option("hintContent");
			if( $hintStructure && $hintStructure.length ) {
				$hintStructure.css( style, (val || null) );
			}
		return instance;
		};
		
		instance.getType = function() {
			return option("type");
		};
		instance.getPosition = function() {
			return option("position");
		};
		instance.getSelector = function() {
			return $selector;
		};
		instance.getUID = function() {
			return UID;
		};
		
		//expose a few methods for testing purposes
		instance.__test__assignPosition_Y = function(e) {
			CURSOR_Y = e.pageY;
			return assignPosition_Y.apply( null, Array.prototype.slice.call(arguments, 1) );
		};
		instance.__test__assignPosition_X = function(e) {
			CURSOR_X = e.pageX;
			return assignPosition_X.apply( null, Array.prototype.slice.call(arguments, 1) );
		};
		instance.__test__updateCursor = function(clickOrigin, $element, e) {
			click_origin = clickOrigin;
			updateCursor.call( $element, e );
			return {
				CURSOR_X: CURSOR_X,
				CURSOR_Y: CURSOR_Y,
				CLICK_ORIGIN: click_origin
			};
		};
		instance.__test__getCSSObj = function(e) {
			CURSOR_X = e.pageX;
			CURSOR_Y = e.pageY;
			return getCSSObj.apply( null, Array.prototype.slice.call(arguments, 1) );
		};
		instance.__test__CLICK_ORIGIN = function() {
			return click_origin;
		};
		
		//old method name support
		instance.remove = instance.destroy;
	};
})(jQuery, window, document);
