<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Level Two Course Exam</title>


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

        .explanation{
            display:none;
            background-color:#e8f4f8;
            padding:10px;
            margin-top:10px;
            margin-bottom:15px;
            border-radius:5px;
            border-left:4px solid #2196F3;
            font-size:14px;
        }

        .explanation.show{
            display:block;
        }

    </style>

</head>

<?php

$question[] ="1. 비가 오기 __ 우산을 챙기세요.";
$answer[]=["(A) 때문에","(B) 만큼","(C) 처럼","(D) 밖에"];

$question[] ="2. 저기 __ 키가 큰 사람이 제 동생이에요.";
$answer[]=["(A) 있는","(B) 있은","(C) 있을","(D) 계신"];

$question[] ="3. 생일에 친구__ 선물을 받았어요.";
$answer[]=["(A) 한테서","(B) 한테","(C) 께","(D) 로"];

$question[] ="4. 가: 왜 에어컨을 켰어요? / 나: 날씨가 __.";
$answer[]=["(A) 덥잖아요","(B) 덥네요","(C) 더워요","(D) 덥지요"];

$question[] ="5. 하늘을 보니 오후에 비가 올 __.";
$answer[]=["(A) 것 같아요","(B) 수 있어요","(C) 줄 알아요","(D) 지 않아요"];

$question[] ="6. 친구가 밥을 먹는 __ 저는 기다렸어요.";
$answer[]=["(A) 동안","(B) 사이","(C) 때","(D) 중"];

$question[] ="7. 음악을 __ 공부를 해요.";
$answer[]=["(A) 들으면서","(B) 듣고","(C) 들어서","(D) 듣지만"];

$question[] ="8. 밥을 __ 전에 손을 씻으세요.";
$answer[]=["(A) 먹기","(B) 먹은","(C) 먹는","(D) 먹을"];

$question[] ="9. 운동을 __ 후에 샤워를 했어요.";
$answer[]=["(A) 한","(B) 할","(C) 하고","(D) 해서"];

$question[] ="10. 학교에 버스__ 가요.";
$answer[]=["(A) 로","(B) 에","(C) 에서","(D) 와"];

$question[] ="11. 주말에는 책을 읽__ 영화를 봐요.";
$answer[]=["(A) 거나","(B) 지만","(C) 어서","(D) 니까"];

$question[] ="12. 한국에 __ 지 1년이 됐어요.";
$answer[]=["(A) 온","(B) 올","(C) 오는","(D) 오기"];

$question[] ="13. 저는 제주도에 __ 적이 있어요.";
$answer[]=["(A) 간","(B) 갈","(C) 가는","(D) 가고"];

$question[] ="14. 가: 여기 앉아도 돼요? / 나: 네, __ 돼요.";
$answer[]=["(A) 앉아도","(B) 앉아야","(C) 앉으면","(D) 앉아서"];

$question[] ="15. 박물관에서 사진을 찍__ 안 돼요.";
$answer[]=["(A) 으면","(B) 어도","(C) 어서","(D) 으니까"];

$question[] ="16. 내일 시험이라서 공부__ 해요.";
$answer[]=["(A) 해야","(B) 해도","(C) 하면","(D) 하고"];

$question[] ="17. 다음 글을 읽고 괄호에 알맞은 말을 고르십시오.<br>\"저는 건강을 위해서 매일 아침 운동을 __ 했어요. 내일부터 시작할 거예요.\"";
$answer[]=["(A) 하기로","(B) 하러","(C) 하려고","(D) 하면"];

$question[] ="18. \"어제 너무 피곤했어요. 그래서 집에 __ 잠이 들었어요.\"";
$answer[]=["(A) 도착하자마자","(B) 도착하기 전에","(C) 도착하거나","(D) 도착하려고"];

$question[] ="19. \"가: 오늘 같이 등산 갈까요? / 나: 날씨가 __ 다음에 가요.\"";
$answer[]=["(A) 안 좋은데","(B) 안 좋아서","(C) 안 좋지만","(D) 안 좋고"];

$question[] ="20. \"한국 대학교에 입학하__ 한국어를 열심히 공부하고 있습니다.\"";
$answer[]=["(A) 기 위해서","(B) 기 때문에","(C) 는 동안","(D) 거나"];

$question[] ="21. 목이 말라요. 물__ 주스 좀 주세요.";
$answer[]=["(A) 이나","(B) 마다","(C) 조차","(D) 까지"];

$question[] ="22. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["① 배운","② 배울","③ 배우는","④ 배우기"];

$question[] ="23. 이 글의 내용과 같은 것을 고르십시오.";
$answer[]=["① 저는 저녁마다 수영장에 갑니다.","② 저는 어렸을 때 수영을 배웠습니다.","③ 저는 요즘 기분이 별로 좋지 않습니다.","④ 저는 이제 수영을 그만할 것입니다."];

$question[] ="24. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["① 가거나","② 가기로","③ 가면서","④ 가려고"];

$question[] ="25. 이 글의 내용과 같은 것을 고르십시오.";
$answer[]=["① 친구는 제주도에 가 봤습니다.","② 저는 제주도에서 사진을 찍었습니다.","③ 저는 다음 주에 제주도에 갈 것입니다.","④ 친구가 어제 비행기 표를 샀습니다."];

