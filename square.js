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
var squares_tb, squares_bt, squares_lr, squares_rl;
var front_squares_tb, front_squares_bt, front_squares_lr, front_squares_rl;
var g_editor_toggle = false;
var back_squares_tb, back_squares_bt, back_squares_lr, back_squares_rl;
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

var g_z = 0;

// change which set of images are displayed
function toggle_display() {
	++g_z;
	if(g_editor_toggle) {
		setTimeout("$('.back_square').css('z-index', g_z)", 10);
		// $('.front_square').css('top', '-500px');
		// $('.front_square').hide();
	} else {
		setTimeout("$('.front_square').css('z-index', g_z)", 10);
		// $('.front_square').show();
	}
}

// change which set of images the editor works with
function toggle_editor() {
	if(g_editor_toggle) {
		g_editor_toggle = false;
		squares_rl = front_squares_rl;
		squares_lr = front_squares_lr;
		squares_bt = front_squares_bt;
		squares_tb = front_squares_tb;
	} else {
		g_editor_toggle = true;
		squares_rl = back_squares_rl;
		squares_lr = back_squares_lr;
		squares_bt = back_squares_bt;
		squares_tb = back_squares_tb;
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

	front_squares_rl = new Array(16);
	front_squares_lr = new Array(16);
	front_squares_tb = new Array(16);
	front_squares_bt = new Array(16);
	back_squares_rl = new Array(16);
	back_squares_lr = new Array(16);
	back_squares_tb = new Array(16);
	back_squares_bt = new Array(16);

	frame.empty();
	for(i = 0; i < 16; ++i) {
		frame.append('<img class="back_square" src="' + g_blank + '" style="top: -500px; left: -500px" />');
		frame.append('<img class="front_square" src="' + g_blank + '" style="top: -500px; left: -500px" />');
	}
	$('.front_square').each(function(i, dom) {
		q = Math.floor((i % 4) / 2) + (2 * Math.floor(i / 8));
		square = $(dom);
		if(q == 0) {
			square.bind('click', zoom_tl);
		} else if(q == 1) {
			square.bind('click', zoom_tr);
		} else if(q == 2) {
			square.bind('click', zoom_bl);
		} else {
			square.bind('click', zoom_br);
		}
		square = dom;
		front_squares_tb[i] = square;
		front_squares_bt[15 - i] = square;
		lr = ((i & 0x3) << 2) | (i >> 2);
		front_squares_lr[lr] = square;
		front_squares_rl[15 - lr] = square;
	});
	$('.back_square').each(function(i, dom) {
		q = Math.floor((i % 4) / 2) + (2 * Math.floor(i / 8));
		square = $(dom);
		if(q == 0) {
			square.bind('click', zoom_tl);
		} else if(q == 1) {
			square.bind('click', zoom_tr);
		} else if(q == 2) {
			square.bind('click', zoom_bl);
		} else {
			square.bind('click', zoom_br);
		}
		square = dom;
		back_squares_tb[i] = square;
		back_squares_bt[15 - i] = square;
		lr = ((i & 0x3) << 2) | (i >> 2);
		back_squares_lr[lr] = square;
		back_squares_rl[15 - lr] = square;
	});
}

function render_square(square_num) {
	var png;

	make_square(square_num);
	png = make_png();
	squares_tb[square_num].src = 'data:image/png;base64,' + png;
}

// put them back in their original size/location
function unzoom_squares() {
	var i;
	for(i = 0; i < 16; ++i) {
		squares_tb[i].style.height = 128 + 'px';
		squares_tb[i].style.width = 128 + 'px';
		squares_tb[i].style.left = (i % 4) * 128 + 'px';
		squares_tb[i].style.top = Math.floor(i / 4) * 128 + 'px';
	}
}


function squares(data) {
	var square_num;
	var i;

	for(i = 0; i < data.length; ++i) {
		in_pixels[i] = (data.charCodeAt(i) & 0xff);
	}

	toggle_editor();

	for(square_num = 0; square_num < 16; square_num++) {
		render_square(square_num);
	}

	unzoom_squares();

	toggle_display();
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

// pass:
//       c: url char
//       size: size of parent square (2, 4 or 8) relative to which you want the quadrant
function quadrant_at_scale(c, size) {
	c = g_charset.indexOf(c);
	var x = c % 8;
	var y = Math.floor(c / 8);
	var oldx = x;
	var oldy = y;

	x %= size;
	y %= size;

	x = Math.floor(x / (size / 2));
	y = Math.floor(y / (size / 2));

	return x + (y * 2);
}

function zoom_tl() { click(0); }
function zoom_tr() { click(1); }
function zoom_bl() { click(2); }
function zoom_br() { click(3); }

function click(quadrant) {
	var letters;
	var dots;

	if(animating) {
		return;
	}

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
			return; // we're already zoomed all the way out
		}
		if(dots == '..') {
			get_and_render(letters.substr(0, letters.length - 1));
			quadrant = quadrant_at_scale(letters.substr(letters.length - 1), 8);
		} else {
			var base = letters.substr(0, letters.length - 1);
			var last = letters.substr(letters.length - 1);
			if(dots == '') {
				quadrant = quadrant_at_scale(last, 2);
				last = quantize_url_char(last, 2);
			} else { // dots == '.'
				quadrant = quadrant_at_scale(last, 4);
				last = quantize_url_char(last, 4);
			}
			get_and_render(base + last + dots + '.');
		}
		animate_zoom_out(quadrant);
		return;
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
		animate_zoom_in(quadrant);
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
var animate_dx;
var animate_dy;
var data_ready;
var data_that_is_ready;

// these two will be constants unless/until we can detect how fast the client is:
var animation_steps = 8;
var animation_frame_delay = 100;

var animation_target_size;
var animation_steps_left;

function animate_zoom_out(quadrant) {
	animate_zoom_to(quadrant, 64);
}

function animate_zoom_in(which) {
	animate_zoom_to(which, 256);
}

function animate_zoom_to(which, target_size) {
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

	animation_target_size = target_size;
	start_animation_ticker();
}

function start_animation_ticker() {
	animating = true;
	animation_steps_left = animation_steps;
	animator_id = setInterval('animate_frame()', animation_frame_delay);
}

function animate_frame() {
	var i, j, xcur, ycur;
	var xsquares, yquares;
	var size;

	animation_steps_left -= 1;

	size = animation_target_size - Math.round((animation_target_size - 128) * animation_steps_left / animation_steps);

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
		ysquares[i].style.height = size + 'px';
		ysquares[i].style.top = ycur + 'px';
		i++;
		if(i % 4 == 0) {
			xcur += size * animate_dx;
			ycur += size * animate_dy;
		}
	}

	if(animation_steps_left == 0) {
		animating = false;
		clearInterval(animator_id);
		if(data_ready) {
			data_ready = false;
			setTimeout('squares(data_that_is_ready)', 10);
		}
		return;
	}
}
