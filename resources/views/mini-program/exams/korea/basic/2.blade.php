<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Basic Exam2</title>


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

//question  1 to 10
$question[] ="1. 날씨가 좋습니다. (       ) 이 맑습니다.";
$answer[]=["공기","하늘","밤","구름"];

$question[]="2. 저는 (       ) 에 갔습니다. 책을 샀습니다.";
$answer[]=["도서관","식당","학교","서점"];

$question[]="3. 수빈 씨와 저는 오늘 (         ) 만났습니다.";
$answer[]=["가장","먼저 ","처음","제일 "];

$question[]="4. 가방에 책이 많습니다. 가방이 (        ) 무겁습니다.";
$answer[]=["너무","오래 ","자주"," 일찍 "];

$question[]="5.  머리가 (      ). 그래서 약을 먹습니다. ";
$answer[]=["나쁩니다","아픕니다","예쁩니다","좋습니다"];

$question[]="6. 오빠는 춤을 배웠습니다. 춤을 잘 (      ).";
$answer[]=["모릅니다","씁니다 ","부릅니다","춥니다"];

$question[]="7. 교실에 학생들이 없습니다. 그래서  (         ).";
$answer[]=["무섭습니다","조용합니다","작습니다"," 깨끗합니다"];

$question[]="8. 이 그림이 마음에 (        ). 이것을 사고 싶습니다.";
$answer[]=["잡니다","듭니다","납니다","옵니다"];

$question[]="9. 운동을 많이 합니다. 그래서  (           ).";
$answer[]=["친절합니다","따뜻합니다","좋습니다","건강합니다"];

$question[]="10. 바람이 많이 붑니다. 창문을 (          ).";
$answer[]=["닦습니다 ","엽니다","닫습니다","놓습니다"];

//question 11 to 20
$question[]="우산이 있어요?";
$answer[]=["네, 우산이에요.","아니요, 우산을 써요.","네, 우산이 있어요.","아니요. 우산이 아니에요."];

$question[]="오늘 회사에 가요?";
$answer[]=["네, 회사에 없어요.","아니요, 회사에 안 가요.","네, 회사가 아니에요.","아니요, 회사에서 일해요."];

$question[]="예나 씨, 저 먼저 갈게요.";
$answer[]=["잘 가요.","고마워요.","반가워요.","안녕하세요."];

$question[]="늦어서 미안해요.";
$answer[]=["고마워요.","죄송해요.","아니에요.","부탁해요."];

$question[]="언제 박물관에 갈 거예요?";
$answer[]=["저녁에 갈 거예요.","친구하고 갈 거예요.","시장에 갈 거예요.","같이 갈 거예요."];

$question[]="한국어가 어때요?";
$answer[]=["좀 어려워요.","배우고 있어요.","한국에 가요.","노래를 좋아해요"];

$question[]="집에 어떻게 가요?";
$answer[]=["지금 가요.","공원에 가요.","버스로 가요.","친구하고 가요."];

$question[]="여기 앉으세요.";
$answer[]=["그렇습니다.","고맙습니다.","환영합니다.","축하합니다."];

$question[]="예나 씨, 연필 좀 주세요.";
$answer[]=["괜찮아요.","반가워요.","여기 있어요.","잘 지냈어요."];

$question[]="구두가 커요?";
$answer[]=["네, 구두예요.","네, 구두가 예뻐요.","아니요, 구두가 있어요.","아니요, 구두가 작아요."];

//question no 21 to 30

$question[] ="21. 비빔밥이 맛있습니다. 불고기도 맛있습니다.";
$answer[]=["고기","시간","요일","음식"];

$question[]="22.  날씨가 따뜻합니다. 꽃도 많이 핍니다.";
$answer[]=["산","가을","봄","날짜"];

$question[]="23.  제 친구 히카루는 일본 사람입니다. 저는 한국 사람입니다. ";
$answer[]=["장소","친구 ","나라","가족 "];

$question[]="24. 토요일에 공원에 갑니다. 일요일에는 쉽니다. ";
$answer[]=["주말","여름 ","오후"," 달력"];

