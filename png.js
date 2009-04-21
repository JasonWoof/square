function tag(name) {
    return document.getElementById(name);
}

var logging = true;

function log(msg) {
    if(logging) {
        tag('log').innerHTML += msg + '<br />';
    }
}



//////////////////
// INSTRUCTIONS //
//////////////////

// 1) call png_init(width, height)
// 2) put your pixel values into png_array starting at png_array[pixels_start]
// 3) the first byte in each row of pixels is the compression type (you should set it to 0)
// 4) call make_png() (it returns the base64-encoded png)

// IMPORTANT NOTES:
// 1) do not alter pixels_start
// 2) height and width must both be less than 256
// 3) each element of png_init is an integer representing 8 bits of the bitplane.




base64_charset = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9','+','/'];

function base64_encode(data){
	var size;
	var extra;
	var result;
	var i;
	var result_i;
	var tri;


	size = data.length;
	extra = (3 - (size % 3)) % 3; // the number of extra base64 chars we need as filler to be alligned
	if(extra != 0) {
		for(i = 0; i < extra; i++) {
			data.push(0);
		}
	}
	size += extra;
	result = new Array(size);

	result_i = 0;
	for(i = 0; i < size; i += 3){
//		if(i % 54 == 0) {
//			result[result_i++] = "\r\n";
//		}
		tri = data[i] <<16 | data[i + 1] << 8 | data[i + 2];
		result[result_i++] = base64_charset[(tri & 0xFC0000) >> 18];
		result[result_i++] = base64_charset[(tri & 0x03F000) >> 12];
		result[result_i++] = base64_charset[(tri & 0x0FC0) >> 6];
		result[result_i++] = base64_charset[(tri & 0x3F)];
	}
	if(extra > 0) {
		result[result_i - 1] = "=";
	}
	if(extra == 2) {
		result[result_i - 2] = "=";
	}

	return result.join("");
};

