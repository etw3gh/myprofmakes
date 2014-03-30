use myprof
db.outgoing.update({},{'$set':{'sent':1}},{'multi': true });
