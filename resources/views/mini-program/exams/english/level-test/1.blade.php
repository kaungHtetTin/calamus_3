<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Level Test</title>


    <style>
        .image{
            width: 100%;
            height: auto;
           
            align-self: center;
        }
        .warnPara{
          
            text-align: justify;
            background-color: bisque;
            padding: 10px;
            border-radius: 5px;
        }
        body{
            padding: 20px;
        }

        button{
            flex: 1;
            margin: 5px;
        }
        .answer{
            color: white;
            background-color: rgb(0, 201, 27);
            padding: 5px;
            border-radius: 2px;
            margin-right: 10px;
            
        }
        .question{
            background-color:rgb(0, 140, 233);
            border-radius: 3px;
            padding: 10px;
            color:white;
            font-weight:bold;
        }
        #final-result{
            padding: 7px;
            border-radius:3px;
            margin:15px;
            font-weight:bold;
            font: size 20px;
            text-align:center;
            color:white;
        }

        .fixedTime{
            padding:5px;
            border-radius:3px;
            background-color:green;
            color:white;
        }

    </style>

</head>

<?php



$question[]="(1)    Is this seat taken?";
$answer[]=["(a)	No, it isn’t.","(b)	Daniel took it too far.","(c)	No, it does."];

$question[]="(2)	Angela is ….. university student.";
$answer[]=["(a)	a","(b)	an","(c)	the"];

$question[]="(3)	Would you mind if I borrow your book for a few days?";
$answer[]=["(a)	What do you do?","(b)	Never mind.","(c)	Not at all."];

$question[]="(4)	Are you still free or do you have ………?";
$answer[]=["(a)	plan","(b)	business","(c)	meeting","(d)	plans"];

$question[]="(5)	When you board a plane, you have to …….your seatbelt.";
$answer[]=["(a)	load","(b)	fasten","(c)	connect","(d)	screw"];

$question[]="(6)	I quit my previous job because I had no …….to sleep.";
$answer[]=["(a)	place","(b)	time","(c)	moment","(d)	way"];

$question[]="(7)	She left her hometown …..three years ago.";
$answer[]=["(a)	about","(b)	when","(c)	then","(d)	already"];

$question[]="(8)	If I had studied, I ……….my exam.";
$answer[]=["(a)	will pass","(b)	would pass","(c)	would have passed"];

$question[]="(9)	There are several ……..to a matter that we must take in consideration.";
$answer[]=["(a)	issues","(b)	features","(c)	sides","(d)	factors"];

$question[]="(10)	 Will you eat ……. rice today since you are on a diet?";
$answer[]=["(a)	more","(b)	less","(c)	several","(d)	lots"];

$question[]="(11)	 I don’t have …… money right now.";
$answer[]=["(a)	some","(b)	any","(c)	many"];

$question[]="(12)	 Yesterday was my bad hair day and my hair was very ….. damaged.";
$answer[]=["(a)	little","(b)	much","(c)	many","(d)	lightly"];

$question[]="(13)	 Such a …… dog cannot be kept inside the house! He will wreak havoc.";
$answer[]=["(a)	fierce","(b)	big","(c)	clumsy","(d)	stupid"];

$question[]="(14)	 Suzy loves to ……. Yoga";
$answer[]=["(a)	go'","(b)	do","(c)	play","(d)	make"];

$question[]="(15)	 Geographical definition of the Sahel …. Can vary.";
$answer[]=["(a)	region","(b)	nation","(c)	country","(d)	union"];

$question[]="(16)	 It rained heavily just when she’s so …… dressed and ready to party.";
$answer[]=["(a)	beautifully","(b)	raggedly","(c)	vainly","(d)	thickly"];

$question[]="(17)	 Sai Sai Kham Leng was born …….. 1979.";
$answer[]=["(a)	at","(b)	on","(c)	in"];

$question[]="(18)	 Try to look at a problem from another ….. and you’ll find the answer.";
$answer[]=["(a)	angle","(b)	angel","(c)	ankle","(d)	uncle"];

$question[]="(19)	 Don’t ….. these lovebirds! Let them be.";
$answer[]=["(a)	seperate","(b)	severe","(c)	separate","(d)	several"];

$question[]="(20)	 I love …….. music.";
$answer[]=["(a)	reading","(b)	listening","(c)	walking","(d)	running"];

$question[]="(21)	 Ni Ni always …… to bed at 9 pm.";
$answer[]=["(a)	go ","(b)	goes","(c)	sleep","(d)	lying"];

$question[]="(22)	 I forgot to bring my phone with me. It’s …… my table.";
$answer[]=["(a)	on","(b)	in","(c)	above","(d)	off"];

$question[]="(23)	 The clouds are ……..";
$answer[]=["(a)	black","(b)	white","(c)	yellow","(d)	brown"];

$question[]="(24)	 ……….. matter how hard you try, you will never beat me.";
$answer[]=["(a)	No","(b)	Any","(c)	How","(d)	Every"];

$question[]="(25)	 Thu Ya is crying because his crush rejected him. She …….him back.";
$answer[]=["(a)	doesn’t love","(b)	loves","(c)	don’t love"];



