<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Level Four Course Exam</title>


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

$question[] ="1. 갑자기 비가 ______ 옷이 다 젖었어요.";
$answer[]=["(A) 오는 바람에","(B) 오기 위해서","(C) 오는 셈 치고","(D) 오자마자"];

$question[] ="2. 매일 야근을 ______ 건강이 나빠졌어요.";
$answer[]=["(A) 하다가 보니까","(B) 하든지 말든지","(C) 하거나 말거나","(D) 하는데도"];

$question[] ="3. 시간이 지나면 슬픈 기억도 다 ______.";
$answer[]=["(A) 잊혀지게 마련이다","(B) 잊혀질 리가 없다","(C) 잊혀질까 봐","(D) 잊혀진 셈이다"];

$question[] ="4. 그 사람은 약속을 잘 안 지키는 걸 보니 믿을 만한 사람이 ______.";
$answer[]=["(A) 못 되는 게 틀림없다","(B) 못 되는 척했다","(C) 못 될지도 모른다","(D) 못 되기로 했다"];

$question[] ="5. 이번 프로젝트의 성공은 팀원들의 노력______.";
$answer[]=["(A) 에 달려 있다","(B) 에 비하면","(C) 에 대해서","(D) 을 위해서"];

$question[] ="6. 그 식당은 맛도 좋을 ______ 서비스도 친절해요.";
$answer[]=["(A) 뿐만 아니라","(B) 뿐이라서","(C) 수밖에 없어서","(D) 정도로"];

$question[] ="7. 백화점에 ______ 친구 선물을 샀어요.";
$answer[]=["(A) 간 김에","(B) 가는 길에","(C) 가느라고","(D) 가자마자"];

$question[] ="8. 이 물건은 너무 낡아서 ______.";
$answer[]=["(A) 버리나 마나예요","(B) 버린 셈이에요","(C) 버린 거나 다름없어요","(D) 버릴 뿐이에요"];

$question[] ="9. 요즘 물가를 생각하면 월급이 ______.";
$answer[]=["(A) 적은 편이에요","(B) 적기만 해요","(C) 적을 리가 없어요","(D) 적어야 해요"];

$question[] ="10. 친구가 늦게 오는 바람에 영화 앞부분을 ______.";
$answer[]=["(A) 못 볼 뻔했어요","(B) 못 보게 마련이에요","(C) 못 본 셈이에요","(D) 못 볼 따름이에요"];

$question[] ="11. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.<br>이 일은 시작하나 마나 실패할 것이 뻔하다.";
$answer[]=["(A) 시작해도","(B) 시작하려면","(C) 시작하니까","(D) 시작한 후에"];

$question[] ="12. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.<br>그 사람은 나이에 비하면 어려 보인다.";
$answer[]=["(A) 나이에 비해서","(B) 나이 때문에","(C) 나이를 통해서","(D) 나이만큼"];

$question[] ="13. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.<br>죄송하지만 그 부탁은 들어 드리기가 곤란할 따름입니다.";
$answer[]=["(A) 곤란할 뿐입니다","(B) 곤란할 지경입니다","(C) 곤란한 셈입니다","(D) 곤란하기 마련입니다"];

$question[] ="14. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.<br>겨울이 가면 봄이 오는 법이다.";
$answer[]=["(A) 오게 마련이다","(B) 오는 셈이다","(C) 올 뿐이다","(D) 오기 나름이다"];

$question[] ="15. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.<br>고향에 도착하는 대로 전화해 주세요.";
$answer[]=["(A) 도착하자마자","(B) 도착하는 김에","(C) 도착하느라고","(D) 도착하더니"];

$question[] ="16. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.<br>그 영화는 재미있을 뿐만 아니라 감동적이다.";
$answer[]=["(A) 재미있는 데다가","(B) 재미있는 반면에","(C) 재미있는 탓에","(D) 재미있는 한편"];

$question[] ="17. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.<br>이 음식은 너무 매워서 못 먹을 지경이다.";
$answer[]=["(A) 먹을 수 없을 것 같다","(B) 먹을 수밖에 없다","(C) 먹으면 안 된다","(D) 먹을지도 모른다"];

$question[] ="18. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.<br>그 사람은 전문가나 다름없다.";
$answer[]=["(A) 전문가와 마찬가지다","(B) 전문가일 뿐이다","(C) 전문가인 셈이다","(D) 전문가이기 마련이다"];

$question[] ="19. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["(A) 나빠지기 십상입니다","(B) 나빠질 리가 없습니다","(C) 나빠질 뿐입니다","(D) 나빠지기 마련입니다"];

$question[] ="20. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["(A) 지키는 데","(B) 지키려고","(C) 지키다 보니","(D) 지키는 탓에"];

$question[] ="21. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["(A) 비싸긴 한데","(B) 비싸다 보니","(C) 비싼 셈 치고","(D) 비쌀 정도로"];

$question[] ="22. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["(A) 한다고 합니다","(B) 하라고 합니다","(C) 하자고 합니다","(D) 하냐고 합니다"];

$question[] ="23. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["(A) 일하다가 보니까","(B) 일하는 탓에","(C) 일하는 김에","(D) 일할 뿐만 아니라"];

