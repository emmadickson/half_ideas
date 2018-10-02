//--------------------------------------------
// PROTICIPOVE TEXTY
//--------------------------------------------

var proticipove_texty = new Array();
proticipove_texty[0] = new Array("We&nbsp;&nbsp;love&nbsp;&nbsp;you<br>and&nbsp;&nbsp;we&nbsp;&nbsp;help&nbsp;&nbsp;you&nbsp;&nbsp;!", "3001");
proticipove_texty[1] = new Array("We&nbsp;&nbsp;keep&nbsp;&nbsp;people<br>above&nbsp;&nbsp;water&nbsp;&nbsp;every&nbsp;&nbsp;day&nbsp;&nbsp;!", "3002");
proticipove_texty[2] = new Array("STOP<br>chip&nbsp;&nbsp;totality&nbsp;&nbsp;!", "3003");
proticipove_texty[3] = new Array("Deliver&nbsp;&nbsp;yourselves<br>from&nbsp;&nbsp;puppet&nbsp;&nbsp;captivity&nbsp;&nbsp;!", "3004");
proticipove_texty[4] = new Array("We&nbsp;&nbsp;will&nbsp;&nbsp;show&nbsp;&nbsp;you<br> the way into Heaven&nbsp;&nbsp;!", "3005");
proticipove_texty[5] = new Array("Saurians&nbsp;&nbsp;from&nbsp;&nbsp;hells&nbsp;&nbsp;control&nbsp;&nbsp;you<br>and&nbsp;&nbsp;usurp&nbsp;&nbsp;you&nbsp;&nbsp;through&nbsp;&nbsp;computers", "3006");
proticipove_texty[6] = new Array("Saurians&nbsp;&nbsp;prepare&nbsp;&nbsp;for&nbsp;&nbsp;you<br>chip&nbsp;&nbsp;hells&nbsp;&nbsp;!", "3007");
proticipove_texty[7] = new Array("People,<br>it’s&nbsp;&nbsp;time&nbsp;&nbsp;to&nbsp;&nbsp;wake&nbsp;&nbsp;up !", "3008");
proticipove_texty[8] = new Array("Saurians&nbsp;&nbsp;replace&nbsp;&nbsp;your&nbsp;&nbsp;heart<br> by&nbsp;&nbsp;calculator&nbsp;&nbsp;!", "3009");
proticipove_texty[9] = new Array("Saurians&nbsp;&nbsp;from&nbsp;&nbsp;hells<br>steal&nbsp;&nbsp;your&nbsp;&nbsp;life&nbsp;&nbsp;from&nbsp;&nbsp;90 %", "3010");
proticipove_texty[10] = new Array("Saurians&nbsp;&nbsp;from&nbsp;&nbsp;hells&nbsp;&nbsp;steal<br>your&nbsp;&nbsp;heart,&nbsp;&nbsp;blessing&nbsp;&nbsp;and&nbsp;&nbsp;freedom", "3011");
proticipove_texty[11] = new Array("Saurians&nbsp;&nbsp;from&nbsp;&nbsp;Dark&nbsp;&nbsp;worlds<br>make&nbsp;&nbsp;slaves&nbsp;&nbsp;of&nbsp;&nbsp;you&nbsp;&nbsp;!", "3012");
proticipove_texty[12] = new Array("Don’t&nbsp;&nbsp;be&nbsp;&nbsp;slaves<br>to saurians from Dark worlds", "3013");
proticipove_texty[13] = new Array("Saurians&nbsp;&nbsp;from&nbsp;&nbsp;hells&nbsp;&nbsp;want<br>to&nbsp;&nbsp;implant&nbsp;&nbsp;CHIPS&nbsp;&nbsp;into&nbsp;&nbsp;your&nbsp;&nbsp;bodies", "3014");
proticipove_texty[14] = new Array("Return&nbsp;&nbsp;to&nbsp;&nbsp;us<br>Home&nbsp;&nbsp;into&nbsp;&nbsp;Heaven&nbsp;&nbsp;!", "3015");
proticipove_texty[15] = new Array("<font style='line-height: 50%;'><br><\/font>Shield&nbsp;&nbsp;your&nbsp;&nbsp;heart&nbsp;&nbsp;from&nbsp;&nbsp;thieves", "3016");

