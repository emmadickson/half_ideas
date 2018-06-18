function CreateHeader(originalGifText){
  header = originalGifText.slice(0,6)
  return originalGifText
}
function HexToBin(hex){
  return ("00000000" + (parseInt(hex, 16)).toString(2)).substr(-8);
}
function CreateLogicalScreenDescriptorPackedByte(logicalScreenDescriptor){
  packedByte = logicalScreenDescriptor.packedByte.toString()
  packedByteBinary = HexToBin(packedByte);
  packedField = {
    fullString: packedByte,
    globalColorTableFlag: packedByteBinary.slice(0,1),
    colorResolution: packedByteBinary.slice(1,4),
    sortFlag: packedByteBinary.slice(1,4),
    sizeOfGlobalGolorTable: BinToDec(packedByteBinary.slice(5,8))
  }
  return packedField
}

function CreateLogicalScreenDescriptor(originalGifText){
  logicalScreenDescriptorBytes = originalGifText.slice(0,7)
  logicalScreenDescriptor = {
    fullString: logicalScreenDescriptorBytes,
    width: logicalScreenDescriptorBytes.slice(0,2),
    height: logicalScreenDescriptorBytes.slice(2,4),
    packedByte: logicalScreenDescriptorBytes.slice(4,5),
    backgroundColorIndex: logicalScreenDescriptorBytes.slice(5,6),
    pixelAspectRatio: logicalScreenDescriptorBytes.slice(6,7)
  }
  return logicalScreenDescriptor
}



