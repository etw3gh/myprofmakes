myprof.README
=============

MongoDB Sunshine List  Queries 
=============


Q: Highest paid Ryerson professor
-------------
    db.sslist.aggregate([{'$match': {"university":/^ryerson university$/i, 
                                     "title":/prof/i}}, 
                                    {"$sort":{"salary":-1}},
                                    {"$limit":1}]){
                                    
    "result" : [
    {
        "_id" : ObjectId("523dfc7b809116eb4946ed62"),
        "salary" : 262693,
        "has_pretentious_title" : 0,
        "first_name" : "ANASTASIOS",
        "last_name" : "VENETSANOPOULOS",
        "long_name_accomodation" : 0,
        "title" : "Professor",
        "taxable_benefit" : 1075,
        "university" : "Ryerson University"
    }],"ok" : 1}
    
--------------------------------------------------------------------------
Q: Highest paid teaching professor in Ontario
-------------
    db.sslist.aggregate([{'$match': {"title":/prof/i}}, 
                         {"$sort":{"salary":-1}},
                         {"$limit":1}]){

    "result" : [
    {
        "_id" : ObjectId("523dfc7a809116eb4946e2b5"),
        "salary" : 506246,
        "has_pretentious_title" : 0,
        "first_name" : "MOHAMED ABDELAZIZ",
        "last_name" : "ELBESTAWI",
        "long_name_accomodation" : 0,
        "title" : "Vice-President Research/Professor",
        "taxable_benefit" : 9921,
        "university" : "McMaster University"
    }],"ok" : 1}
    
--------------------------------------------------------------------------
Q: Highest paid department chair at Ryerson
-------------
    db.sslist.aggregate([{'$match': {"university":/^ryerson university$/i, 
                                     "title":/chair/i}},
                         {"$sort":{"salary":-1}},
                         {"$limit":1}]){

    "result" : [
    {
        "_id" : ObjectId("523dfc7b809116eb4946ea62"),
        "salary" : 191423,
        "has_pretentious_title" : 0,
        "first_name" : "MARTIN",
        "last_name" : "ANTONY",
        "long_name_accomodation" : 0,
        "title" : "Chair, Psychology",
        "taxable_benefit" : 1015,
        "university" : "Ryerson University"
    }],"ok" : 1}
    
--------------------------------------------------------------------------

Q: Salaries plus details of all department chairs (only showing first page of results or top earners)
-------------
    db.sslist.find({"university":/^ryerson university$/i, "title":/chair/i}, 
                   {"last_name":1, "_id":0,"salary":1,"title":1}).sort({"salary":-1})

    { "salary" : 191423, "last_name" : "ANTONY", "title" : "Chair, Psychology" }
    { "salary" : 184503, "last_name" : "MARTIN", "title" : "Chair, Hospitality and Tourism Management" }
    { "salary" : 182442, "last_name" : "MITCHELL", "title" : "Chair, Interior Design" }
    { "salary" : 178872, "last_name" : "YUAN", "title" : "Chair, Electrical Engineering" }
    { "salary" : 178545, "last_name" : "UPRETI", "title" : "Chair, Chemical Engineering" }
    { "salary" : 177837, "last_name" : "LIN", "title" : "Chair, Global Management Studies" }
    { "salary" : 173653, "last_name" : "SHANNON", "title" : "Chair, Theatre School" }
    { "salary" : 171987, "last_name" : "SENNAH", "title" : "Chair, Civil Engineering" }
    { "salary" : 171766, "last_name" : "GOSS", "title" : "Chair, Finance" }
    { "salary" : 169443, "last_name" : "SCHRYER", "title" : "Chair, Professional Communications" }
    { "salary" : 168001, "last_name" : "BENN", "title" : "Chair, History" }
    { "salary" : 166666, "last_name" : "MAZEROLLE", "title" : "Chair, Human Resources and Organizational Behaviour" }
    { "salary" : 166287, "last_name" : "ROCHE", "title" : "Chair, Economics" }
    { "salary" : 165758, "last_name" : "PEJOVIC-MILIC", "title" : "Chair, Physics" }
    { "salary" : 165741, "last_name" : "KAPLAN", "title" : "Chair" }
    { "salary" : 165307, "last_name" : "SUGIMAN", "title" : "Chair, Sociology" }
    { "salary" : 165129, "last_name" : "SADEGHIAN", "title" : "Chair, Computer Science" }
    { "salary" : 160990, "last_name" : "DUTIL", "title" : "Chair, Politics and Public Administration" }
    { "salary" : 156967, "last_name" : "SYDOR", "title" : "Chair, Accounting" }
    { "salary" : 156842, "last_name" : "WALSH", "title" : "Chair, Aerospace Engineering" }

--------------------------------------------------------------------------

