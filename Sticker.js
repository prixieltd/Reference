(function(window) {
	'use strict';
	
	var
		PX_PER_CM = 5,
		MOUSE_EVENTS = ['down', 'move', 'up', 'leave']
	;
	
	var Sticker = function(canvas, width, height) {
		if (!(this instanceof Sticker)) return new (Function.prototype.bind.apply(Sticker, [null].concat(Array.prototype.slice.call(arguments))));
		
		for (var i = 0, il = MOUSE_EVENTS.length; i < il; i++) {
			canvas.addEventListener('mouse' + MOUSE_EVENTS[i], this['onmouse' + MOUSE_EVENTS[i]].bind(this));
		}
		
		this.canvas = canvas;
		this.ctx = canvas.getContext('2d');
		
		this.transformImage = new Image();
		this.transformImage.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAdklEQVQYV2NkSNnyn4EIwAhS+H+2N16ljKlbGeAKQRwYsFcTYjhQagnnY1U4P0GPIcFaFsUGDIUgRYkLLjHAFDdsusXQ4KfGgKLQofs42LoFRx/DFYM0gdyPohDZLphikBhOhSDrGjffhuvDayJ6WMGtJiK8GQBruFPxBTGpagAAAABJRU5ErkJggg==';
		this.transformImageSize = 10;
		
		this.entries = [];
		this.selected = null;
		this.dragged = null;
		this.transfromSelected = false;
		
		this.resize(width, height);
	};
	
	Sticker.prototype = {
		constructor: Sticker,
		
		inverted: false,
		flipped: false,
		
		onmousedown: function(e) {
			var x = this.flipped ? this.width - e.layerX : e.layerX,
				y = e.layerY,
				target;
			
			if ((target = this.getTarget(x, y)) !== null) {
				this.select(target);
				this.selected.dragStartX = x - this.selected.x;
				this.selected.dragStartY = y - this.selected.y;
				
				if (!this.selected.transformArea(x, y, this.transformImageSize)) {
					this.dragged = target;
					this.transfromSelected = false;
				} else {
					this.transfromSelected = true;
				}
			} else {
				this.deselect();
			}

			this.update();
		},
		
		onmousemove: function(e) {
			var x = this.flipped ? this.width - e.layerX : e.layerX,
				y = e.layerY;
			
			if (this.dragged !== null) {
				this.dragged.x = x - this.dragged.dragStartX;
				this.dragged.y = y - this.dragged.dragStartY;
				
				this.update();
			} else if (this.transfromSelected && this.selected !== null) {
				if (this.selected instanceof imageEntry) {
					if (x > this.selected.x + this.transformImageSize) this.selected.width = x - this.selected.x;
					if (y > this.selected.y + this.transformImageSize) this.selected.height = y - this.selected.y;
				} else if (y > this.selected.y + this.transformImageSize) {
					this.selected.height = y - this.selected.y;
					this.selected.size = this.selected.height + 'px';
					this.selected.setTextStyle();
				}
				
				this.update();
			}
		},
		
		onmouseup: function() {
			this.dragged = null;
			this.transfromSelected = false;
		},
		
		onmouseleave: function() {
			// this.dragged = null;
		},
		
		getTarget: function(x, y) {
			// loop backward for get the top entry
			for (var i = this.entries.length - 1; i >= 0; i--) {
				if (this.entries[i].accept(x, y)) return this.entries[i];
			}
			
			return null;
		},
		
		select: function(entry) {
			this.deselect();
			this.selected = entry;
			this.selected.borderWidth = 1;
			
			if (typeof this.onselect === 'function') this.onselect();
		},
		
		deselect: function() {
			if (this.selected !== null) {
				this.selected.borderWidth = 0;
				this.selected = null;
				this.update();
			}
		},
		
		push: function(entry) {
			this.entries.push(entry);
			this.select(entry);
			this.update();
		},
		
		add: function(type, data) {
			this.push(type === 'text' ? new textEntry(this.ctx, data) : new imageEntry(this.ctx, data));
		},
		
		remove: function(entry) {
			var index = this.entries.indexOf(entry);
			
			if (index !== -1) {
				this.entries.splice(index, 1);
				this.deselect();
			}
		},
		
		resize: function(width, height) {
			this.ctx.save();
			
			this.width = this.canvas.width = +width || 0;
			this.height = this.canvas.height = +height || 0;
			
			this.ctx.restore();
			
			this.update();
		},
		
		forwardTarget: function() {
			this.order(this.entries.length - 1);
		},
		
		backwardTarget: function() {
			this.order(0);
		},
		
		order: function(o) {
			var index, entry;
			
			if (this.selected !== null) {
				index = this.entries.indexOf(this.selected);
				
				if (index !== -1) {
					entry = this.entries.splice(index, 1)[0];
					this.entries.splice(o, 0, entry);
					
					this.update();
				}
			}
		},
		
		update: function() {
			this.clear();
			
			this.ctx.save();
			this.ctx.beginPath();
			
			if (this.flipped) {
				this.ctx.translate(this.width, 0);
				this.ctx.scale(-1, 1);
			}
			
			this.each(this.render.bind(this));
			
			this.ctx.closePath();
			this.ctx.restore();
			
			if (this.inverted) {
				var imgData = this.ctx.getImageData(0, 0, this.width, this.height);
				
				for (var i = 0, il = imgData.data.length; i < il; i += 4) {
					imgData.data[i] = 255 - imgData.data[i];
					imgData.data[i + 1] = 255 - imgData.data[i + 1];
					imgData.data[i + 2] = 255 - imgData.data[i + 2];
				}
				
				this.ctx.putImageData(imgData, 0, 0);
			}
		},
		
		render: function(entry) {
			entry.draw();
			entry.drawBorder();
			
			if (this.selected === entry) {
				this.ctx.drawImage(
					this.transformImage,
					0, 0,
					this.transformImage.width, this.transformImage.height,
					entry.x + entry.width - (entry.width > 0 ? this.transformImageSize : 0),
						entry.y + entry.height - (entry.height > 0 ? this.transformImageSize : 0),
					this.transformImageSize, this.transformImageSize
				);
			}
		},
		
		clear: function() {
			this.ctx.clearRect(0, 0, this.width, this.height);
		},
		
		each: function(callback) {
			this.entries.forEach(callback);
		},
		
		flip: function() {
			var w = this.width;
			
			this.flipped = !this.flipped;
			this.update();
		},
		
		invert: function() {
			this.inverted = !this.inverted;
			this.update();
		},
		
		setBg: function(bgColor) {
			this.canvas.style.background = bgColor;
		},
		
		setFontType: function(font) {
			textEntry.prototype.font = font;
			
			if (this.selected instanceof textEntry) {
				this.selected.font = font;
				this.selected.setTextStyle();
				this.update();
			}
		},
		
		setFontSize: function(size) {
			size = +size;
			
			if (size > this.transformImageSize) {
				textEntry.prototype.size = size + 'px';
				
				if (this.selected instanceof textEntry) {
					this.selected.height = size;
					this.selected.size = size + 'px';
					this.selected.setTextStyle();
					this.update();
				}
			}
		},
		
		setFontColor: function(color) {
			textEntry.prototype.color = color;
			
			if (this.selected instanceof textEntry) {
				this.selected.color = color;
				this.update();
			}
		},
		
		setFontBold: function(color) {
			if (this.selected instanceof textEntry) {
				this.selected.bold = !this.selected.bold;
				this.update();
			}
			
			textEntry.prototype.bold = !textEntry.prototype.bold;
		},
		
		setFontItalic: function(color) {
			if (this.selected instanceof textEntry) {
				this.selected.italic = !this.selected.italic;
				this.update();
			}
			
			textEntry.prototype.italic = !textEntry.prototype.italic;
		},
		
		setFontUnderline: function(color) {
			if (this.selected instanceof textEntry) {
				this.selected.underline = !this.selected.underline;
				this.update();
			}
			
			textEntry.prototype.underline = !textEntry.prototype.underline;
		}
	};

	var Entry = function(layer) {
		this.layer = layer;
	};
	
	Entry.prototype = {
		constructor: Entry,
		
		x: 15,
		y: 15,
		
		width: 0,
		height: 0,
		
		borderWidth: 0,
		borderColor: '#000000',
		borderStyle: 'solid',
		
		get realX() {
			return this.x + (this.width < 0 ? this.width : 0);
		},
		
		get realY() {
			return this.y + (this.height < 0 ? this.height : 0);
		},
		
		get realXE() {
			return this.x + (this.width < 0 ? 0 : this.width);
		},
		
		get realYE() {
			return this.y + (this.height < 0 ? 0 : this.height);
		},

		accept: function(x, y) {
			// return x >= this.x && y >= this.y && x <= this.x + this.width && y <= this.y + this.height;
			return	x >= this.realX &&
					y >= this.realY &&
					x <= this.realXE &&
					y <= this.realYE;
		},
		
		transformArea: function(x, y, size) {
			return	x >= this.x + this.width - (this.width > 0 ? size : 0) &&
					y >= this.y + this.height - (this.height > 0 ? size : 0) &&
					x <= this.x + this.width + (this.width < 0 ? size : 0) &&
					y <= this.y + this.height + (this.height < 0 ? size : 0);
		},
		
		clone: function() {
			var clone = this instanceof imageEntry ? new imageEntry(this.layer, this.image) : new textEntry(this.layer, this.text),
				prop;
			
			for (prop in this) {
				if (Object.prototype.hasOwnProperty.call(this, prop)) clone[prop] = this[prop];
			}
			
			clone.x += 15;
			clone.y += 15;
			
			return clone;
		},
		
		drawBorder: function() {
			if (this.borderWidth > 0) {
				this.layer.strokeStyle = this.borderColor;
				this.layer.strokeRect(this.x, this.y, this.width, this.height);
			}
		},
		
		transform: function() {
			// todo
		}
	};
	
	var textEntry = function(layer, text) {
		Entry.call(this, layer);
		this.text = text;
		this.setTextStyle();
		this.setWidth();
		this.height = window.parseInt(this.size, 10) || 0;
		
		this.font = this.font;
		this.size = this.size;
		this.color = this.color;
		this.bold = this.bold;
		this.italic = this.italic;
		this.underline = this.underline;
	};
	
	textEntry.prototype = Object.create(Entry.prototype);
	textEntry.constructor = Entry;
	
	Object.assign(textEntry.prototype, {
		size: '16px',
		color: '#000000',
		font: 'Arial',
		bold: false,
		italic: false,
		underline: false,
		hAlign: 'left',
		vAlign: 'hanging'
	});
	
	textEntry.prototype.setTextStyle = function() {
		var fontStyle = '';
		
		if (this.bold) fontStyle += 'bold ';
		if (this.italic) fontStyle += 'italic ';
		
		fontStyle += this.size + ' ';
		fontStyle += this.font;
		
		this.layer.font = fontStyle;
		this.layer.fillStyle = this.color;
		this.layer.textAlign = this.hAlign;
		this.layer.textBaseline = this.vAlign;
		
		this.setWidth();
	};
	
	textEntry.prototype.setWidth = function() {
		return this.width = this.layer.measureText(this.text).width;
	};
	
	textEntry.prototype.draw = function() {
		this.setTextStyle();
		this.layer.fillText(this.text, this.x, this.y);
		
		if (this.underline) {
			this.layer.beginPath();
			
			this.layer.moveTo(this.x, this.y + this.height * .8);
			this.layer.lineTo(this.x + this.width, this.y + this.height * .8);
			this.layer.strokeStyle = this.color;
			this.layer.stroke();
			
			this.layer.closePath();
		}
	};
	
	var imageEntry = function(layer, image) {
		Entry.call(this, layer);
		this.image = image;
		
		this.width = image.width;
		this.height = image.height;
	};
	
	imageEntry.prototype = Object.create(Entry.prototype);
	imageEntry.constructor = Entry;
	
	imageEntry.prototype.draw = function() {
		this.layer.drawImage(this.image, 0, 0, this.image.width, this.image.height, this.x, this.y, this.width, this.height);
	};
	
	window.Sticker = Sticker;
	window.textEntry = textEntry;
	window.imageEntry = imageEntry;
}(window, (function() {
	if (Object.assign === undefined) {
		Object.assign = function(target) {
			for (var i = 1, il = arguments.length, prop; i < il; i++) {
				for (prop in arguments[i]) {
					if (Object.prototype.hasOwnProperty.call(arguments[i], prop)) target[prop] = arguments[i][prop];
				}
			}
		};
	}
}())));