$explanation[] = "'때문에' သည် အကြောင်းပြချက်ကို ပြသည်။";
$explanation[] = "'있다' (ရှိသည်) ကို နာမ် ရှေ့တွင် အထူးပြုရန် '있는' ကို သုံးသည်။";
$explanation[] = "'한테서/에게서' သည် တစ်စုံတစ်ယောက်ထံမှ ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'-잖아요' သည် တစ်ဖက်သား သိပြီးသား အကြောင်းအရာကို အတည်ပြုပြောဆိုရာတွင် သုံးသည်။";
$explanation[] = "'-는/(으)ㄴ/(으)ㄹ 것 같다' သည် ခန့်မှန်းခြင်းကို ပြသည်။";
$explanation[] = "'-는 동안' သည် 'အချိန်အတွင်း'ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'-(으)면서' သည် လုပ်ဆောင်ချက် နှစ်ခုကို တစ်ပြိုင်နက်တည်း လုပ်ခြင်းကို ပြသည်။";
$explanation[] = "'-기 전에' သည် 'မလုပ်ခင်'ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'-(으)ㄴ 후에' သည် 'ပြီးနောက်'ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'(으)로' သည် နည်းလမ်း သို့မဟုတ် ယာဉ်ကို ပြသည်။";
$explanation[] = "'-거나' သည် ကြိယာနှစ်ခုအနက် တစ်ခုခုကို ရွေးချယ်ခြင်း (verb + or) ကို ပြသည်။";
$explanation[] = "'-(으)ㄴ 지' သည် တစ်စုံတစ်ခု လုပ်ပြီးနောက် ကြာမြင့်ချိန် (since) ကို ပြသည်။";
$explanation[] = "'-(으)ㄴ 적이 있다' သည် အတွေ့အကြုံရှိခြင်းကို ပြသည်။";
$explanation[] = "'-아/어/여도 되다' သည် ခွင့်ပြုချက်ကို ပြသည်။";
$explanation[] = "'-(으)면 안 되다' သည် မလုပ်ရ ဟု တားမြစ်ခြင်း ဖြစ်သည်။";
$explanation[] = "'-아/어/여야 하다' သည် 'လုပ်ရမည်'ဟု တာဝန်ကို ပြသည်။";
$explanation[] = "'-기로 하다' သည် ဆုံးဖြတ်ချက်ချခြင်းကို ပြသည်။";
$explanation[] = "'-자마자' သည် တစ်ခုခုပြီးပြီးချင်းကို ပြသည်။";
$explanation[] = "'-는데/(으)ㄴ데' သည် နောက်ဆက်တွဲ စကားပြောရန် အခြေအနေပေးခြင်း ဖြစ်သည်။";
$explanation[] = "'-기 위해서' သည် ရည်ရွယ်ချက်ကို ပြသည်။";
$explanation[] = "'(이)나' သည် 'သို့မဟုတ်' (or) ဟု အဓိပ္ပာယ်ရပြီး ရွေးချယ်စရာကို ပြသည်။";
$explanation[] = "'-(으)ㄴ 적이 있다' သည် အတိတ်က အတွေ့အကြုံ (experience) ကို ပြောရာတွင် သုံးသည်။ 'ဖူးသည်' (have done) ဟု အဓိပ္ပာယ်ရသည်။ '배우다' (သင်ယူသည်) ၏ အတိတ်ကာလ အထူးပြုပုံစံမှာ '배운' ဖြစ်သည်။";
$explanation[] = "စာပိုဒ်ထဲတွင် \"어렸을 때 수영을 배운 적이 있어서\" (ငယ်ငယ်က ရေကူးသင်ဖူးတဲ့အတွက်) ဟု ပါရှိသည်။";
$explanation[] = "'-기로 하다' သည် ဆုံးဖြတ်ချက်ချခြင်း သို့မဟုတ် ကတိပြုခြင်း (decided to/promised to) ကို ပြသည်။ သူငယ်ချင်းနှင့် သွားရန် 'ကတိပြုသည်/ဆုံးဖြတ်သည်' ဟု ပြောခြင်းက အမှန်ကန်ဆုံးဖြစ်သည်။";
$explanation[] = "ပထမဆုံး စာကြောင်းတွင် \"다음 주에 ... 가기로 했습니다\" (နောက်အပတ် ... သွားဖို့ လုပ်ထားပါတယ်) ဟု ပါရှိသည်။";


function questionFormatTwo($question, $answer, $no, $explanation = "") {
    echo $question;
    echo "<div style='display:flex; flex-wrap: wrap; margin-top:10px;'>";
    foreach ($answer as $i => $text) {
        $ansNo = $i + 1;
        echo "
            <div class='form-check' style='flex-basis: 50%; min-width: 150px;'>
                <input class='form-check-input' type='radio' name='$no' id='ans$no$ansNo'>
                <label class='form-check-label' for='ans$no$ansNo'>
                <span id='right$no$ansNo'> $text </span>
                <i id='correct$no$ansNo' class='material-icons' style='font-size:18px;color:rgb(0, 255, 0);display:none;'>check_circle</i>
                <i id='error$no$ansNo' class='material-icons' style='font-size:18px;color:red;display:none;'>cancel</i>                   
                </label>
            </div>
        ";
    }
    echo "</div>";
    if ($explanation != "") {
        echo "<div class='explanation' id='explanation$no'><strong>ရှင်းလင်းချက်:</strong> $explanation</div>";
    }
    echo "<br><br>";
}

