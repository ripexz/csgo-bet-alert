var csgobetalert = {

	reqUrl: 'http://eyeur.com/csgo/get_json.php',

	getData: function() {
		var req = new XMLHttpRequest();
		req.open("GET", this.reqUrl, true);
		req.onload = this.listMatches.bind(this);
		req.send(null);
	},

	listMatches: function(data) {
		var matchObj = JSON.parse(data.target.responseText);
		if ( matchObj.matches && matchObj.matches.length > 0 ) {
			for ( var i = 0; i < matchObj.matches.length; i++ ) {
				var p = document.createElement('p');
				p.id = matchObj.matches[i].id + '';
				p.innerHTML = '<span class="t1">'+matchObj.matches[i].team1+'</span><span class="vs">vs</span><span class="t2">'+matchObj.matches[i].team2+'</span>';
				document.getElementById('match_list').appendChild(p);
			}
			var str_num = i + '';
			this.applyBindings();
		}
		else {
			var p = document.createElement('p');
			p.innerHTML = 'No active matches found.';
			document.getElementById('match_list').appendChild(p);
			var str_num ='0';
		}
		chrome.browserAction.setBadgeText({text:str_num});
	},

	applyBindings: function() {
		var links = document.getElementsByTagName("p");
		for (var i = 0; i < links.length; i++) {
			(function () {
				var ln = links[i];
				var location = 'http://csgolounge.com/match?m=' + ln.id;
				ln.onclick = function () {
					chrome.tabs.create({active: true, url: location});
				};
			})();
		}
	}
};

document.addEventListener('DOMContentLoaded', function () {
	csgobetalert.getData();
});