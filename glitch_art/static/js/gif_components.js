function gif_components(mouthmouth_bytes){
  og = mouthmouth_bytes;

  //header
  header = mouthmouth_bytes.slice(0,6)
  mouthmouth_bytes = mouthmouth_bytes.slice(6,mouthmouth_bytes.length)

  //logical_screen_descriptor
  logical_screen_descriptor = mouthmouth_bytes.slice(0,7)
  logical_screen_descriptor = {
    full_string: logical_screen_descriptor,
    width: logical_screen_descriptor.slice(0,2),
    height: logical_screen_descriptor.slice(2,4),
    packed_byte: logical_screen_descriptor.slice(4,5),
    background_color_index: logical_screen_descriptor.slice(5,6),
    pixel_aspect_ratio: logical_screen_descriptor.slice(6,7)
  }
  packed_byte = logical_screen_descriptor.packed_byte.toString()
  packed_byte_binary = hex2bin(packed_byte);
  packed_field = {
    full_string: packed_byte,
    global_color_table_flag: packed_byte_binary.slice(0,1),
    color_resolution: packed_byte_binary.slice(1,4),
    sort_flag:packed_byte_binary.slice(1,4),
    size_of_global_color_table: bin_to_dec(packed_byte_binary.slice(5,8))
  }
  logical_screen_descriptor.packed_field = packed_field;
  mouthmouth_bytes = mouthmouth_bytes.slice(7, mouthmouth_bytes.length)

  gct_f = 0;
  if (logical_screen_descriptor.packed_field.global_color_table_flag == 1){
    //global color table
    gct_f = 1;
    global_color_table_length = Math.round(3*(2 ** (logical_screen_descriptor.packed_field.size_of_global_color_table + 1)))
    global_color_table = mouthmouth_bytes.slice(0, global_color_table_length)
    colors = []
    mouthmouth_bytes = mouthmouth_bytes.slice(global_color_table_length,mouthmouth_bytes.length)
    and_the_rest = og.slice(global_color_table_length,og.length)
  }

  //graphics_control_ext
  graphics_control_ext = mouthmouth_bytes.slice(0, 8)
  graphics_control_ext = {
    full_string: graphics_control_ext,
    extension_introducer: graphics_control_ext.slice(0,1)[0],
    graphics_control_label: graphics_control_ext.slice(1,2),
    byte_size: graphics_control_ext.slice(2,3),
    packed_field: graphics_control_ext.slice(3,4),
    delay_time: graphics_control_ext.slice(4,6),
    transparent_color_index:  graphics_control_ext.slice(6,7),
    block_terminator: graphics_control_ext.slice(7,8)
  }
  packed_byte_graphics = graphics_control_ext.packed_field.toString()
  packed_byte_graphics_binary = hex2bin(packed_byte_graphics);
  packed_field_graphics = {
    full_string: packed_byte_graphics,
    reserved_for_future_use: packed_byte_graphics_binary.slice(0,3),
    disposal_method: packed_byte_graphics_binary.slice(3,4),
    user_input_flag: packed_byte_graphics_binary.slice(4,5),
    transparent_color_flag: packed_byte_graphics_binary.slice(5,6)
  }
  mouthmouth_bytes = mouthmouth_bytes.slice(8, mouthmouth_bytes.length)

  //Image descriptor
  image_descriptor = mouthmouth_bytes.slice(0, 10)
  image_descriptor =  {
    full_string: image_descriptor,
    image_separator: image_descriptor.slice(0,1),
    image_left: image_descriptor.slice(1,3),
    image_top: image_descriptor.slice(3,5),
    image_width: image_descriptor.slice(5,7),
    image_height: image_descriptor.slice(7,9),
    packed_field: image_descriptor.slice(8,9)
  }
  packed_byte_image = image_descriptor.packed_field.toString()
  packed_byte_image_binary = hex2bin(packed_byte_graphics);
  packed_field_image = {
    full_string: packed_byte_image,
    local_color_table_flag: packed_byte_image_binary.slice(0,1),
    interlace_flag: packed_byte_image_binary.slice(1,2),
    sort_flag: packed_byte_image_binary.slice(2,3),
    reserved_for_future_use: packed_byte_image_binary.slice(3,5),
    size_of_local_color_table: packed_byte_image_binary.slice(5,8),
  }
  image_descriptor.packed_field = packed_field_image;
  mouthmouth_bytes = mouthmouth_bytes.slice(10, mouthmouth_bytes.length)
  lct_f = 0;
  if (image_descriptor.packed_field.local_color_table_flag == 1){
    //local color table
    lct_f = 1;
    local_color_table_length = Math.round(3*(2 ** (image_descriptor.packed_field.size_of_local_color_table + 1)))
    local_color_table = mouthmouth_bytes.slice(0, local_color_table_length)
    mouthmouth_bytes = mouthmouth_bytes.slice(local_color_table_length,mouthmouth_bytes.length)
  }


  image_data = mouthmouth_bytes.slice(0, 2)
  number_of_bytes = mouthmouth_bytes.slice(1,2)
  number_of_bytes = hex2bin(number_of_bytes);
  number_of_bytes = bin_to_dec(number_of_bytes)
  image_data = mouthmouth_bytes.slice(0,number_of_bytes+3)
  mouthmouth_bytes = mouthmouth_bytes.slice(number_of_bytes+3, mouthmouth_bytes.length)


  //plain text extension
  pte_f = 0;
  if (mouthmouth_bytes.slice(0,2) == '21 01'){
    plain_text_size = mouthmouth_bytes.slice(2,3)
    plain_text_size = hex2bin(plain_text_size)
    plain_text_size = bin_to_dec(plain_text_size)
    //skip the useless stuff
    pte_f = 1;
    mouthmouth_bytes = mouthmouth_bytes.slice(3,plain_text_size)
    plain_text_ext = []
    var i = 0;
    while(mouthmouth_bytes[i]!= '00'){
      plain_text_ext.append(mouthmouth_bytes[i])
    }
    mouthmouth_bytes = mouthmouth_bytes.slice(plain_text_size + i, mouthmouth_bytes.length)
  }
  //application text extension
  ate_f = 0;
  if (mouthmouth_bytes.slice(0,2) == '21 FF'){
    ate_f = 1;
    app_size = mouthmouth_bytes.slice(2,3)
    app_size = hex2bin(app_size)
    app_size = bin_to_dec(app_size)
    //skip the useless stuff
    mouthmouth_bytes = mouthmouth_bytes.slice(3,app_size)
    app_text_ext = []
    var i = 0;
    while(mouthmouth_bytes[i]!= '00'){
      app_text_ext.append(mouthmouth_bytes[i])
    }
    mouthmouth_bytes = mouthmouth_bytes.slice(app_size + i, mouthmouth_bytes.length)
  }


  //comment extension
  cte_f = 0;
  if (mouthmouth_bytes.slice(0,2) == '21 FE'){
    cte_f = 1;
    comment_size = mouthmouth_bytes.slice(2,3)
    comment_size = hex2bin(comment_size)
    comment_size = bin_to_dec(comment_size)
    //skip the useless stuff
    mouthmouth_bytes = mouthmouth_bytes.slice(3,comment_size)
    comm_text_ext = []
    var i = 0;
    while(mouthmouth_bytes[i]!= '00'){
      comm_text_ext.append(mouthmouth_bytes[i])
    }
    mouthmouth_bytes = mouthmouth_bytes.slice(comment_size + i, mouthmouth_bytes.length)
  }

  trailer = mouthmouth_bytes.slice(0,mouthmouth_bytes.length)
  console.log("HI")
  console.log(mouthmouth_bytes)
  total_package = {
    header:header,
    logical_screen_descriptor:logical_screen_descriptor,
    graphics_control_ext:graphics_control_ext,
    image_descriptor:image_descriptor,
    image_data:image_data,
    trailer:trailer,
  }
  if (gct_f == 1){
    total_package.global_color_table = global_color_table
    total_package.global_color_table_length = global_color_table_length
    total_package.and_the_rest = and_the_rest
  }
  if (lct_f == 1){
    total_package.local_color_table = local_color_table
  }
  if (pte_f == 1){
    total_package.plain_text_ext = plain_text_ext;
  }
  if (ate_f == 1){
    total_package.plain_text_ext = app_text_ext;
  }
  if (cte_f == 1){
    total_package.comm_text_ext = comm_text_ext;
  }

  return total_package
}