?>

 
<body>
<div class="container">

    <div style="position:fixed;right: 10px;;"><span class="fixedTime" id="timer" >Time Left</span></div><br>
    <h3 align="center">Calamus Education</h2>
    <h4 align="center">Easy Korean - Level Two Course Exam</h3>

    <span >Allowed Time - 30 min</span>
    <span style="float:right">Marks - 25</span>
    <br>
    <br>
    
    <h5><u>문법 ( GRAMMAR )</u></h5><br>
    <h6>A. 다음을 보고 빈칸에 들어갈 알맞은 것을 고르십시오.</h6><br>

    <?php  
        //for question 1 to 21
        for($i=0;$i<21;$i++){
            questionFormatTwo($question[$i],$answer[$i],$i+1, $explanation[$i]);
        }
    ?>

    <h5><u>읽기 ( READING )</u></h5><br>
    <h6>B. 다음 글을 읽고 물음에 답하십시오.</h6><br>
    <p>
    저는 요즘 건강해지기 위해서 수영을 배웁니다. 어렸을 때 수영을 ( ㉠ ) 적이 있어서 배우기가 쉽습니다. 매일 아침 수영장에 가는 것은 조금 힘들지만 운동을 하고 나면 기분이 좋습니다. 앞으로도 계속 수영을 할 것입니다.
    </p>

    <?php  
        //for question 22 to 23
        questionFormatTwo($question[21],$answer[21],22, $explanation[21]);
        questionFormatTwo($question[22],$answer[22],23, $explanation[22]);
    ?>

    <h6>C. 다음 글을 읽고 물음에 답하십시오.</h6><br>
    <p>
    다음 주에 친구와 제주도에 ( ㉠ ) 했습니다. 그래서 어제 비행기 표를 샀습니다. 저는 제주도에 가 본 적이 있는데 제 친구는 처음입니다. 날씨가 좋으면 예쁜 사진을 많이 찍고 싶습니다.
    </p>

    <?php  
        //for question 24 to 25
        questionFormatTwo($question[23],$answer[23],24, $explanation[23]);
        questionFormatTwo($question[24],$answer[24],25, $explanation[24]);
    ?>

    <div style="display:flex;">
        <button id="bt-checkAns" class="btn btn-primary" onClick="checkAnswer()" >Check Answer</button>
        <button id="bt-showAns" class="btn btn-danger" onclick="showAnswer();" style="display:none;">Show Answer</button>
    </div>
   

    <p id="final-result">
      
    </p>

</div>


    <script>
        var ansChecker=["11","21","31","41","51","61","71","81","91","101","111","121","131","141","151","161","171","181","191","201","211","221","232","242","253"];
       
        function showAnswer(){
            for(var i=0,j=ansChecker.length;i<j;i++){
              var ansSpan =document.getElementById("right"+ansChecker[i]);
              ansSpan.setAttribute('style','background-color:blue;padding:5px;border-radius:3px;color:white; font-weight:bold;');
              
              // Show explanation for each question
              var questionNo = i + 1;
              var explanationDiv = document.getElementById("explanation" + questionNo);
              if(explanationDiv){
                  explanationDiv.classList.add("show");
              }
            }    
        }

        function checkAnswer(){

            var result=0;

            document.getElementById("bt-showAns").setAttribute('Style','');

            for(var i=0,j=ansChecker.length;i<j;i++){
              var ansSpan =document.getElementById("right"+ansChecker[i]);
              ansSpan.setAttribute('style','');
            }  

            for(var i=0;i<25;i++){
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

        function showFinalResult(result){
            var finalResult=document.getElementById('final-result');
            if(result<10){
                finalResult.setAttribute('style','background-color:red');
                if(result <1){
                    finalResult.innerHTML="Fail <br> "+result+"/25 mark";
                }else{
                    finalResult.innerHTML="Fail <br> "+result+"/25 marks";
                }
              
           }else if(result>=10 && result<=15){
                finalResult.setAttribute('style','background-color:rgb(255,165,0);');
                finalResult.innerHTML="Good <br> "+result+"/25 marks";
           }else if(result>15 && result<=20){
                finalResult.setAttribute('style','background-color:yellow; color:black;');
                finalResult.innerHTML="Very Good <br> "+result+"/25 marks";
           }else{
                finalResult.setAttribute('style','background-color:green');
                finalResult.innerHTML="Excellent <br> "+result+"/25 marks";
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
            ajax.send("userId=<?php echo isset($userId) ? $userId : ''; ?>&major=korea&test=leveltwo_exam&result="+result);

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
            for(var i=0;i<25;i++){
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
