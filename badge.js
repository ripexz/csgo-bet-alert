function updateBadge(data) {
	var matchObj = JSON.parse(data.target.responseText);
	var str_num = matchObj.matches.length + '';
	chrome.browserAction.setBadgeText({text:str_num});
}
function getMatchCount() {
	var req = new XMLHttpRequest();
	req.open("GET", 'http://www.ripexz.com/csgobetalert/get_json.php', true);
	req.onload = updateBadge.bind(this);
	req.send(null);
}

function addAlarm() {
	chrome.alarms.get("csgobetalert", function(alarm){
		if (typeof alarm == "undefined") {
			chrome.alarms.create("csgobetalert", {
				when: Date.now(),
				periodInMinutes: 5.0
			});
		}
	});
}
chrome.alarms.onAlarm.addListener(function(alarm) {
	if (alarm.name == "csgobetalert") {
		getMatchCount();
	}
});
chrome.runtime.onStartup.addListener(function() {
	addAlarm();
});
chrome.runtime.onInstalled.addListener(function() {
	addAlarm();
});