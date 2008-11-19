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

var square_id; // id number of current square

// this is called (exclusively) by the html page's body onload
function load(square) {
	squares_init()
	get_and_render(square, false);
}

function get_and_render(square, zoom) {
	var url = 'binary';
	var sep = '?';

	square_id = square; // set the global

	url += '?url=' + square;
	if(zoom != false && zoom != 'out') {
		url += zoom;
		square_id += zoom;
	} else if (zoom == 'out') {
		// FIXME remove last char from url and square_id
	}

	sendRequest(url, call_me);
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
}

function render_square(square_num) {
	var png;

	make_square(square_num);
	png = make_png();
	tag(square_names[square_num]).src = 'data:image/png;base64,' + png;
	tag(square_names[square_num]).style.width = '128px';
	tag(square_names[square_num]).style.height = '128px';
}


function squares(data) {
	var square_num;
	var i;

	// square_id  = (data.charCodeAt(0) & 0xff) << 24;
	// square_id |= (data.charCodeAt(1) & 0xff) << 16;
	// square_id |= (data.charCodeAt(2) & 0xff) << 8;
	// square_id |= (data.charCodeAt(3) & 0xff);

	for(i = 4; i < data.length; ++i) {
		in_pixels[i - 4] = (data.charCodeAt(i) & 0xff);
	}

	for(square_num = 0; square_num < 16; square_num++) {
		render_square(square_num);
	}
}

function click(url, quadrant) {
	if(url == 'out') {
		get_and_render(square_id.substr(0, square_id.length - 1), false); // FIXME zoom out one, not all the way
	} else {
		get_and_render(square_id, url);
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

var h_shrinkers = new Array(8);
var v_shrinkers = new Array(8);
var h_expanders = new Array(8);
var v_expanders = new Array(8);
var animating = false;
var animator_id;
var pixels_to_animate;
var data_ready;
var data_that_is_ready;

function animate_zoom(which) {
	var i;
	var quad_act = new Array(4);
	var flags;
	var hs_index = 0;
	var vs_index = 0;
	var hg_index = 0;
	var vg_index = 0;

	quad_act[which] = EXPAND;
	quad_act[which^1] = HORIZ;
	quad_act[which^2] = VERTI;
	quad_act[which^3] = SHRINK;

	for(i = 0; i < 16; i++) {
		flags = quad_act[square_num_to_quadrant(i)];
		if(flags & ANIM_HS) {
			h_shrinkers[hs_index++] = i;
		}
		if(flags & ANIM_VS) {
			v_shrinkers[vs_index++] = i;
		}
		if(flags & ANIM_HG) {
			h_expanders[hg_index++] = i;
		}
		if(flags & ANIM_VG) {
			v_expanders[vg_index++] = i;
		}
	}

	animating = true;
	pixels_to_animate = 126;
	animator_id = setInterval('animate_frame()', 100);
}

function animate_frame() {
	var i;
	pixels_to_animate -= 13;
	if(pixels_to_animate < 1) {
		pixels_to_animate = 1;
	}
	
	for(i = 0; i < 8; i++) {
		tag(square_names[h_shrinkers[i]]).style.width = pixels_to_animate.toString() + 'px';
	}
	for(i = 0; i < 8; i++) {
		tag(square_names[v_shrinkers[i]]).style.height = pixels_to_animate.toString() + 'px';
	}
	for(i = 0; i < 8; i++) {
		tag(square_names[h_expanders[i]]).style.width = (256 - pixels_to_animate).toString() + 'px';
	}
	for(i = 0; i < 8; i++) {
		tag(square_names[v_expanders[i]]).style.height = (256 - pixels_to_animate).toString() + 'px';
	}

	if(pixels_to_animate == 1) {
		animating = false;
		clearInterval(animator_id);
		if(data_ready) {
			data_ready = false;
			squares(data_that_is_ready);
		}
	}
}