$question[]="25. 저는 수영을 좋아합니다. 내일도 친구들과 수영장에 갈 겁니다.";
$answer[]=["저녁","취미","주말","친구"];

$question[]="26.  제 언니는 25 살입니다. 저는 20살입니다.";
$answer[]=["이름","가족 ","숫자","나이"];

$question[]="27.  떡볶이는 3000 원입니다. 만두는 5000원입니다.";
$answer[]=["옷","값","일"," 맛"];

$question[]="28.  바람이 붑니다. 시원합니다.";
$answer[]=["날씨","음식","여름","새벽"];

$question[]="29.  사과는 빨간색입니다. 바나나는 노란색입니다. ";
$answer[]=["과자","과일","채소","색깔"];

$question[]="30. 내일 친구를 만납니다. 영화도 보고 쇼핑도 할 겁니다. ";
$answer[]=["장소 ","영화","계획","날짜"];


//question no 31 to 40

$question[]="31-cooker.jpg";
$answer[]=["요리사","경찰","간호사","서냉님"];

$question[]="32-library.jpg";
$answer[]=["꽃집","도서관","은행","영화관"];

$question[]="33-flowers.jpg";
$answer[]=["나무","모자","옷","꽃다발"];

$question[]="34-cherry.jpg";
$answer[]=["참외","체리","귤","딸기"];

$question[]="35-desk.jpg";
$answer[]=["의자","책상","침대","냉장고"];

$question[]="36-skirt.jpg";
$answer[]=["바지","신발","원피스","티셔츠"];

$question[]="37-cat.jpg";
$answer[]=["호랑이","고양이","사자","여우"];

$question[]="38-chinese.jpg";
$answer[]=["일본","중국","한국"," 태국"];

$question[]="39-clips.jpg";
$answer[]=["머리카락","머리띠","머리핀","머리끈"];

$question[]="40-white.jpg";
$answer[]=["파란색","노란색","빨간색 ","하얀색"];

//question no 41 to 50
$question[] ="41.   몇시 (        ) 에 옵니까?";
$answer[]=["가","는","에","를"];

$question[]="42.  여기 (          ) 시장입니다.";
$answer[]=["가","에","와","를"];

$question[]="43. 지갑에 돈 (         ) 없어요.";
$answer[]=["까지","마다 ","밖에","부터 "];

$question[]="44. 동생이 책 (         )읽어요.";
$answer[]=["을","은 ","이"," 와 "];

$question[]="45. 김치는 (         ) 맵습니다.";
$answer[]=["맛있으면 ","맛있지만","맛있어서","맛있으나 "];

$question[]="46. 머리가 깁니다. 그래서  (            ) 싶습니다.";
$answer[]=["자르고","나오고","가지고","마시고"];

$question[]="47. 예나 씨는 대학생입니다. 수빈 씨 (            ) 대학생입니다.";
$answer[]=["는","도","에"," 가"];

$question[]="48. 어제 친구 (            ) 백화점에 갔어요.";
$answer[]=["과","하고","이랑","고"];

$question[]="49. 제 오빠는 농구 선수  (        ) 농구를 잘해요.";
$answer[]=["처럼","에게","마다","밖에"];

$question[]="50. 이따가 저녁을 (            ) 영화를 볼 거예요. ";
$answer[]=["먹어서 ","먹으러","먹고","먹으연"];



if (!function_exists('questionFormatOne')) {
    function questionFormatOne($question, $answer, $no) {
        echo $question;
        echo "<div style='display:flex; flex-wrap: wrap; margin-top:10px;'>";
        foreach ($answer as $i => $text) {
            $ansNo = $i + 1;
            echo "
                <div class='form-check' style='flex-basis: 50%; min-width: 150px;'>
                    <input class='form-check-input' type='radio' name='$no' id='ans$no$ansNo'>
                    <label class='form-check-label' for='ans$no$ansNo'>
                    <span id='right$no$ansNo'> $ansNo. $text </span>
                    <i id='correct$no$ansNo' class='material-icons' style='font-size:18px;color:rgb(0, 255, 0);display:none;'>check_circle</i>
                    <i id='error$no$ansNo' class='material-icons' style='font-size:18px;color:red;display:none;'>cancel</i>                   
                    </label>
                </div>
            ";
        }
        echo "</div><br><br>";
    }
}