Q: Average prof salary at Ryerson: 
-------------
   (among those making 100K or more with 'professor' in their title)

    db.sslist.aggregate({$match:{"university":/ryerson/i,"title":/professor/i}}, 
                        {$group : { _id : { university : "$university" }, 
                        "Average Professor Salary" : { $avg :"$salary" } } } ){
    
    "result" : [
    {
        "_id" : {
            "university" : "Ryerson University"
        },
        "Average Professor Salary" : 138797.01487603306
    }],"ok" : 1}
    
--------------------------------------------------------------------------

Q: Details for Dr. Josh Panar.
-------------
    db.sslist.find({"last_name": /panar/i, "first_name": /josh/i}).pretty(){
    
    "_id" : ObjectId("523dfc7b809116eb4946ec92"),
    "salary" : 168348,
    "has_pretentious_title" : 0,
    "first_name" : "JOSHUA",
    "last_name" : "PANAR",
    "long_name_accomodation" : 0,
    "title" : "Professor",
    "taxable_benefit" : 888,
    "university" : "Ryerson University"}
    
--------------------------------------------------------------------------
Pretentiously Long Titles
-------------

Q: Find professors of any school who have pretentious titles. DISCLAIMER: pretentious only means the title takes up more than 1 line and required more work to scrape out of the data set. But seriously, many titles wanted to go longer than 2 lines, where they have been uncerimoniously cut off.
(TODO: fix to include entire title and note longest title in character length as the most pretentious).

NOTE: these are all 6 figure salaries, not 5

    db.sslist.find({"has_pretentious_title":1}, 
                   {"last_name":1, 
                    "_id":0,
                    "salary":1,
                    "title":1}).sort({"salary":-1})
                   
    { "salary" : 427794, "last_name" : "MOLDOVEANU", "title" : "Associate Dean, Masters of Business Administration Program and Professor of Business" }
    { "salary" : 411570, "last_name" : "REZNICK", "title" : "Dean - Faculty of Health Science, Professor - Faculty of Health Science, Professor - Surgery," }
    { "salary" : 393640, "last_name" : "DACIN", "title" : "Professor - School of Business, Director (Centre for Responsible Leadership) - School of" }
    { "salary" : 371647, "last_name" : "CLEARY", "title" : "Professor - School of Business, Director (Masters of Management In Finance) - School of" }
    { "salary" : 344530, "last_name" : "GOTLIEB", "title" : "Professor, Laboratory Medicine and Pathobiology and Interim Vice Dean, Graduate and Life" }
    { "salary" : 344166, "last_name" : "MALO", "title" : "Managing Director, Investment Strategy and Co-Chief Information  Officer, University of Toronto" }
    { "salary" : 322339, "last_name" : "MURRAY", "title" : "Associate Professor - School of Business, Associate Dean (Masters of Business Administration" }
    { "salary" : 314971, "last_name" : "GOLDREICH", "title" : "Academic Director, Morning and Evening Masters of Business Administration Programs and" }
    { "salary" : 313004, "last_name" : "PATTERSON", "title" : "Full Professor (Seconded as President and Chief Executive Officer, Council of Ontario" }
    { "salary" : 300968, "last_name" : "MEYER", "title" : "Eisenhauer Chair (Clinical Cancer Research) - Oncology, Director - National Cancer Institute," }
    { "salary" : 296005, "last_name" : "STEIN", "title" : "University Professor of Political Science, Director of the Munk School of Global Affairs and" }
    { "salary" : 266717, "last_name" : "ANDREWS", "title" : "Professor and Chair, Banting and Best Department of Medical Research; Director, Centre for" }
    { "salary" : 254487, "last_name" : "BUCHAN", "title" : "Professor of Laboratory Medicine and Pathobiology and Vice Dean, Research and International" }
    { "salary" : 244202, "last_name" : "GILLESPIE", "title" : "Legal Counsel to Office of Vice President and Provost and Vice President Human Resources" }
    { "salary" : 242895, "last_name" : "SARGENT", "title" : "Professor of Electrical and Computer Engineering, Canada Research Council Chair, Vice" }
    { "salary" : 241613, "last_name" : "BROOKS", "title" : "Professor of Business Ethics, Director of Master of Management and Professional Accounting" }
    { "salary" : 236220, "last_name" : "LEVIN", "title" : "Professor - School of Business, Director (Masters of Management Analytics) - School of" }
    { "salary" : 230065, "last_name" : "VERMA", "title" : "Professor, Family and Community Medicine, Deputy Dean, Associate Vice-Provost, Health" }
    { "salary" : 229714, "last_name" : "DUBEY", "title" : "Lecturer - School of Business, Director (Queen's - Cornell Executive Masters of Business" }
    { "salary" : 227406, "last_name" : "MACKINNON", "title" : "Department Head - Economics, Professor - Economics, Sir Edward Peacock Professor -" }
