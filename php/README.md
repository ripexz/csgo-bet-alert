Files in this folder are hosted on my own web server.

The get_data.php is called every minute to get active match data.
It's a bulky way of doing it because CS:GO Lounge doesnt have an API so I check if the match is in the future, i.e. you can bet on it.
The lack of an API is mostly the reason I put the load on my servers instead.

The users then query get_json.php which is formatted into a nice list in the extension