function GifComponents(originalGifText){
  og = originalGifText;

  //header
  originalGifText = CreateHeader(originalGifText)
  originalGifText = originalGifText.slice(6,originalGifText.length)

  //logicalScreenDescriptor
  logicalScreenDescriptorText = CreateLogicalScreenDescriptor(originalGifText)
  packedField = CreateLogicalScreenDescriptorPackedByte(logicalScreenDescriptor)
  logicalScreenDescriptor.packedField = packedField;
  originalGifText = originalGifText.slice(7, originalGifText.length)

  //gct
  gct_f = 0;
  if (logicalScreenDescriptor.packedField.globalColorTableFlag == 1){
    //global color table
    gct_f = 1;
    globalColorTableLength = Math.round(3*(2 ** (logicalScreenDescriptor.packedField.sizeOfGlobalGolorTable + 1)))
    globalColorTable = originalGifText.slice(0, globalColorTableLength)
    colors = []
    originalGifText = originalGifText.slice(globalColorTableLength, originalGifText.length)
    and_the_rest = og.slice(globalColorTableLength, og.length)
  }

  //graphicsControlExt
  graphicsControlExtBytes = originalGifText.slice(0, 8)
  graphicsControlExt = {
    fullString: graphicsControlExtBytes,
    extensionIntroducer: graphicsControlExtBytes.slice(0,1)[0],
    graphicsControlLabel: graphicsControlExtBytes.slice(1,2),
    byteSize: graphicsControlExtBytes.slice(2,3),
    packedField: graphicsControlExtBytes.slice(3,4),
    delayTime: graphicsControlExtBytes.slice(4,6),
    transparentColorIndex: graphicsControlExtBytes.slice(6,7),
    blockTerminator: graphicsControlExtBytes.slice(7,8)
  }
  packed_byte_graphics = graphicsControlExt.packedField.toString()
  packed_byte_graphics_binary = HexToBin(packed_byte_graphics);
  packed_field_graphics = {
    fullString: packed_byte_graphics,
    reserved_for_future_use: packed_byte_graphics_binary.slice(0,3),
    disposal_method: packed_byte_graphics_binary.slice(3,4),
    user_input_flag: packed_byte_graphics_binary.slice(4,5),
    transparent_color_flag: packed_byte_graphics_binary.slice(5,6)
  }
  originalGifText = originalGifText.slice(8, originalGifText.length)

  //Image descriptor
  imageDescriptor = originalGifText.slice(0, 10)
  imageDescriptor =  {
    fullString: imageDescriptor,
    image_separator: imageDescriptor.slice(0,1),
    image_left: imageDescriptor.slice(1,3),
    image_top: imageDescriptor.slice(3,5),
    image_width: imageDescriptor.slice(5,7),
    image_height: imageDescriptor.slice(7,9),
    packed_field: imageDescriptor.slice(8,9)
  }
  packed_byte_image = imageDescriptor.packed_field.toString()
  packed_byte_image_binary = HexToBin(packed_byte_graphics);
  packed_field_image = {
    fullString: packed_byte_image,
    local_color_table_flag: packed_byte_image_binary.slice(0,1),
    interlace_flag: packed_byte_image_binary.slice(1,2),
    sort_flag: packed_byte_image_binary.slice(2,3),
    reserved_for_future_use: packed_byte_image_binary.slice(3,5),
    size_of_local_color_table: packed_byte_image_binary.slice(5,8),
  }
  imageDescriptor.packed_field = packed_field_image;
  originalGifText = originalGifText.slice(10, originalGifText.length)
  lct_f = 0;
  if (imageDescriptor.packed_field.local_color_table_flag == 1){
    //local color table
    lct_f = 1;
    local_color_table_length = Math.round(3*(2 ** (imageDescriptor.packed_field.size_of_local_color_table + 1)))
    local_color_table = originalGifText.slice(0, local_color_table_length)
    originalGifText = originalGifText.slice(local_color_table_length,originalGifText.length)
  }


  imageData = originalGifText.slice(0, 2)
  number_of_bytes = originalGifText.slice(1,2)
  number_of_bytes = HexToBin(number_of_bytes);
  number_of_bytes = BinToDec(number_of_bytes)
  imageData = originalGifText.slice(0,number_of_bytes+3)
  originalGifText = originalGifText.slice(number_of_bytes+3, originalGifText.length)


  //plain text extension
  pte_f = 0;
  if (originalGifText.slice(0,2) == '21 01'){
    plain_text_size = originalGifText.slice(2,3)
    plain_text_size = HexToBin(plain_text_size)
    plain_text_size = BinToDec(plain_text_size)
    //skip the useless stuff
    pte_f = 1;
    originalGifText = originalGifText.slice(3,plain_text_size)
    plain_text_ext = []
    var i = 0;
    while(originalGifText[i]!= '00'){
      plain_text_ext.append(originalGifText[i])
    }
    originalGifText = originalGifText.slice(plain_text_size + i, originalGifText.length)
  }
  //application text extension
  ate_f = 0;
  if (originalGifText.slice(0,2) == '21 FF'){
    ate_f = 1;
    app_size = originalGifText.slice(2,3)
    app_size = HexToBin(app_size)
    app_size = BinToDec(app_size)
    //skip the useless stuff
    originalGifText = originalGifText.slice(3,app_size)
    app_text_ext = []
    var i = 0;
    while(originalGifText[i]!= '00'){
      app_text_ext.append(originalGifText[i])
    }
    originalGifText = originalGifText.slice(app_size + i, originalGifText.length)
  }


  //comment extension
  cte_f = 0;
  if (originalGifText.slice(0,2) == '21 FE'){
    cte_f = 1;
    comment_size = originalGifText.slice(2,3)
    comment_size = HexToBin(comment_size)
    comment_size = BinToDec(comment_size)
    //skip the useless stuff
    originalGifText = originalGifText.slice(3,comment_size)
    comm_text_ext = []
    var i = 0;
    while(originalGifText[i]!= '00'){
      comm_text_ext.append(originalGifText[i])
    }
    originalGifText = originalGifText.slice(comment_size + i, originalGifText.length)
  }

  trailer = originalGifText.slice(0,originalGifText.length)

  total_package = {
    header:header,
    logicalScreenDescriptor:logicalScreenDescriptor,
    graphicsControlExt:graphicsControlExt,
    imageDescriptor:imageDescriptor,
    imageData:imageData,
    trailer:trailer,
  }
  if (gct_f == 1){
    total_package.globalColorTable = globalColorTable
    total_package.globalColorTableLength = globalColorTableLength
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
