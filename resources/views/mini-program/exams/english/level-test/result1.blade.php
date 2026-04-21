@php

$q[0]['question']="(1) Is this seat taken?";
$q[1]['question']="(2)	Angela is ….. university student.";
$q[2]['question']="(3)	Would you mind if I borrow your book for a few days?";
$q[3]['question']="(4)	Are you still free or do you have ………?";
$q[4]['question']="(5)	When you board a plane, you have to …….your seatbelt.";
$q[5]['question']="(6)	I quit my previous job because I had no …….to sleep.";
$q[6]['question']="(7)	She left her hometown …..three years ago.";
$q[7]['question']="(8)	If I had studied, I ……….my exam.";
$q[8]['question']="(9)	There are several ……..to a matter that we must take in consideration.";
$q[9]['question']="(10)	 Will you eat ……. rice today since you are on a diet?";
$q[10]['question']="(11)	 I don’t have …… money right now.";
$q[11]['question']="(12)	 Yesterday was my bad hair day and my hair was very ….. damaged.";
$q[12]['question']="(13)	 Such a …… dog cannot be kept inside the house! He will wreak havoc.";
$q[13]['question']="(14)	 Suzy loves to ……. Yoga";
$q[14]['question']="(15)	 Geographical definition of the Sahel …. Can vary.";
$q[15]['question']="(16)	 It rained heavily just when she’s so …… dressed and ready to party.";
$q[16]['question']="(17)	 Sai Sai Kham Leng was born …….. 1979.";
$q[17]['question']="(18)	 Try to look at a problem from another ….. and you’ll find the answer.";
$q[18]['question']="(19)	 Don’t ….. these lovebirds! Let them be.";
$q[19]['question']="(20)	 I love …….. music.";
$q[20]['question']="(21)	 Ni Ni always …… to bed at 9 pm.";
$q[21]['question']="(22)	 I forgot to bring my phone with me. It’s …… my table.";
$q[22]['question']="(23)	 The clouds are ……..";
$q[23]['question']="(24)	 ……….. matter how hard you try, you will never beat me.";
$q[24]['question']="(25)	 Thu Ya is crying because his crush rejected him. She …….him back.";

$q[0]['ans']="(a)	No, it isn’t.";
$q[1]['ans']="(a)	a";
$q[2]['ans']="(c)	Not at all.";
$q[3]['ans']="(d)	plans";
$q[4]['ans']="(b)	fasten";
$q[5]['ans']="(b)	time";
$q[6]['ans']="(a)	since";
$q[7]['ans']="(c)	would have passed";
$q[8]['ans']="(d)	factors";
$q[9]['ans']="(b)	less";
$q[10]['ans']="(b)	any";
$q[11]['ans']="(b)	much";
$q[12]['ans']="(a)	fierce";
$q[13]['ans']="(b)	do";
$q[14]['ans']="(a)	region";
$q[15]['ans']="(a)	beautifully";
$q[16]['ans']="(c)	in";
$q[17]['ans']="(a)	angle";
$q[18]['ans']="(c)	separate";
$q[19]['ans']="(b)	listening";
$q[20]['ans']="(b)	goes";
$q[21]['ans']="(a)	on";
$q[22]['ans']="(b)	white";
$q[23]['ans']="(a)	No";
$q[24]['ans']="(a)	doesn’t love";



@endphp


<html>
    
    <header>
        
        <meta charset="utf-8" />
        <meta
          name="viewport"
          content="width=device-width, initial-scale=1, shrink-to-fit=no"
        />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link
          rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"
          integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l"
          crossorigin="anonymous"
        />
        <title>English Exam</title>
        
        <style>
            .button{
                padding:20px;
            }
        </style>
    </header>
    <body style="padding:10px">
        
        <h3 class="text-primary">Level Test Result</h3>
        <p><i class="fa fa-mortar-board"></i> <h5>{{$level}}</h5></p>

        <br>
        <div style="text-align:center">
            <span class="btn btn-primary" style="padding:15px; color:white; border-radius:50%; width:50px;height:50px; font-size:15px;"><b>{{$result}}</b></span>
        </div>
        <br>
        <hr style="height:10px;">
        <p>The correct answers are listed below.</p>
    
    @foreach($q as $que)
       
       <div style="margin:5px; border-radius:7px;">
        <div style="background-color:rgb(240,240,240); padding:7px;">
              {{$que['question']}}
        </div>
        
        <div style="background-color:rgb(230,230,230); padding:7px;padding-left:30px;">
              {{$que['ans']}}
        </div>
           
       </div>
 
    @endforeach
    <script
      src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
      integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns"
      crossorigin="anonymous"
    ></script>

    </body>
    
</html>