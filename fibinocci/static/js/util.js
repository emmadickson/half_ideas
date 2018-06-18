function HexToBase64(hexstring) {
  return btoa(hexstring.match(/\w{2}/g).map(function(a) {
      return String.fromCharCode(parseInt(a, 16));
  }).join(""));
}

function HexToBin(hex){
  return ("00000000" + (parseInt(hex, 16)).toString(2)).substr(-8);
}

function BinToDec(bstr) {
  return parseInt((bstr + '')
  .replace(/[^01]/gi, ''), 2);
}

Array.prototype.clean = function(deleteValue) {
  for (var i = 0; i < this.length; i++) {
    if (this[i] == deleteValue) {
      this.splice(i, 1);
      i--;
    }
  }
  return this;
};

function GetRandomIntInclusive(min, max) {
  min = Math.ceil(min);
  max = Math.floor(max);
  return Math.floor(Math.random() * (max - min + 1)) + min;
}