function zobraz_proticipovy_text()
{
	var idx = Math.round(Math.random() * (proticipove_texty.length-1));
	document.getElementById("id_proticipovy_text").innerHTML = proticipove_texty[idx][0];
	document.getElementById("id_cislo_sdeleni").innerHTML    = proticipove_texty[idx][1];
}

function prepni_proticipovy_text() 
{
	setTimeout('prepni_proticipovy_text()', 5000);
	zobraz_proticipovy_text();
}


//--------------------------------------------
// VLAJKY STATU
//--------------------------------------------

var v;         // Zaznamy k vlajkam statu a zavislych uzemi, odkud se stahuje
var imgVlajky; // CSS Sprite obrazek vlajek

function napln_pole_vlajek()
{
	v = new Array();
	// Format pole 'v': vertikalni offset pro vlajku kontinentu; vertikalni offset pro vlajku statu; sirka vlajky statu; farba nazvu statu; nazev statu
	v[0]=new Array("10","465","68","Purple","ALGERIA");
	v[1]=new Array("10","530","68","Purple","ANGOLA");
	v[2]=new Array("10","595","68","Purple","BENIN");
	v[3]=new Array("10","660","68","Purple","BOTSWANA");
	v[4]=new Array("10","725","68","Purple","BURKINA FASO");
	v[5]=new Array("10","790","75","Purple","BURUNDI");
	v[6]=new Array("10","855","68","Purple","CAMEROON");
	v[7]=new Array("10","920","75","Purple","CAPE VERDE");
	v[8]=new Array("10","985","68","Purple","CENTRAL AFRICAN REPUBLIC");
	v[9]=new Array("10","1050","68","Purple","CONGO (FRENCH CONGO)");
	v[10]=new Array("10","1115","60","Purple","CONGO, ZAIRE");
	v[11]=new Array("10","1180","68","Purple","DJIBOUTI");
	v[12]=new Array("10","1245","68","Purple","EGYPT");
	v[13]=new Array("10","1310","68","Purple","EQUATORIAL GUINEA");
	v[14]=new Array("10","1375","89","Purple","ERITREA");
	v[15]=new Array("10","1440","88","Purple","ETHIOPIA");
	v[16]=new Array("10","1505","60","Purple","GABON");
	v[17]=new Array("10","1570","68","Purple","GAMBIA");
	v[18]=new Array("10","1635","68","Purple","GHANA");
	v[19]=new Array("10","1700","68","Purple","IVORY COAST");
	v[20]=new Array("10","1765","68","Purple","KENYA");
	v[21]=new Array("10","1830","68","Purple","LESOTHO");
	v[22]=new Array("10","1895","90","Purple","LIBYA");
	v[23]=new Array("10","1960","68","Purple","MADAGASCAR");
	v[24]=new Array("10","2025","68","Purple","MALAWI");
	v[25]=new Array("10","2090","68","Purple","MALI");
	v[26]=new Array("10","2155","68","Purple","MAURITANIA");
	v[27]=new Array("10","2220","68","Purple","MAURITIUS");
	v[28]=new Array("10","2285","68","Purple","MOROCCO");
	v[29]=new Array("10","2350","68","Purple","MOZAMBIQUE");
	v[30]=new Array("10","2415","68","Purple","NAMIBIA");
	v[31]=new Array("10","2480","53","Purple","NIGER");
	v[32]=new Array("10","2545","91","Purple","NIGERIA");
	v[33]=new Array("10","2610","75","Purple","REUNION");
	v[34]=new Array("10","2675","68","Purple","RWANDA");
	v[35]=new Array("10","2740","68","Purple","SENEGAL");
	v[36]=new Array("10","2805","89","Purple","SEYCHELLES");
	v[37]=new Array("10","2870","68","Purple","SIERRA LEONE");
	v[38]=new Array("10","2935","68","Purple","SOMALIA");
	v[39]=new Array("10","3000","68","Purple","SOUTH AFRICA");
	v[40]=new Array("10","3065","89","Purple","SUDAN");
	v[41]=new Array("10","3130","68","Purple","SWAZILAND");
	v[42]=new Array("10","3195","68","Purple","TANZANIA");
	v[43]=new Array("10","3260","73","Purple","TOGO");
	v[44]=new Array("10","3325","68","Purple","TUNISIA");
	v[45]=new Array("10","3390","68","Purple","UGANDA");
	v[46]=new Array("10","3455","68","Purple","ZAMBIA");
	v[47]=new Array("10","3520","91","Purple","ZIMBABWE");
	v[48]=new Array("75","3585","67","Maroon","ANTARCTICA");
	v[49]=new Array("140","3650","68","Navy","AFGHANISTAN");
	v[50]=new Array("140","3715","89","Navy","ARMENIA");
	v[51]=new Array("140","3780","89","Navy","AZERBAIJAN");
	v[52]=new Array("140","3845","75","Navy","BAHRAIN");
	v[53]=new Array("140","3910","75","Navy","BANGLADESH");
	v[54]=new Array("140","3975","68","Navy","BHUTAN");
	v[55]=new Array("140","4040","89","Navy","BRUNEI DARUSSALAM");
	v[56]=new Array("140","4105","80","Navy","BURMA, MYANMAR");
	v[57]=new Array("140","4170","68","Navy","CAMBODIA");
	v[58]=new Array("140","4235","89","Navy","EAST TIMOR");
	v[59]=new Array("140","4300","68","Navy","GEORGIA");
	v[60]=new Array("140","4365","68","Navy","HONG KONG");
	v[61]=new Array("140","4430","68","Navy","CHINA");
	v[62]=new Array("140","4495","68","Navy","INDIA");
	v[63]=new Array("140","4560","68","Navy","INDONESIA");
	v[64]=new Array("140","4625","79","Navy","IRAN");
	v[65]=new Array("140","4690","68","Navy","IRAQ");
	v[66]=new Array("140","4755","62","Navy","ISRAEL");
	v[67]=new Array("140","4820","68","Navy","JAPAN");
	v[68]=new Array("140","4885","89","Navy","JORDAN");
	v[69]=new Array("140","4950","89","Navy","KAZAKHSTAN");
	v[70]=new Array("140","5015","89","Navy","KUWAIT");
	v[71]=new Array("140","5080","75","Navy","KYRGYZSTAN");
	v[72]=new Array("140","5145","68","Navy","LAOS");
	v[73]=new Array("140","5210","68","Navy","LEBANON");
	v[74]=new Array("140","5275","68","Navy","MACAU");
	v[75]=new Array("140","5340","89","Navy","MALAYSIA");
	v[76]=new Array("140","5405","68","Navy","MALDIVES");
	v[77]=new Array("140","5470","89","Navy","MONGOLIA");
	v[78]=new Array("140","5535","37","Navy","NEPAL");
	v[79]=new Array("140","5600","89","Navy","NORTH KOREA");
	v[80]=new Array("140","5665","89","Navy","OMAN");
	v[81]=new Array("140","5730","68","Navy","PAKISTAN");
	v[82]=new Array("140","5795","89","Navy","PALESTINE");
	v[83]=new Array("140","5860","89","Navy","PHILIPPINES");
	v[84]=new Array("140","5925","114","Navy","QATAR");
	v[85]=new Array("140","5990","68","Navy","SAUDI ARABIA");
	v[86]=new Array("140","6055","68","Navy","SINGAPORE");
	v[87]=new Array("140","6120","68","Navy","SOUTH KOREA");
	v[88]=new Array("140","6185","89","Navy","SRI LANKA");
	v[89]=new Array("140","6250","68","Navy","SYRIA");
	v[90]=new Array("140","6315","89","Navy","TADJIKISTAN");
	v[91]=new Array("140","6380","68","Navy","TAIWAN");
	v[92]=new Array("140","6445","68","Navy","THAILAND");
	v[93]=new Array("140","6510","68","Navy","TURKEY");
	v[94]=new Array("140","6575","68","Navy","TURKMENISTAN");
	v[95]=new Array("140","6640","89","Navy","UNITED ARAB EMIRATES");
	v[96]=new Array("140","6705","89","Navy","UZBEKISTAN");
	v[97]=new Array("140","6770","68","Navy","VIETNAM");
	v[98]=new Array("140","6835","68","Navy","YEMEN");
	v[99]=new Array("205","6900","89","Maroon","AMERICAN SAMOA");
	v[100]=new Array("205","6965","89","DarkGreen","AUSTRALIA");
	v[101]=new Array("205","7030","89","DarkGreen","COOK ISLANDS");
	v[102]=new Array("205","7095","89","DarkGreen","FIJI");
	v[103]=new Array("205","7160","68","Maroon","FRENCH POLYNESIA");
	v[104]=new Array("205","7225","75","DarkGreen","GUAM");
	v[105]=new Array("205","7290","85","DarkGreen","MICRONESIA");
	v[106]=new Array("205","7355","89","Maroon","NEW CALEDONIA (FRENCH)");
	v[107]=new Array("205","7420","89","DarkGreen","NEW ZEALAND");
	v[108]=new Array("205","7485","90","Maroon","NORFOLK ISLAND");
	v[109]=new Array("205","7550","89","Maroon","NORTHERN MARIANA ISLANDS");
	v[110]=new Array("205","7615","60","DarkGreen","PAPUA NEW GUINEA");
	v[111]=new Array("205","7680","89","DarkGreen","SAMOA, WESTERN SAMOA");
	v[112]=new Array("205","7745","75","DarkGreen","VANUATU");
	v[113]=new Array("205","7810","68","Maroon","WALLIS AND FUTUNA");
	v[114]=new Array("270","7875","68","Maroon","ALAND ISLANDS");
	v[115]=new Array("270","7940","63","Blue","ALBANIA");
	v[116]=new Array("270","8005","64","Blue","ANDORRA");
	v[117]=new Array("270","8070","68","Blue","AUSTRIA");
	v[118]=new Array("270","8135","89","Blue","BELARUS");
	v[119]=new Array("270","8200","52","Blue","BELGIUM");
	v[120]=new Array("270","8265","89","Blue","BOSNIA AND HERZEGOVINA");
	v[121]=new Array("270","8330","75","Blue","BULGARIA");
	v[122]=new Array("270","8395","89","Blue","CROATIA");
	v[123]=new Array("270","8460","75","Blue","CYPRUS");
	v[124]=new Array("270","8525","68","Red","CZECH REPUBLIC");
	v[125]=new Array("270","8590","60","Blue","DENMARK");
	v[126]=new Array("270","8655","70","Blue","ESTONIA");
	v[127]=new Array("270","8720","62","Maroon","FAROE ISLANDS");
	v[128]=new Array("270","8785","74","Blue","FINLAND");
	v[129]=new Array("270","8850","68","Blue","FRANCE");
	v[130]=new Array("270","8915","75","Blue","GERMANY");
	v[131]=new Array("270","8980","89","Maroon","GIBRALTAR");
	v[132]=new Array("270","9045","89","Blue","GREAT BRITAIN");
	v[133]=new Array("270","9110","68","Blue","GREECE");
	v[134]=new Array("270","9175","68","Maroon","GUERNSEY");
	v[135]=new Array("270","9240","89","Blue","HUNGARY");
	v[136]=new Array("270","9305","62","Blue","ICELAND");
	v[137]=new Array("270","9370","89","Blue","IRELAND");
	v[138]=new Array("270","9435","89","Maroon","ISLE OF MAN");
	v[139]=new Array("270","9500","68","Blue","ITALY");
	v[140]=new Array("270","9565","75","Maroon","JERSEY");
	v[141]=new Array("270","9630","68","Blue","KOSOVO");
	v[142]=new Array("270","9695","89","Blue","LATVIA");
	v[143]=new Array("270","9760","75","Blue","LIECHTENSTEIN");
	v[144]=new Array("270","9825","75","Blue","LITHUANIA");
	v[145]=new Array("270","9890","75","Blue","LUXEMBOURG");
	v[146]=new Array("270","9955","89","Blue","MACEDONIA");
	v[147]=new Array("270","10020","68","Blue","MALTA");
	v[148]=new Array("270","10085","89","Blue","MOLDOVA");
	v[149]=new Array("270","10150","56","Blue","MONACO");
	v[150]=new Array("270","10215","89","Blue","MONTENEGRO");
	v[151]=new Array("270","10280","68","Blue","NETHERLANDS");
	v[152]=new Array("270","10345","62","Blue","NORWAY");
	v[153]=new Array("270","10410","72","Blue","POLAND");
	v[154]=new Array("270","10475","68","Blue","PORTUGAL");
	v[155]=new Array("270","10540","68","Blue","ROMANIA");
	v[156]=new Array("270","10605","68","Blue","RUSSIA");
	v[157]=new Array("270","10670","60","Blue","SAN MARINO");
	v[158]=new Array("270","10735","68","Blue","SERBIA");
	v[159]=new Array("270","10800","68","Blue","SLOVAKIA");
	v[160]=new Array("270","10865","89","Blue","SLOVENIA");
	v[161]=new Array("270","10930","68","Blue","SPAIN");
	v[162]=new Array("270","10995","72","Blue","SWEDEN");
	v[163]=new Array("270","11060","45","Blue","SWITZERLAND");
	v[164]=new Array("270","11125","68","Blue","UKRAINE");
	v[165]=new Array("270","11190","45","Blue","VATICAN");
	v[166]=new Array("335","11255","89","Maroon","ANGUILLA ISLAND");
	v[167]=new Array("335","11320","68","Green","ANTIGUA AND BARBUDA");
	v[168]=new Array("335","11385","68","Green","ARUBA");
	v[169]=new Array("335","11450","68","Green","BARBADOS");
	v[170]=new Array("335","11515","68","Green","BELIZE");
	v[171]=new Array("335","11580","89","Maroon","BERMUDA ISLANDS");
	v[172]=new Array("335","11645","89","Maroon","BRITISH VIRGIN ISLANDS");
	v[173]=new Array("335","11710","89","Green","CANADA");
	v[174]=new Array("335","11775","89","Maroon","CAYMAN ISLANDS");
	v[175]=new Array("335","11840","75","Green","COSTA RICA");
	v[176]=new Array("335","11905","89","Green","CUBA");
	v[177]=new Array("335","11970","89","Green","DOMINICA");
	v[178]=new Array("335","12035","72","Green","DOMINICAN REPUBLIC");
	v[179]=new Array("335","12100","79","Green","EL SALVADOR");
	v[180]=new Array("335","12165","68","Maroon","GREENLAND");
	v[181]=new Array("335","12230","75","Green","GRENADA");
	v[182]=new Array("335","12295","68","Maroon","GUADELOUPE ISLANDS");
	v[183]=new Array("335","12360","72","Green","GUATEMALA");
	v[184]=new Array("335","12425","75","Green","HAITI");
	v[185]=new Array("335","12490","89","Green","HONDURAS");
	v[186]=new Array("335","12555","89","Green","JAMAICA");
	v[187]=new Array("335","12620","67","Maroon","MARTINIQUE ISLAND");
	v[188]=new Array("335","12685","79","Green","MEXICO");
	v[189]=new Array("335","12750","75","Green","NICARAGUA");
	v[190]=new Array("335","12815","68","Green","PANAMA");
	v[191]=new Array("335","12880","68","Green","PUERTO RICO");
	v[192]=new Array("335","12945","68","Green","SAINT KITTS AND NEVIS");
	v[193]=new Array("335","13010","89","Green","SAINT LUCIA");
	v[194]=new Array("335","13075","72","Maroon","SAINT MARTIN ISLAND");
	v[195]=new Array("335","13140","68","Green","ST. VINCENT AND THE GRENADINES");
	v[196]=new Array("335","13205","89","Green","THE BAHAMAS");
	v[197]=new Array("335","13270","75","Green","TRINIDAD AND TOBAGO");
	v[198]=new Array("335","13335","89","Maroon","TURKS AND CAICOS ISLANDS");
	v[199]=new Array("335","13400","68","Maroon","UNITED STATES VIRGIN ISLANDS");
	v[200]=new Array("335","13465","85","Green","USA");
	v[201]=new Array("400","13530","70","Teal","ARGENTINA");
	v[202]=new Array("400","13595","68","Teal","BOLIVIA");
	v[203]=new Array("400","13660","64","Teal","BRAZIL");
	v[204]=new Array("400","13725","68","Teal","COLOMBIA");
	v[205]=new Array("400","13790","68","Teal","CURACAO");
	v[206]=new Array("400","13855","89","Teal","ECUADOR");
	v[207]=new Array("400","13920","75","Teal","GUYANA, BRITISH GUIANA");
	v[208]=new Array("400","13985","68","Teal","CHILE");
	v[209]=new Array("400","14050","68","Teal","NETHERLANDS ANTILLES");
	v[210]=new Array("400","14115","75","Teal","PARAGUAY");
	v[211]=new Array("400","14180","68","Teal","PERU");
	v[212]=new Array("400","14245","68","Teal","SURINAME");
	v[213]=new Array("400","14310","68","Teal","URUGUAY");
	v[214]=new Array("400","14375","68","Teal","VENEZUELA");
	
	// Prepni vlajku
	prepni_vlajku();
}

