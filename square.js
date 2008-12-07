var IN_WIDTH = 256;
var IN_HEIGHT = IN_WIDTH
var OUT_BOX_HEIGHT = 64;
var OUT_BOX_WIDTH = OUT_BOX_HEIGHT
var width = OUT_BOX_WIDTH;
var height = OUT_BOX_HEIGHT;
var in_row_bytes = IN_WIDTH / 8;
var in_box_bytes = in_row_bytes / 4;
var in_box_vert = in_row_bytes * OUT_BOX_HEIGHT;
var in_pixels;
var square_names = ['square_0', 'square_1', 'square_2', 'square_3', 'square_4', 'square_5', 'square_6', 'square_7', 'square_8', 'square_9', 'square_a', 'square_b', 'square_c', 'square_d', 'square_e', 'square_f'];
var squares_tb, squares_bt, squares_lr, squares_rl;
var g_blank = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEX///+nxBvIAAAACklEQVQIHWNgAAAAAgABz8g15QAAAABJRU5ErkJggg==';

var g_url; // id number of current square
var g_charset = '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'; // id number of current square

// this is called (exclusively) by the html page's body onload
function load(square) {
	squares_init()
	get_and_render(square);
}

function get_and_render(url) {
	g_url = url; // set the global

	sendRequest('binary?url=' + url, call_me);
}

function call_me(rec) {
	if(animating) {
		data_ready = true;
		data_that_is_ready = rec.responseText;
	} else {
		squares(rec.responseText);
	}
}

function make_square(square_num) {
	var index;
	var i;
	var b;
	var bits;

	index = pixels_start;
	in_index = in_box_bytes * (square_num % 4);
	in_index += Math.floor(square_num / 4) * in_box_vert;
	
	// 'i' counts the number of bytes of pixels (not counting filter bytes) to output
	for(i = 0; i < height*width/8; i++) {
		if(i % (width/8) == 0) {
			// filter type at the begining of each scanlines
			if(i) {
				in_index += in_row_bytes - in_box_bytes;
			}
			png_array[index++] = 0;
		}
		png_array[index++] = (in_pixels[in_index++]);
	}
}

function squares_init() {
	in_pixels = new Array(IN_WIDTH * IN_WIDTH / 8);
	png_init(width, height);
	var frame = $('#square_frame');
	var i;
	var t;
	var l;
	var q;
	var square;
	var lr;

	squares_rl = new Array(16);
	squares_lr = new Array(16);
	squares_tb = new Array(16);
	squares_bt = new Array(16);

	frame.empty();
	for(i = 0; i < 16; ++i) {
		t = Math.floor(i / 4) * 128;
		l = (i % 4) * 128;
		q = Math.floor(l / 256) + (2 * Math.floor(t / 256));
		frame.append('<img class="square" id="' + square_names[i] + '" src="' + g_blank + '" style="top: ' + t + 'px; left: ' + l + 'px" />');
		square = $('#' + square_names[i]);
		if(q == 0) {
			square.bind('click', zoom_tl);
		} else if(q == 1) {
			square.bind('click', zoom_tr);
		} else if(q == 2) {
			square.bind('click', zoom_bl);
		} else {
			square.bind('click', zoom_br);
		}
		square = square.get(0);
		squares_tb[i] = square;
		squares_bt[15 - i] = square;
		lr = ((i & 0x3) << 2) | (i >> 2);
		squares_lr[lr] = square;
		squares_rl[15 - lr] = square;
	}
}

function render_square(square_num) {
	var png;

	make_square(square_num);
	png = make_png();
	tag(square_names[square_num]).src = 'data:image/png;base64,' + png;
	tag(square_names[square_num]).style.width = '128px';
	tag(square_names[square_num]).style.height = '128px';
}

// put them back in their original size/location
function unzoom_squares() {
	var i;
	for(i = 0; i < 16; ++i) {
		squares_tb[i].style.height = 128 + 'px';
		squares_tb[i].style.width = 128 + 'px';
		squares_tb[i].style.top = Math.floor(i / 4) * 128 + 'px';
		squares_tb[i].style.left = (i % 4) * 128 + 'px';
	}
}


function squares(data) {
	var square_num;
	var i;

	// g_url  = (data.charCodeAt(0) & 0xff) << 24;
	// g_url |= (data.charCodeAt(1) & 0xff) << 16;
	// g_url |= (data.charCodeAt(2) & 0xff) << 8;
	// g_url |= (data.charCodeAt(3) & 0xff);

	for(i = 4; i < data.length; ++i) {
		in_pixels[i - 4] = (data.charCodeAt(i) & 0xff);
	}

	for(square_num = 0; square_num < 16; square_num++) {
		render_square(square_num);
	}

	unzoom_squares();
}

// parameters:
//   quadrant number 0-3
//   zoom (width of a quadrant)
function quadrant_to_offset(quadrant, zoom) {
	var x = quadrant % 2;
	var y = (quadrant - x) / 2;
	return (y * 8 * zoom) + (x * zoom);
}