if (!function_exists('questionFormatTwo')) {
    function questionFormatTwo($question, $answer, $no) {
        echo $no . " .";
        echo " <div style='display:flex; flex-wrap: wrap;'>";
        echo " <div style='flex: 1; min-width: 120px;'><img src='$question' style='width:120px; height:90px; border-radius:10px;'></div>";
        echo "<div style='flex: 2; min-width: 200px;'>";
        foreach ($answer as $i => $text) {
            $ansNo = $i + 1;
            echo "
                <div class='form-check'>
                    <input class='form-check-input' type='radio' name='$no' id='ans$no$ansNo'>
                    <label class='form-check-label' for='ans$no$ansNo'>
                    <span id='right$no$ansNo'> $ansNo. $text </span>
                    <i id='correct$no$ansNo' class='material-icons' style='font-size:18px;color:rgb(0, 255, 0);display:none;'>check_circle</i>
                    <i id='error$no$ansNo' class='material-icons' style='font-size:18px;color:red; display:none;'>cancel</i>    
                    </label>
                </div>
            ";
        }
        echo "</div></div><br><br>";
    }
}

if (!function_exists('questionFormatThree')) {
    function questionFormatThree($question, $answer, $no) {
        echo $no . " .<br>";
        echo "A : " . $question . "<br>";
        echo "B : ..............";
        echo "<div class='row'>";
        foreach ($answer as $i => $text) {
            $ansNo = $i + 1;
            echo "
                <div class='col-xl-3 col-lg-3 col-md-3 col-sm-6 col-xs-6'>
                    <div class='form-check'>
                        <input class='form-check-input' type='radio' name='$no' id='ans$no$ansNo'>
                        <label class='form-check-label' for='ans$no$ansNo'>
                        <span id='right$no$ansNo'> $ansNo. $text </span>
                        <i id='correct$no$ansNo' class='material-icons' style='font-size:18px;color:rgb(0, 255, 0); display:none;'>check_circle</i>
                        <i id='error$no$ansNo' class='material-icons' style='font-size:18px;color:red; display:none;'>cancel</i>    
                        </label>
                    </div>
                </div>
            ";
        }
        echo "</div><br><br>";
    }
}

?>
 
