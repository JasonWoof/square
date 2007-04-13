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


function squares(data) {
	var png;
	var data_i = 0;
	var square_num;
	var square_names = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

	in_pixels = new Array(data.length);
	for(var jt = 0; jt < data.length; ++jt) {
		in_pixels[jt] = (data.charCodeAt(jt) & 0xff);
	}

	png_init(width, height);

	for(square_num = 0; square_num < 16; square_num++) {
		make_square(square_num);
		png = make_png();
		tag('square_' + square_names[square_num]).style.backgroundImage = 'url(data:image/png;base64,' + png + ')';
	}
}
