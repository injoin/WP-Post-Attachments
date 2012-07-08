function geraUID(pref) {
	var date = new Date();
	pref = pref || "";
	return pref + (Math.ceil((date.getTime() * (Math.random() * 2)) / 10));
}
Array.prototype.contains = function(obj) {
	var _reg = 0, t = this, i = t.length;
	while(i--) { //!== 0
		if(t[i] === obj) {
			_reg++;
		}
	}
return _reg;
};
String.prototype.replaceAll = function(token, newToken, ignoreCase) {
	var str = this.toString(), i = -1, _token;
	if(typeof token === "string") {
		if(ignoreCase === true) {
			_token = token.toLowerCase();
			while((i = str.toLowerCase().indexOf( token, i >= 0? i + newToken.length : 0 )) !== -1 ) {
			    str = str.substring(0, i)
			    		.concat(newToken)
			    		.concat(str.substring(i + token.length));
			}
		} else {
			return this.split(token).join(newToken);
		}
	}
return str;
};