<body>
<div class="container">

    <div style="position:fixed;right: 10px;;"><span class="fixedTime" id="timer" >Time Left</span></div><br>
    <h3 align="center">Calamus Education</h2>
    <h5 align="center">Easy Korean - Basic Course Exam 2</h5>
   
    <span >Allowed Time - 30 min</span>
    <span style="float:right">Marks - 50</span>
    <br>
    <br>
    <p align="justify" class="question" >다음을 보고 빈칸에 들어갈 알맞은 것을 고르십시오.<br/>
    
    [ အောက်ပါ စာကြောင်းကို ဖတ်ပြီး ကွက်လပ်ထဲ ထည့်ရမယ့် စာလုံးကို ရွေးပါ။]</p>
 

    <?php  
        //for question 1 to 10
        for($i=0;$i<10;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <p align="justify" class="question" >다음 대화를 보고  B  가 이어서 할 말을 고르십시오.<br/>
    [ အောက်ပါ စကားပြောကို ဖတ်ပြီး B ဆက်ပြောမယ့် စာကြောင်းကို ရွေးပါ။]</p>

    <?php  
        //for question 11 to 20
        for($i=10;$i<20;$i++){
            questionFormatThree($question[$i],$answer[$i],$i+1);
        }
    ?>

    <p align="justify" class="question" >무엇에 대한 이야기입니까? 알맞은 것을 고르십시오.<br/>  
    [ ဘာအကြောင်းနဲ့ ပက်သက်ပြီး ပြောနေတာလဲ? ကိုက်ညီတဲ့ စကားလုံးကို ရွေးချယ်ပါ]</p>

    <?php  
        //for question 21 to 30
        for($i=20;$i<30;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>


    <p align="justify" class="question" >다음 사진을 보고 알맞은 어휘를 고르십시오.<br/>  
    [ အောက်ပါ ပုံကို ကြည့်ပြီး ပုံနဲ့ ကိုက်ညီတဲ့ စာလုံးကို ရွေးပါ။]</p>

    <?php  
        //for question 31 to 40
        for($i=30;$i<40;$i++){
            questionFormatTwo("https://www.calamuseducation.com/uploads/lessons/images/".$question[$i],$answer[$i],$i+1);
        }
    ?>



    <p align="justify" class="question" > 다음을 보고 빈칸에 들어갈 알맞은 문법을 고르십시오.<br/>  
    [အောက်ပါ စာကြောင်းကို ဖတ်ပြီး ကွက်လပ်ထဲ ထည့်ရမယ့် Grammar ကို ရွေးပါ။]</p>

    <?php  
        //for question 41 to 50
        for($i=40;$i<50;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <div style="display:flex;">
        <button id="bt-checkAns" class="btn btn-primary" onclick="checkingAnswer();">Check Answer</button>
        <button id="bt-showAns" class="btn btn-danger" onclick="showAnswer();" style="display:none;">Show Answer</button>
    </div>
   

    <p id="final-result">
      
    </p>

</div>


    <script>
       
        var ansChecker=["12","24","33","41","52","64","72","82","94","103","113","122","131","143","151","161","173","182","193","204","214","223","233","241","252","264","272","281","294","303","311","322","334","342","352","363","372","382","393","404","413","421","433","441","452","461","472","482","491","503"];
       
        function showAnswer(){
            for(var i=0,j=ansChecker.length;i<j;i++){
              var ansSpan =document.getElementById("right"+ansChecker[i]);
              ansSpan.setAttribute('style','background-color:blue;padding:5px;border-radius:3px;color:white; font-weight:bold;');
            }    
        }

        function checkingAnswer(){

             var result=0;

            document.getElementById("bt-showAns").setAttribute('style','');

            // var inputEle=document.getElementsByTagName('span');
            // for(var i=0,j=inputEle.length;i<j;i++){
            //     inputEle[i].setAttribute('style','');
            // }
           
            for(var i=0,j=ansChecker.length;i<j;i++){
              var ansSpan =document.getElementById("right"+ansChecker[i]);
              ansSpan.setAttribute('style','');
            }  

            for(var i=0;i<50;i++){
                var no=i+1;
                for(var j=1;j<5;j++){
                    var ansInput=document.getElementById("ans"+no+j);
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

            console.log(result);
            
            document.getElementById("bt-checkAns").setAttribute('style','display:none;');
            showFinalResult(result);
            saveExamResult(result);
            clearInterval(stop);
           
        }

        function showFinalResult(result){
            var finalResult=document.getElementById('final-result');
            if(result<20){
                finalResult.setAttribute('style','background-color:red');
                if(result <1){
                    finalResult.innerHTML="Fail <br> "+result+"/50 mark";
                }else{
                    finalResult.innerHTML="Fail <br> "+result+"/50 marks";
                }
              
           }else if(result>=21 && result<=30){
                finalResult.setAttribute('style','background-color:rgb(255,165,0);');
                finalResult.innerHTML="Good <br> "+result+"/50 marks";
           }else if(result>30 && result<=40){
                finalResult.setAttribute('style','background-color:yellow; color:black;');
                finalResult.innerHTML="Very Good <br> "+result+"/50 marks";
           }else{
                finalResult.setAttribute('style','background-color:green');
                finalResult.innerHTML="Excellent <br> "+result+"/50 marks";
           }
        }
        
        function saveExamResult(result){
            var ajax=new XMLHttpRequest();
            ajax.onload=function(){
                if(ajax.status==200 || ajax.readyState==4){
            
                }
            }
            ajax.open("POST","https://www.calamuseducation.com/calamus/api/exam/result/update",true);
            ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajax.send("userId=<?php echo $userId?>&major=korea&test=basic_exam&result="+result);

        }


        var second=0;
        var timerEle=document.getElementById('timer');
        var stop =setInterval(updateTimer,1000);

        function updateTimer(){
            second++;
           timerEle.innerHTML=formatTime(second);

           if(second==1800){
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