crc32_table=[
0x00000000,0x77073096,0xee0e612c,0x990951ba,0x076dc419,0x706af48f,0xe963a535,0x9e6495a3,
0x0edb8832,0x79dcb8a4,0xe0d5e91e,0x97d2d988,0x09b64c2b,0x7eb17cbd,0xe7b82d07,0x90bf1d91,
0x1db71064,0x6ab020f2,0xf3b97148,0x84be41de,0x1adad47d,0x6ddde4eb,0xf4d4b551,0x83d385c7,
0x136c9856,0x646ba8c0,0xfd62f97a,0x8a65c9ec,0x14015c4f,0x63066cd9,0xfa0f3d63,0x8d080df5,
0x3b6e20c8,0x4c69105e,0xd56041e4,0xa2677172,0x3c03e4d1,0x4b04d447,0xd20d85fd,0xa50ab56b,
0x35b5a8fa,0x42b2986c,0xdbbbc9d6,0xacbcf940,0x32d86ce3,0x45df5c75,0xdcd60dcf,0xabd13d59,
0x26d930ac,0x51de003a,0xc8d75180,0xbfd06116,0x21b4f4b5,0x56b3c423,0xcfba9599,0xb8bda50f,
0x2802b89e,0x5f058808,0xc60cd9b2,0xb10be924,0x2f6f7c87,0x58684c11,0xc1611dab,0xb6662d3d,
0x76dc4190,0x01db7106,0x98d220bc,0xefd5102a,0x71b18589,0x06b6b51f,0x9fbfe4a5,0xe8b8d433,
0x7807c9a2,0x0f00f934,0x9609a88e,0xe10e9818,0x7f6a0dbb,0x086d3d2d,0x91646c97,0xe6635c01,
0x6b6b51f4,0x1c6c6162,0x856530d8,0xf262004e,0x6c0695ed,0x1b01a57b,0x8208f4c1,0xf50fc457,
0x65b0d9c6,0x12b7e950,0x8bbeb8ea,0xfcb9887c,0x62dd1ddf,0x15da2d49,0x8cd37cf3,0xfbd44c65,
0x4db26158,0x3ab551ce,0xa3bc0074,0xd4bb30e2,0x4adfa541,0x3dd895d7,0xa4d1c46d,0xd3d6f4fb,
0x4369e96a,0x346ed9fc,0xad678846,0xda60b8d0,0x44042d73,0x33031de5,0xaa0a4c5f,0xdd0d7cc9,
0x5005713c,0x270241aa,0xbe0b1010,0xc90c2086,0x5768b525,0x206f85b3,0xb966d409,0xce61e49f,
0x5edef90e,0x29d9c998,0xb0d09822,0xc7d7a8b4,0x59b33d17,0x2eb40d81,0xb7bd5c3b,0xc0ba6cad,
0xedb88320,0x9abfb3b6,0x03b6e20c,0x74b1d29a,0xead54739,0x9dd277af,0x04db2615,0x73dc1683,
0xe3630b12,0x94643b84,0x0d6d6a3e,0x7a6a5aa8,0xe40ecf0b,0x9309ff9d,0x0a00ae27,0x7d079eb1,
0xf00f9344,0x8708a3d2,0x1e01f268,0x6906c2fe,0xf762575d,0x806567cb,0x196c3671,0x6e6b06e7,
0xfed41b76,0x89d32be0,0x10da7a5a,0x67dd4acc,0xf9b9df6f,0x8ebeeff9,0x17b7be43,0x60b08ed5,
0xd6d6a3e8,0xa1d1937e,0x38d8c2c4,0x4fdff252,0xd1bb67f1,0xa6bc5767,0x3fb506dd,0x48b2364b,
0xd80d2bda,0xaf0a1b4c,0x36034af6,0x41047a60,0xdf60efc3,0xa867df55,0x316e8eef,0x4669be79,
0xcb61b38c,0xbc66831a,0x256fd2a0,0x5268e236,0xcc0c7795,0xbb0b4703,0x220216b9,0x5505262f,
0xc5ba3bbe,0xb2bd0b28,0x2bb45a92,0x5cb36a04,0xc2d7ffa7,0xb5d0cf31,0x2cd99e8b,0x5bdeae1d,
0x9b64c2b0,0xec63f226,0x756aa39c,0x026d930a,0x9c0906a9,0xeb0e363f,0x72076785,0x05005713,
0x95bf4a82,0xe2b87a14,0x7bb12bae,0x0cb61b38,0x92d28e9b,0xe5d5be0d,0x7cdcefb7,0x0bdbdf21,
0x86d3d2d4,0xf1d4e242,0x68ddb3f8,0x1fda836e,0x81be16cd,0xf6b9265b,0x6fb077e1,0x18b74777,
0x88085ae6,0xff0f6a70,0x66063bca,0x11010b5c,0x8f659eff,0xf862ae69,0x616bffd3,0x166ccf45,
0xa00ae278,0xd70dd2ee,0x4e048354,0x3903b3c2,0xa7672661,0xd06016f7,0x4969474d,0x3e6e77db,
0xaed16a4a,0xd9d65adc,0x40df0b66,0x37d83bf0,0xa9bcae53,0xdebb9ec5,0x47b2cf7f,0x30b5ffe9,
0xbdbdf21c,0xcabac28a,0x53b39330,0x24b4a3a6,0xbad03605,0xcdd70693,0x54de5729,0x23d967bf,
0xb3667a2e,0xc4614ab8,0x5d681b02,0x2a6f2b94,0xb40bbe37,0xc30c8ea1,0x5a05df1b,0x2d02ef8d];

function crc32(data, offset, offset2) {
	var crc = 0xFFFFFFFF;
	var k;
	var i;

	for(i = offset; i < offset2; i++) {
		k = (crc ^ data[i]) & 0xFF;
		crc=((crc >> 8) & 0x00FFFFFF) ^ crc32_table[k];
	}
	return ~crc;
};



// adler-32 checksum for end of the zlib block in IDAT chunk
function adler32(data, offset, offset2) {
	var base=65521;
	var nmax=5552;
	var s1=1;
	var s2=0;
	var k=nmax;
	var i;

	// pnglets code started offset one byte before the address that made sense on each line. not sure why
	// maybe I should check that foo[i++] works the way it does in C
	for(i = offset; i < offset2; i++) {
		s1 += data[i];
		s2 += s1;
		if(--k == 0) {
			s1 %= base;
			s2 %= base;
			k = nmax;
		}
	}

	s1 %= base;
	s2 %= base;

	return (s2 << 16) | s1;
};

// example code for turning a string into a series of integers
// type = 'IDAT';
// for(var i=0;i<4;i++) {
//	 data[] = type.charCodeAt(i);};
// }

var png_array;
var pixels_start;
var crc_start;
var crc_end;
var adler_start;
var adler_end;

// this function generates a PNG image as described in rfc2083 and rfc1950

