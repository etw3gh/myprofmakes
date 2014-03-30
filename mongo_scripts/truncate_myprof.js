use myprof;
db.incoming.remove();
db.outgoing.remove();
db.tweets.remove();
db.runCommand({emptycapped: "last_search_id"})
db.runCommand({emptycapped: "last_mention_id"})
