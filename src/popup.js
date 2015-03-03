var csgobetalert = {

	reqUrl: 'http://eyeur.com/csgo/get_json.php',

	getData: function() {
		chrome.storage.local.get('csgomatches', function(matchObj){
			if (matchObj.csgomatches && matchObj.csgomatches.valid) {
				csgobetalert.renderMatches(matchObj.csgomatches);
			}
			else {
				csgobetalert.getDataViaAjax();
			}
		});
	},

	getDataViaAjax: function() {
		var req = new XMLHttpRequest();
		req.open("GET", this.reqUrl, true);
		req.onload = this.renderMatches.bind(this);
		req.send(null);
	},

	renderMatches: function(data) {
		var matchObj, countStr;
		if (data.valid) {
			matchObj = data;
		}
		else {
			matchObj = JSON.parse(data.target.responseText);
			matchObj.valid = true;
			chrome.storage.local.set({'csgomatches': matchObj});
		}

		if (matchObj.matches && matchObj.matches.length > 0) {
			for (var i = 0; i < matchObj.matches.length; i++) {
				var p = document.createElement('p');
				p.id = matchObj.matches[i].id + '';

				var chance1 = parseInt(matchObj.matches[i].chance1, 10),
					chance2 = parseInt(matchObj.matches[i].chance2, 10),
					c1class = chance1 >= chance2 ? 'higher' : 'lower',
					c2class = chance2 >= chance1 ? 'higher' : 'lower';

				p.innerHTML = '<div class="chance1 '+c1class+'">'+matchObj.matches[i].chance1+'</div>';
				p.innerHTML += '<div class="t1">'+matchObj.matches[i].team1+'</div>';
				p.innerHTML += '<div class="vs">vs</div>';
				p.innerHTML += '<div class="t2">'+matchObj.matches[i].team2+'</div>';
				p.innerHTML += '<div class="chance2 '+c2class+'">'+matchObj.matches[i].chance2+'</div>';

				document.getElementById('match_list').appendChild(p);
			}
			countStr = i + '';
			this.applyBindings();
		}
		else {
			var p = document.createElement('p');
			p.innerHTML = 'No active matches found.';
			document.getElementById('match_list').appendChild(p);
			countStr = '0';
		}
		chrome.browserAction.setBadgeText({text: countStr});
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