function zobraz_vlajku()
{
	var idx = Math.round(Math.random() * (v.length-1));
	document.getElementById("id_vlajka_statu").style.backgroundPosition = '-10px -' + v[idx][1] + 'px';
	document.getElementById("id_vlajka_statu").style.width = v[idx][2] + 'px';
	document.getElementById("id_nazev_statu").innerHTML = '<span style=\'color: ' + v[idx][3] + ';\'>' + v[idx][4];
	document.getElementById("id_vlajka_kontinentu").style.backgroundPosition = '-10px -' + v[idx][0] + 'px';
}

function prepni_vlajku() 
{
	setTimeout('prepni_vlajku()', 2500);
	zobraz_vlajku();
}

/*
function inicializuj_vlajky()
{
	// Nektere stare prohlizece nepodporuji objekt images.
	if (!document.images)
		return;
	imgVlajky = new Image();
	imgVlajky.onload = napln_pole_vlajek;
	imgVlajky.src = "images/sprite_evacuation_vlajky_statu_en.png";
}

inicializuj_vlajky();
*/

function nastav_banner() 
{
	var idx = Math.round(Math.random() * (8-1));
	idx = idx + 1;
	var elem = document.getElementById("id_banner");
	elem.src = 'images/anim_banner_0' + idx + '_en.gif';
	
	//alert(idx);
	
	if (idx == 4 || idx == 8)
	{
		elem.height = 102;
	}
	elem.style.display = 'inline';
}

function zobraz_obr_varovani()
{
	var idx = Math.round(Math.random() * (10-1));
	idx = idx + 1;
	
	var baseObrNr = 6546;
	var selectedObrNr = 6546 + idx;
	
	var elem = document.getElementById("id_obr_varovani");
	elem.src = 'images/obr' + selectedObrNr + 'x_reklama_varovani_' + idx + '_en_de_cz.jpg';
	
	//alert(elem.src);
}

function prepni_obr_varovani() 
{
	setTimeout('prepni_obr_varovani()', 5000);
	zobraz_obr_varovani();
}