// rount c down, so it's in the top left of the POWxPOW tile it's in
function quantize_url_char(c, pow) {
	c = g_charset.indexOf(c);
	var x = c % 8;
	var y = Math.floor(c / 8);

	x = Math.floor(x / pow) * pow;
	y = Math.floor(y / pow) * pow;
	return g_charset.charAt((y * 8) + x);
}

function zoom_tl() { click(0); }
function zoom_tr() { click(1); }
function zoom_bl() { click(2); }
function zoom_br() { click(3); }

function click(quadrant) {
	var letters;
	var dots;

	if(g_url == '') {
		letters = '';
		dots = '';
	} else if(g_url.length == 1) {
		letters = g_url;
		dots = '';
	} else {
		if(g_url.substr(g_url.length - 2) == '..') {
			letters = g_url.substr(0, g_url.length - 2);
			dots = '..';
		} else if(g_url.substr(g_url.length - 1) == '.') {
			letters = g_url.substr(0, g_url.length - 1);
			dots = '.';
		} else {
			letters = g_url;
			dots = '';
		}
	}

	if(quadrant == 'out') {
		if(g_url == '') {
			get_and_render(g_url);
		} else if(dots == '..') {
			get_and_render(letters.substr(0, letters.length - 1));
		} else {
			var base = letters.substr(0, letters.length - 1);
			var last = letters.substr(letters.length - 1);
			if(dots == '') {
				last = quantize_url_char(last, 2);
			} else { // dots == '.'
				last = quantize_url_char(last, 4);
			}
			get_and_render(base + last + dots + '.');
		}
	} else {
		if(dots == '') {
			get_and_render(letters + g_charset.charAt(quadrant_to_offset(quadrant, 4))  + '..');
		} else {
			var last = letters.substr(letters.length - 1);
			var offset = g_charset.indexOf(last);
			if(dots == '..') {
				offset += quadrant_to_offset(quadrant, 2);
				get_and_render(letters.substr(0, letters.length - 1) + g_charset.charAt(offset) + '.');
			} else {
				offset += quadrant_to_offset(quadrant, 1);
				get_and_render(letters.substr(0, letters.length - 1) + g_charset.charAt(offset));
			}
		}
		animate_zoom(quadrant);
	}
}


// quadrants are numbered:
//
//  01
//  23
//
// squares are numbered:
//
//  0123
//  4567
//  89ab
//  cdef

function square_num_to_quadrant(square_num) {
	var x = square_num % 4;
	var y = square_num - x;
	return ((x>>1) & 1) | ((y>>2) & 2);
}

var ANIM_HS  = 1;
var ANIM_VS  = 2;
var ANIM_HG  = 4;
var ANIM_VG  = 8;

var HORIZ  = ANIM_VG | ANIM_HS;
var VERTI  = ANIM_VS | ANIM_HG;
var SHRINK = ANIM_VS | ANIM_HS;
var EXPAND = ANIM_VG | ANIM_HG;

var animating = false;
var animator_id;
var pixels_to_animate;
var animate_dx;
var animate_dy;
var data_ready;
var data_that_is_ready;
var animate_speed = 16;

function animate_zoom(which) {
	if(which < 2) {
		animate_dy = 1;
	} else {
		animate_dy = -1;
	}

	if(which % 2) {
		animate_dx = -1;
	} else {
		animate_dx = 1;
	}

	animating = true;
	pixels_to_animate = 128;
	animator_id = setInterval('animate_frame()', 100);
}

function animate_frame() {
	var i, j, xcur, ycur;
	var xsquares, yquares;
	var size;
	if(pixels_to_animate && pixels_to_animate < animate_speed) {
		pixels_to_animate = animate_speed;
	}

	if(pixels_to_animate == 0 || (pixels_to_animate <= animate_speed && data_ready)) {
		animating = false;
		clearInterval(animator_id);
		if(data_ready) {
			data_ready = false;
			squares(data_that_is_ready);
		}
		return;
	}
	
	pixels_to_animate -= animate_speed;

	size = 256 - pixels_to_animate; // new size

	// rows/columns move move by animate-speed + tile-size
	if(animate_dx < 0) {
		xsquares = squares_rl;
		xcur = 512 - size;
	} else {
		xsquares = squares_lr;
		xcur = 0;
	}
	if(animate_dy < 0) {
		ysquares = squares_bt;
		ycur = 512 - size;
	} else {
		ysquares = squares_tb;
		ycur = 0;
	}
	for(i = 0; i < 16; ) {
		xsquares[i].style.left = xcur + 'px';
		xsquares[i].style.width = size + 'px';
		xsquares[i].width = size;
		ysquares[i].style.top = ycur + 'px';
		ysquares[i].style.height = size + 'px';
		ysquares[i].height = size;
		i++;
		if(i % 4 == 0) {
			xcur += size * animate_dx;
			ycur += size * animate_dy;
		}
	}
}
