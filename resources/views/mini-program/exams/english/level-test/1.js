let questions = [
    {
        question:'(1)	Is this seat taken?',
        answer1:'(a)	No, it isn’t.',
        answer2:'(b)	Daniel took it too far.',
        answer3:'(c)	No, it does.',
    },
    {
        question:'(2)	Angela is ….. university student.',
        answer1:'(a)	a',
        answer2:'(b)	an',
        answer3:'(c)	the'
        
    },
    {
        question:'(3)	Would you mind if I borrow your book for a few days?',
        answer1:'(a)	What do you do?',
        answer2:'(b)	Never mind.',
        answer3:'(c)	Not at all.'
    },
    {
        question:'(4)	Are you still free or do you have ………?',
        answer1:'(a)	plan',
        answer2:'(b)	business',
        answer3:'(c)	meeting ',
        answer4:'(d)	plans'

    },
    {
        question:'(5)	When you board a plane, you have to …….your seatbelt.',
        answer1:'(a)	load',
        answer2:'(b)	fasten',
        answer3:'(c)	connect',
        answer4:'(d)	screw'

    },
    {
        question:'(6)	I quit my previous job because I had no …….to sleep.',
        answer1:'(a)	place',
        answer2:'(b)	time',
        answer3:'(c)	moment',
        answer4:'(d)	way'

    },
    {
        question:'(7)	She left her hometown …..three years ago.',
        answer1:'(a)	since',
        answer2:'(b)	when',
        answer3:'(c)	then ',
        answer4:'(d)	already'

    },
    {
        question:'(8)	If I had studied, I ……….my exam.',
        answer1:'(a)	will pass',
        answer2:'(b)	would pass',
        answer3:'(c)	would have passed'

    },
    {
        question:'(9)	There are several ……..to a matter that we must take in consideration.   ',
        answer1:'(a)	opinions',
        answer2:'(b)	features',
        answer3:'(c)	sides',
        answer4:'(d)	factors'

    },
    {
        question:'(10)	 Will you eat ……. rice today since you are on a diet?',
        answer1:'(a)	more',
        answer2:'(b)	less',
        answer3:'(c)	several',
        answer4:'(d)	lots'

    },
    {
        question:'(11)	 I don’t have …… money right now.',
        answer1:'(a)	some',
        answer2:'(b)	any',
        answer3:'(c)	many'   

    },
    {
        question:'(12)	 Yesterday was my bad hair day and my hair was very ….. damaged.',
        answer1:'(a)	little',
        answer2:'(b)	much',
        answer3:'(c)	many',
        answer4:'(d)	lightly'

    },
    {
        question:'(13)	 Such a …… dog cannot be kept inside the house! He will wreak havoc.',
        answer1:'(a)	fierce',
        answer2:'(b)	big',
        answer3:'(c)	clumsy',
        answer4:'(d)	stupid'

    },
    {
        question:'(14)	 Suzy loves to ……. Yoga',
        answer1:'(a)	go',
        answer2:'(b)	do',
        answer3:'(c)	play',
        answer4:'(d)	make'

    },
    {
        question:'(15)	 Geographical definition of the Sahel …. Can vary.',
        answer1:'(a)	region',
        answer2:'(b)	nation',
        answer3:'(c)	country',
        answer4:'(d)	union'

    },
    {
        question:'(16)	 It rained heavily just when she’s so …… dressed and ready to party.',
        answer1:'(a)	beautifully',
        answer2:'(b)	raggedly',
        answer3:'(c)	vainly',
        answer4:'(d)	thickly'

    },
    {
        question:'(17)	 Sai Sai Kham Leng was born …….. 1979.',
        answer1:'(a)	at',
        answer2:'(b)	on',
        answer3:'(c)	in'

    },
    {
        question:'(18)	 Try to look at a problem from another ….. and you’ll find the answer.',
        answer1:'(a)	angle',
        answer2:'(b)	angel',
        answer3:'(c)	ankle',
        answer4:'(d)	uncle'

    },
    {
        question:'(19)	 Don’t ….. these lovebirds! Let them be.',
        answer1:'(a)	seperate',
        answer2:'(b)	severe',
        answer3:'(c)	separate',
        answer4:'(d)	several'

    },
    {
        question:'(20)	 I love …….. music.',
        answer1:'(a)	reading',
        answer2:'(b)	listening',
        answer3:'(c)	walking',
        answer4:'(d)	running'

    },
    {
        question:'(21)	 Ni Ni always …… to bed at 9 pm.',
        answer1:'(a)	go  ',
        answer2:'(b)	goes',
        answer3:'(c)	sleep',
        answer4:'(d)	lying'

    },
    {
        question:'(22)	 I forgot to bring my phone with me. It’s …… my table.',
        answer1:'(a)	on',
        answer2:'(b)	in',
        answer3:'(c)	above',
        answer4:'(d)	off'

    },
    {
        question:'(23)	 The clouds are ……..',
        answer1:'(a)	black',
        answer2:'(b)	white',
        answer3:'(c)	yellow',
        answer4:'(d)	brown'

    },
    {
        question:'(24)	 ……….. matter how hard you try, you will never beat me.',
        answer1:'(a)	No',
        answer2:'(b)	Any',
        answer3:'(c)	How ',
        answer4:'(d)	Every'

    },
    {
        question:'(25)	 Thu Ya is crying because his crush rejected him. She …….him back.',
        answer1:'(a)	doesn’t love',
        answer2:'(b)	loves',
        answer3:'(c)	don’t love'

    }
];
function initialLoad(){
    let form1 = document.getElementById('form1');
    console.log(questions[1].answer5);
    for(var i=0 ; i < questions.length ; i++){
            var cardStr = `<div class="card text-left"><div class="card-header bg-info">${questions[i].question}</div><div class="card-body"><input type="radio" name="${'answer'+(1+i)}" id="${questions[i].answer1+i}" value="a"><label for="${questions[i].answer1+i}">${questions[i].answer1}</label><br><input type="radio" name="${'answer'+(1+i)}" id="${questions[i].answer2+i}" value="b"><label for="${questions[i].answer2+i}">${questions[i].answer2}</label>`;
            //If user didn't select and button(send 'null')
            var cardEnd = `<br><input style="display:none" type="radio" name="${'answer'+(1+i)}" value="null" checked></div></div>`;
            if(questions[i].answer3 != undefined){
                cardStr +=`<br><input type="radio" name="${'answer'+(1+i)}" id="${questions[i].answer3+i}" value="c"><label for="${questions[i].answer3+i}">${questions[i].answer3}</label>`
            }
            if(questions[i].answer4 != undefined){
                cardStr +=`<br><input type="radio" name="${'answer'+(1+i)}" id="${questions[i].answer4+i}" value="d"><label for="${questions[i].answer4+i}">${questions[i].answer4}</label>`;
            }
            form1.innerHTML += cardStr + cardEnd;
        
    }
    form1.innerHTML+='<button style="margin-bottom:24px;margin-top:25px" type="submit" class="btn btn-warning btn-block text-white">Submit</button>';
    
};