// assumes width and height are less than 256
function png_init(height, width) {
	var png_index;
	var length;
	var length_most;
	var length_least;

	png_index = 0;
	png_array = new Array(86 + Math.floor(((width + 8) * height + 7) / 8));

	// signature
	png_array[png_index++] = 137;
	png_array[png_index++] = 80;
	png_array[png_index++] = 78;
	png_array[png_index++] = 71;
	png_array[png_index++] = 13;
	png_array[png_index++] = 10;
	png_array[png_index++] = 26;
	png_array[png_index++] = 10;

	///////////////
	// IHDR chunk
	///////////////

	// chunk length
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = 13;

	crc_start = png_index;

	// chunk type "IHDR"
	png_array[png_index++] = 73;
	png_array[png_index++] = 72;
	png_array[png_index++] = 68;
	png_array[png_index++] = 82;

	// image width
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = width;

	// image height
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = height;

	// bit depth (bits per pixel)
	png_array[png_index++] = 1;

	// color type (0: greyscale, 2: rgb)
	png_array[png_index++] = 0;

	// compression method (0: zlib)
	png_array[png_index++] = 0;

	// filter method
	png_array[png_index++] = 0;

	// interlace method (0: none, 1: adam7)
	png_array[png_index++] = 0;

	// chuck CRC
	crc = crc32(png_array, crc_start, png_index);
	png_array[png_index++] = (crc >> 24) & 0xff;
	png_array[png_index++] = (crc >> 16) & 0xff;
	png_array[png_index++] = (crc >> 8) & 0xff;
	png_array[png_index++] = crc & 0xff;


	///////////////
	// IDAT chunk
	///////////////

	// chunk length
	length = Math.floor(((width + 8) * height + 7) / 8);
	length += 2; // zlib headers
	length += 1; // deflate block header
	length += 4; // deflate uncompressed block length
	length += 4; // zlib adler checksum

	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = length >> 8;
	png_array[png_index++] = length & 0xff;

	// save this in a global
	crc_start = png_index;

	// chunk type "IDAT"
	png_array[png_index++] = 73;
	png_array[png_index++] = 68;
	png_array[png_index++] = 65;
	png_array[png_index++] = 84;

	// zlib header
	png_array[png_index++] = 0x78;
	png_array[png_index++] = 0x01;

	// deflate block header
	png_array[png_index++] = 1; // no compression, last block

	// deflate block length
	length = (Math.ceil(width / 8) + 1) * height;
	length_most = length >> 8;
	length_least = length & 0xff;
	png_array[png_index++] = length_least;
	png_array[png_index++] = length_most;
	png_array[png_index++] = ((~length_least) & 0xff);
	png_array[png_index++] = ((~length_most) & 0xff);

	////////////////////////
	// PIXELS
	////////////////////////

	// save index for pixels (also where adler-32 checksum starts)
	pixels_start = png_index;
	adler_start = png_index;

	// leave room for pixels
	png_index += length;

	// save index for adler-32 checksum
	adler_end = png_index;
	png_index += 4;

	// save index for IDAT crc32 checksum
	crc_end = png_index;
	png_index += 4;


	///////////////
	// IEND chunk
	///////////////

	// always looks like this: 0000 0000 4945 4e44 ae42 6082  ....IEND.B`.

	// length
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;
	png_array[png_index++] = 0;

	// type
	png_array[png_index++] = 73;
	png_array[png_index++] = 69;
	png_array[png_index++] = 78;
	png_array[png_index++] = 68;

	// crc32
	png_array[png_index++] = 0xae;
	png_array[png_index++] = 0x42;
	png_array[png_index++] = 0x60;
	png_array[png_index++] = 0x82;
}

function make_png() {
	var adler;
	var idat_crc;
	var index;

	// calculate adler-32 checksum for zlib block in IDAT chunk
	adler = adler32(png_array, adler_start, adler_end);
	index = adler_end;
	png_array[index++] = (adler >> 24) & 0xff;
	png_array[index++] = (adler >> 16) & 0xff;
	png_array[index++] = (adler >> 8) & 0xff;
	png_array[index++] = adler & 0xff;

	// calculate CRC32 checksum for IDAT chunk
	idat_crc = crc32(png_array, crc_start, crc_end);
	index = crc_end;
	png_array[index++] = (idat_crc >> 24) & 0xff;
	png_array[index++] = (idat_crc >> 16) & 0xff;
	png_array[index++] = (idat_crc >> 8) & 0xff;
	png_array[index++] = idat_crc & 0xff;

	return base64_encode(png_array);
}
