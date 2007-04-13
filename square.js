function jason() {
	var index;
	var i;
	var png;
	var width = 128;
	var height = 128;
	var b;
	var bits;
	var row;

	png_init(width, height);
	index = pixels_start;

	row = (width/8) + 1;
	for(i = 0; i < (height*width)/16; i += 2) {
		if(i % (width/8) == 0) {
			// filter type at the begining of each scanline
			if(i) {
				index += row;
			}
			png_array[index] = 0;
			png_array[index + row] = 0;
			index++;
		}
		b = (i & 0xf0) >> 4;
		bits = (b & 0x1) | ((b<<1) & 0x4) | ((b<<2) & 0x10) | ((b<<3) & 0x40);
		bits |= bits << 1;
		png_array[index] = bits;
		png_array[index + row] = bits;
		index++;
		b = (i & 0xf);
		bits = (b & 0x1) | ((b<<1) & 0x4) | ((b<<2) & 0x10) | ((b<<3) & 0x40);
		bits |= bits << 1;
		png_array[index] = bits;
		png_array[index + row] = bits;
		index++;

		//png_array[index++] = (i & 0xff);
	}

	png = make_png();
	tag('square_img').src = "data:image/png;base64," + png;
	tag('square').style.backgroundImage = "url(data:image/png;base64," + png + ")";
}