function questionFormatThree($question, $answer,$no){
 
    echo  $question."<br>";

    echo "<div class='row'>";

    for($i=0;$i<count($answer);$i++){
        $ansNo=$i+1;
        echo "
            <div class='col-xl-3 col-lg-3 col-md-3 col-sm-6 col-xs-6'>
                <div class='form-check'>
                    <input class='form-check-input' type='radio' name='$no' id='ans$no$ansNo'>
                    <label class='form-check-label' for='ans$no$ansNo'>
                    <span id='right$no$ansNo'>$answer[$i]  </span>
                    <i id='correct$no$ansNo' class='material-icons' style='font-size:18px;color:rgb(0, 255, 0); display:none;'>check_circle</i>
                    <i id='error$no$ansNo' class='material-icons' style='font-size:18px;color:red; display:none;'>cancel</i>    
                    </label>
                </div>
            </div>
        ";
    }
    echo "</div><br><br>";
}

?>
 
<body>
<div class="container">

    <div style="position:fixed;right: 10px;;"><span class="fixedTime" id="timer" >Time Left</span></div><br>
    <h3 align="center">Calamus Education</h2>
    <h4 align="center">Easy English - Level Test</h3>

    <span >Allowed Time - 10 min</span>
    <span style="float:right">Marks - 25</span>
    <br>
    <br>

    <p align="justify" class="question" >This level test includes 25 questions to test your English Level. 
                Please do not use dictionary, google and other materials for the exam.</p>

    <?php  
        //for question 1 to 10
        for($i=0;$i<25;$i++){
            questionFormatThree($question[$i],$answer[$i],$i+1);
        }
    ?>

    <div style="display:flex;">
        <button id="bt-checkAns" class="btn btn-primary" onClick="checkAnswer()" >Check Answer</button>
        <button id="bt-showAns" class="btn btn-danger" onclick="showAnswer();" style="display:none;">Show Answer</button>
    </div>
   

    <p id="final-result">
      
    </p>

</div>

 
    <script>
        var ansChecker=[ '11', '21', '33', '44', '52', '62', '71', '83', '94', '102', '112', '122', '131', '142', '151', '161', '173', '181', '193', '202', '212', '221', '232', '241', '251' ];
        
        function showAnswer(){
            for(var i=0,j=ansChecker.length;i<j;i++){
              var ansSpan =document.getElementById("right"+ansChecker[i]);
              ansSpan.setAttribute('style','background-color:blue;padding:5px;border-radius:3px;color:white; font-weight:bold;');
            }    
        }

        function checkAnswer(){

            var result=0;

            document.getElementById("bt-showAns").setAttribute('Style','');

            for(var i=0,j=ansChecker.length;i<j;i++){
              var ansSpan =document.getElementById("right"+ansChecker[i]);
              ansSpan.setAttribute('style','');
            }  

            for(var i=0;i<50;i++){
                var no=i+1;
                for(var j=1;j<5;j++){
                    var ansInput=document.getElementById("ans"+no+j);
                    if(ansInput!=null){
                        var isChecked=ansInput.checked;
                        if(isChecked){
                            var inputId=ansInput.getAttribute("id");
                            if(inputId=="ans"+ansChecker[i]){
                                result++;
                                document.getElementById('correct'+no+j).setAttribute('style','font-size:18px;color:rgb(0, 255, 0);');
                            }else{
                                document.getElementById('error'+no+j).setAttribute('style','font-size:18px;color:red;');
                            }
                        }
                    }
                }
            }

            console.log(result);
            
            document.getElementById("bt-checkAns").setAttribute('style','display:none;');
            showFinalResult(result);
            saveExamResult(result);
            clearInterval(stop);
           
        }
        
        function saveExamResult(result){
            var ajax=new XMLHttpRequest();
            ajax.onload=function(){
                if(ajax.status==200 || ajax.readyState==4){
            
                }
            }
            ajax.open("POST","https://www.calamuseducation.com/calamus/api/exam/result/update",true);
            ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajax.send("userId=<?php echo $userId?>&major=english&test=level_test&result="+result);

        }

        function showFinalResult(result){
            var finalResult=document.getElementById('final-result');
            if(result<8){
                finalResult.setAttribute('style','background-color:red');
                if(result <1){
                    finalResult.innerHTML="You are basic level. <br> "+result+"/25 mark";
                }else{
                    finalResult.innerHTML="You are basic level. <br> "+result+"/25 marks";
                }
              
           }else if(result>=8 && result<=13){
                finalResult.setAttribute('style','background-color:rgb(255,165,0)');
                finalResult.innerHTML="You are elementary level. <br> "+result+"/25 marks";
           }else if(result>13 && result<=20){
                finalResult.setAttribute('style','background-color:yellow; color:black;');
                finalResult.innerHTML="You are pre-intermediate level. <br> "+result+"/25 marks";
           }else{
                finalResult.setAttribute('style','background-color:green');
                finalResult.innerHTML="You are intermediate level <br> "+result+"/25 marks";
           }
        }

        var second=0;
        var timerEle=document.getElementById('timer');
        var stop =setInterval(updateTimer,1000);

        function updateTimer(){
            second++;
           timerEle.innerHTML=formatTime(second);

           if(second==600){
               clearInterval(stop);
               timerEle.setAttribute('style','background-color:red;');
               checkAnswer();
           }
        }

        function stopExam(){
            var inputRadio=document.getElementsByTagName('input');
            for(var i=0;i<50;i++){
                inputRadio[i].disable=true;
            }
        }

        function formatTime(sec){
            var s=parseInt(sec%60);
            var m =parseInt(sec/60);

            if(m<10){
                if(s <10){
                    return "0"+ m+" : 0"+s;
                }else{
                    return "0"+ m+" : "+s;
                }
            }else{
                if(s <10){
                    return  m+" : 0"+s;
                }else{
                    return  m+" : "+s;
                }
            }
            
        }

    </script>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</html>