$question[] ="24. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["(A) 생기게 마련입니다","(B) 생길 리가 없습니다","(C) 생길 수밖에 없습니다","(D) 생긴 셈입니다"];

$question[] ="25. ㉠에 들어갈 알맞은 말을 고르십시오.";
$answer[]=["(A) 하기로 했습니다","(B) 하게 되었습니다","(C) 한 적이 있습니다","(D) 할까 합니다"];

$explanation[] = "'-는 바람에' သည် မမျှော်လင့်ထားသော အကြောင်းရင်းတစ်ခုကြောင့် မကောင်းသော ရလဒ်တစ်ခု ဖြစ်ပေါ်လာရသည့်အခါသုံးသည်။";
$explanation[] = "'-다가 보니까' သည် တစ်စုံတစ်ခုကို ဆက်တိုက်လုပ်ဆောင်ရင်း နောက်ဆက်တွဲ ရလဒ် သို့မဟုတ် အခြေအနေသစ်တစ်ခုကို သိရှိလာရသည့်အခါ သုံးသည်။";
$explanation[] = "'-게 마련이다' သည် သဘာဝတရားအရ သို့မဟုတ် မလွဲမသွေ ဖြစ်လာမည့် အရာကို ဆိုလိုသည်။";
$explanation[] = "'-는 게 틀림없다' သည် သေချာပေါက် မှန်ကန်သည်ဟု ခန့်မှန်းပြောဆိုရာတွင် သုံးသည်။";
$explanation[] = "'-에 달려 있다' သည် တစ်စုံတစ်ခုအပေါ် မူတည်နေသည်ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'-(으)ㄹ 뿐만 아니라' သည် ရှေ့အရာတင်မကဘဲ နောက်အရာပါ ရှိကြောင်းပြသည်။";
$explanation[] = "'-(으)ㄴ 김에' သည် တစ်စုံတစ်ခု လုပ်ရင်းနှင့် အခွင့်ကြုံခိုက် နောက်တစ်ခုပါ လုပ်လိုက်ကြောင်း ပြသည်။";
$explanation[] = "'-(이)나 다름없다' သည် တစ်စုံတစ်ခုနှင့် ခြားနားမှုမရှိ၊ အတူတူပဲဖြစ်သည်ဟု ဆိုလိုသည်။";
$explanation[] = "'-는 편이다' သည် အုပ်စုတစ်ခုတွင် ပါဝင်သည် ဟု ပြောခြင်းဖြစ်သည်။";
$explanation[] = "'-(으)ㄹ 뻔하다' သည် တစ်စုံတစ်ခု ဖြစ်လုနီးပါး ဖြစ်ခဲ့ခြင်းကို ပြသည်။";
$explanation[] = "'-나 마나' (လုပ်သည်ဖြစ်စေ မလုပ်သည်ဖြစ်စေ) သည် '-아/어/여도' နှင့် အဓိပ္ပာယ် ဆင်တူသည်။";
$explanation[] = "'-에 비하면' နှင့် '-에 비해서' သည် 'နှင့် နှိုင်းယှဉ်လျှင်'ဟု အဓိပ္ပာယ်တူသည်။";
$explanation[] = "'-(으)ㄹ 따름이다' နှင့် '-(으)ㄹ 뿐이다' သည် '...ရုံမျှသာ ဖြစ်သည်' ဟု အဓိပ္ပာယ်တူသည်။";
$explanation[] = "'-는 법이다' နှင့် '-게 마련이다' သည် သဘာဝနိယာမ သို့မဟုတ် မလွဲမသွေ ဖြစ်လာမည့်အရာကို ပြရာတွင် အဓိပ္ပာယ်တူသည်။";
$explanation[] = "'-는 대로' နှင့် '-자마자' သည် ချက်ချင်းဆိုသလို ဟု အဓိပ္ပာယ်တူသည်။";
$explanation[] = "'-(으)ㄹ 뿐만 아니라' (တင်မကဘဲ) နှင့် '-(으)ㄴ/는 데다가' (အပြင်/ထပ်ပေါင်းပြီး) သည် အဓိပ္ပာယ် ဆင်တူသည်။";
$explanation[] = "'-을 지경이다' (လုနီးပါးဖြစ်သည်) သည် သည်းမခံနိုင်မှုကို ပြသည်။ 'မစားနိုင်တော့သလိုပဲ/မစားနိုင်လောက်အောင်ပဲ' ဆိုတဲ့ အဓိပ္ပာယ်နှင့် ဆင်တူသည်။";
$explanation[] = "'-나 다름없다' နှင့် '-와/과 마찬가지다' သည် အတူတူပင်ဖြစ်သည်/ခြားနားမှုမရှိဟု အဓိပ္ပာယ်တူသည်။";
$explanation[] = "'-기 십상이다' သည် ဖြစ်လွယ်သည် (မကောင်းသော ရလဒ်အတွက် အသုံးများ) ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'-는 데(에) 도움이 되다' သည် တစ်စုံတစ်ခု လုပ်ဆောင်ရာတွင်အထောက်အကူဖြစ်သည် ဟု သုံးလေ့ရှိသည်။";
$explanation[] = "'-긴 한데' သည် အမှန်တရားတစ်ခုကို ဝန်ခံသော်လည်း နောက်ဆက်တွဲ ဆန့်ကျင်ဘက် အကြောင်းအရာကို ပြောလိုသည့်အခါ သုံးသည်။";
$explanation[] = "'-는다고/ㄴ다고 하다' သည် တစ်ဆင့်စကားဖြစ်သည်။ ရာထူးတိုးတယ်လို့ ပြောကြတယ်/သတင်းကြားတယ် ဆိုတဲ့ သဘောပါ။";
$explanation[] = "'-다가 보니까' သည် တစ်စုံတစ်ခုကို အာရုံစိုက်လုပ်ဆောင်နေရင်း သတိမထားမိလိုက်ဘဲ အခြားရလဒ်တစ်ခု ဖြစ်ပေါ်လာပုံကို ပြသည်။";
$explanation[] = "'-게 마련이다' သည် သဘာဝကျကျ ဖြစ်လာမည့် ရလဒ်ဖြစ်သည်။";
$explanation[] = "'-기로 하다' သည် ဆုံးဖြတ်ချက်ချခြင်းကို ပြသည်။";


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
    <h4 align="center">Easy Korean - Level Four Course Exam</h3>

    <span >Allowed Time - 30 min</span>
    <span style="float:right">Marks - 25</span>
    <br>
    <br>
    
    <h5><u>문법 ( GRAMMAR )</u></h5><br>
    <h6>A. 다음을 보고 빈칸에 들어갈 알맞은 것을 고르십시오.</h6><br>

    <?php  
        //for question 1 to 10
        for($i=0;$i<10;$i++){
            questionFormatTwo($question[$i],$answer[$i],$i+1, $explanation[$i]);
        }
    ?>

    <h6>B. 밑줄 친 부분과 의미가 가장 비슷한 것을 고르십시오.</h6><br>

    <?php  
        //for question 11 to 18
        for($i=10;$i<18;$i++){
            questionFormatTwo($question[$i],$answer[$i],$i+1, $explanation[$i]);
        }
    ?>

    <h5><u>읽기 ( READING )</u></h5><br>
    <h6>C. 다음 글의 ( ㉠ )에 들어갈 알맞은 말을 고르십시오.</h6><br>

    <p>
    "성공적인 대화를 위해서는 상대방의 말을 잘 들어야 합니다. 자신의 의견만 주장하다가는 상대방과의 관계가 ( ㉠ ). 그러므로 말하기보다는 듣기를 더 중요하게 생각해야 합니다."
    </p>

    <?php  
        //for question 19
        questionFormatTwo($question[18],$answer[18],19, $explanation[18]);
    ?>

    <p>
    "환경 보호는 우리의 작은 실천에서 시작됩니다. 일회용품 사용을 줄이고 대중교통을 이용하는 것만으로도 환경을 ( ㉠ ) 큰 도움이 됩니다."
    </p>

    <?php  
        //for question 20
        questionFormatTwo($question[19],$answer[19],20, $explanation[19]);
    ?>

    <p>
    "요즘 건강에 대한 관심이 높아지면서 유기농 식품을 찾는 사람들이 늘고 있다. 가격이 조금 ( ㉠ ) 내 가족이 먹을 음식이라서 좋은 재료를 선택하는 것이다."
    </p>

    <?php  
        //for question 21
        questionFormatTwo($question[20],$answer[20],21, $explanation[20]);
    ?>

    <p>
    "김 대리는 이번 달 실적이 아주 좋습니다. 열심히 일하더니 결국 승진을 ( ㉠ ). 동료들이 모두 축하해 주었습니다."
    </p>

    <?php  
        //for question 22
        questionFormatTwo($question[21],$answer[21],22, $explanation[21]);
    ?>

    <p>
    "어제 친구와 약속이 있었는데 깜빡 잊어버렸습니다. 너무 바빠서 정신없이 ( ㉠ ) 약속 시간을 놓치고 말았습니다."
    </p>

    <?php  
        //for question 23
        questionFormatTwo($question[22],$answer[22],23, $explanation[22]);
    ?>

    <p>
    "기술이 발전하면서 우리 생활은 편리해졌지만, 그만큼 개인 정보 유출과 같은 문제도 생겨났습니다. 편리함을 얻는 대신에 잃는 것도 ( ㉠ )."
    </p>

    <?php  
        //for question 24
        questionFormatTwo($question[23],$answer[23],24, $explanation[23]);
    ?>

    <p>
    "저는 올해부터 매일 아침 조깅을 ( ㉠ ). 건강을 위해서 꾸준히 운동을 하는 것이 중요하다고 생각하기 때문입니다."
    </p>

    <?php  
        //for question 25
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
        var ansChecker=["11","21","31","41","51","61","71","83","91","101","111","121","131","141","151","161","171","181","191","201","211","221","231","241","251"];
       
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
            ajax.send("userId=<?php echo isset($userId) ? $userId : ''; ?>&major=korea&test=levelfour_exam&result="+result);

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
