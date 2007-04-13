var IN_WIDTH = 256;
var IN_HEIGHT = IN_WIDTH
var OUT_BOX_BEFORE_SCALING = 64;
var OUT_BOX_HEIGHT = OUT_BOX_BEFORE_SCALING * 2;
var OUT_BOX_WIDTH = OUT_BOX_HEIGHT
var width = 128;
var height = 128;
var in_row_bytes = 256 / 8;
var in_box_bytes = in_row_bytes / 4;
var in_box_vert = in_row_bytes * OUT_BOX_BEFORE_SCALING;
var in_pixels;

function jason() {
	sendRequest('binary.html', call_me);
}

function call_me(rec) {
	squares(rec.responseText);
}

function make_square(square_num) {
	var index;
	var i;
	var b;
	var bits;
	var out_row;

	index = pixels_start;
	in_index = in_box_bytes * (square_num % 4);
	in_index += Math.floor(square_num / 4) * in_box_vert;

	out_row = (width/8) + 1;
	for(i = 0; i < (height*width)/16; i += 2) {
		if(i % (width/8) == 0) {
			// filter type at the begining of each scanlines
			if(i) {
				index += out_row;
				in_index += in_row_bytes - in_box_bytes;
			}
			png_array[index] = 0;
			png_array[index + out_row] = 0;
			index++;
		}
		b = (in_pixels[in_index] & 0xf0) >> 4;
		bits = (b & 0x1) | ((b<<1) & 0x4) | ((b<<2) & 0x10) | ((b<<3) & 0x40);
		bits |= bits << 1;
		png_array[index] = bits;
		png_array[index + out_row] = bits;
		index++;
		b = (in_pixels[in_index++] & 0x0f);
		bits = (b & 0x1) | ((b<<1) & 0x4) | ((b<<2) & 0x10) | ((b<<3) & 0x40);
		bits |= bits << 1;
		png_array[index] = bits;
		png_array[index + out_row] = bits;
		index++;
	}
}


function squares(data) {
	var png;
	var data_i = 0;
	var square_num;
	var square_names = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

	in_pixels = new Array(data.length);
	for(var jt = 0; jt < data.length; ++jt) {
		in_pixels[jt] = (data.charCodeAt(jt));
	}

	png_init(width, height);

	for(square_num = 0; square_num < 16; square_num++) {
		make_square(square_num);
		png = make_png();
		tag('square_' + square_names[square_num]).style.backgroundImage = 'url(data:image/png;base64,' + png + ')';
	}
}
