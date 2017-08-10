# Gaming House  
A platform to release wagers for players to bet on and published results to calculate each player’s payoff.  
Integrated 6 EIP patterns and 4 design patterns in order to get correct payoff amount under condition that only partial wager results are published.

***
## Patterns used
### EIP
1. Request-reply channel  
	Player requests for wager list by sending his/her name in message and Dealer sends the list via message queue naming after the player's name.

2. Point-to-point channel
	Dealer sends wager results to message queue--Results

3. Pub/Sub channel
	A publisher publishes messages from Results queue to Results_Topic and Player subscribes to Results_Topic

4. Content-based router
	Once a wager is bet on, Player will send bet messages to different queues depending on the wager type for further analyses. i.e. Total_Points, Point_Spread, Single

5. Invalid message channel
	If the content of the wager list is incomplete, send the message to Invalid message queue. (e.g. There's no comma in the message or lacking of some items in each wager)

6. Dead letter channel
	If the Player enters empty string as name or player_name is null, send the request message to dead letter channel.

### Design pattern
1. Composite
	Wager and Parlay
2. Strategy
	Different kinds of OddsConverter (EUR/US/UK)
3. Visitor
	WinningsCounter, for counting player's payoff
4. Observer
	Notify results based on game name