function jason() {
	sendRequest('binary.html', call_me);
}

function call_me(rec) {
	squares(rec.responseText);
}

function squares(data) {
	var index;
	var i;
	var png;
	var width = 128;
	var height = 128;
	var b;
	var bits;
	var row;
	var data_i = 0;
	var square_num;
	var square_names = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

	png_init(width, height);

	for(square_num = 0; square_num < 16; square_num++) {
		index = pixels_start;

		row = (width/8) + 1;
		for(i = 0; i < (height*width)/16; i += 2) {
			if(i % (width/8) == 0) {
				// filter type at the begining of each scanlines
				if(i) {
					index += row;
				}
				png_array[index] = 0;
				png_array[index + row] = 0;
				index++;
			}
			b = (data[data_i] & 0xf0) >> 4;
			bits = (b & 0x1) | ((b<<1) & 0x4) | ((b<<2) & 0x10) | ((b<<3) & 0x40);
			bits |= bits << 1;
			png_array[index] = bits;
			png_array[index + row] = bits;
			index++;
			b = (data[data_i++] & 0x0f);
			bits = (b & 0x1) | ((b<<1) & 0x4) | ((b<<2) & 0x10) | ((b<<3) & 0x40);
			bits |= bits << 1;
			png_array[index] = bits;
			png_array[index + row] = bits;
			index++;
		}

		png = make_png();
		tag('square_' + square_names[square_num]).style.backgroundImage = 'url(data:image/png;base64,' + png + ')';
	}
}
