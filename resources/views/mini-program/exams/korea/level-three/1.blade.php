<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Level Three Course Exam</title>


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

$question[] ="1. 가: 지금 출발할까요? / 나: 네, 저도 막 ______.";
$answer[]=["(A) 나가려던 참이었어요","(B) 나가기만 했어요","(C) 나가는지 알았어요","(D) 나가야 했어요"];

$question[] ="2. 이 식당은 음식 맛이 ______ 서비스도 아주 훌륭해요.";
$answer[]=["(A) 좋을 뿐만 아니라","(B) 좋은 대신에","(C) 좋을 텐데","(D) 좋기로 해서"];

$question[] ="3. 가: 민수 씨가 왜 늦을까요? / 나: 길이 많이 ______.";
$answer[]=["(A) 막히나 봐요","(B) 막히기로 해요","(C) 막히라고 해요","(D) 막히자마자요"];

$question[] ="4. 친구에게 주말에 같이 영화를 ______ 했는데 친구가 시간이 없다고 거절했어요.";
$answer[]=["(A) 보자고","(B) 본다고","(C) 보라고","(D) 보냐고"];

$question[] ="5. 어제 늦게 ______ 아침에 일어나는 것이 힘들었어요.";
$answer[]=["(A) 자서 그런지","(B) 자고 나서","(C) 자는 동안","(D) 자도 되지만"];

$question[] ="6. 저는 한국 문화에 ______ 관심이 많습니다.";
$answer[]=["(A) 대해서","(B) 위해서","(C) 의해서","(D) 비해서"];

$question[] ="7. 가: 영수 씨가 회사를 그만두었나요? / 나: 네, 저도 다른 사람한테서 ______.";
$answer[]=["(A) 들었어요","(B) 물었어요","(C) 시켰어요","(D) 주었어요"];

$question[] ="8. 가: 이번 방학에 뭐 할 거예요? / 나: 친구들하고 부산으로 여행을 ______.";
$answer[]=["(A) 가기로 했어요","(B) 가게 되었어요","(C) 간 적이 있어요","(D) 가면 안 돼요"];

$question[] ="9. 너무 피곤해서 집에 ______ 잤어요.";
$answer[]=["(A) 가자마자","(B) 가다가","(C) 가려다가","(D) 가더니"];

$question[] ="10. 가: 선생님, 제가 이 책을 가져가도 될까요? / 나: 네, ______.";
$answer[]=["(A) 가져가도 돼요","(B) 가져가야 해요","(C) 가져가면 안 돼요","(D) 가져갈래요"];

$question[] ="11. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 위해서","(B) 대해서","(C) 통해서","(D) 의해서"];

$question[] ="12. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 하면서","(B) 하러","(C) 하거나","(D) 하기로"];

$question[] ="13. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 떠들거나","(B) 떠들어서","(C) 떠드는데","(D) 떠들지만"];

$question[] ="14. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 배운 적이 있지만","(B) 배울 수 있어서","(C) 배우게 되어서","(D) 배워야 해서"];

$question[] ="15. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 쉬려던","(B) 쉬는","(C) 쉰","(D) 쉴"];

$question[] ="16. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 놓으십시오","(B) 놓지 마십시오","(C) 놓을까요?","(D) 놓고 싶습니다"];

$question[] ="17. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 먹게 돼서","(B) 먹기 위해서","(C) 먹은 후에","(D) 먹더라도"];

$question[] ="18. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 라고 하셨어요","(B) 다고 하셨어요","(C) 자고 하셨어요","(D) 냐고 하셨어요"];

$question[] ="19. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 타느니","(B) 타니까","(C) 타면서","(D) 타기 전에"];

$question[] ="20. ㉠에 들어갈 알맞은 것을 고르십시오.";
$answer[]=["(A) 열어 놓고","(B) 열어 가지고","(C) 열게 되고","(D) 열기로 하고"];

$question[] ="21. 다음 글을 읽고 내용과 같은 것을 고르십시오.";
$answer[]=["(A) 저는 요즘 텀블러를 사용하는 것이 익숙해졌습니다.","(B) 카페에서 텀블러를 사용하면 돈을 더 내야 합니다.","(C) 저는 텀블러를 가지고 다니는 것이 귀찮습니다.","(D) 저는 환경 보호에 관심이 별로 없습니다."];

$question[] ="22. 다음 글을 읽고 내용과 같은 것을 고르십시오.";
$answer[]=["(A) 직원들은 한 달에 한 번 일찍 집에 갈 수 있습니다.","(B) 이 회사는 예전부터 가족의 날이 있었습니다.","(C) 가족의 날에는 회사에 오지 않아도 됩니다.","(D) 매주 수요일은 가족의 날입니다."];

$question[] ="23. 다음 글을 읽고 내용과 같은 것을 고르십시오.";
$answer[]=["(A) 혼자 여행하면 다양한 음식을 먹기 어렵습니다.","(B) 요즘 혼자 여행하는 사람이 줄어들고 있습니다.","(C) 혼자 여행하면 가고 싶은 곳에 못 갑니다.","(D) 혼자 여행하는 것은 장점만 있습니다."];

$question[] ="24. 다음 글을 읽고 내용과 같은 것을 고르십시오.";
$answer[]=["(A) 저는 봉사활동을 하면서 기쁨을 느낍니다.","(B) 저는 매일 도서관에 가서 일합니다.","(C) 도서관 일은 항상 쉽고 편합니다.","(D) 저는 책을 읽어주는 봉사활동을 합니다."];

$question[] ="25. 다음 글을 읽고 내용과 같은 것을 고르십시오.";
$answer[]=["(A) 추석에는 가족들이 함께 음식을 만듭니다.","(B) 추석에는 고향에 가는 길이 한가합니다.","(C) 추석에는 보름달을 보면 안 됩니다.","(D) 추석에는 가족들을 만나지 않습니다."];

$explanation[] = "'-(으)려던 참이다' သည် တစ်စုံတစ်ခုကို လုပ်ရန် ကြံရွယ်နေဆဲ သို့မဟုတ် လုပ်ခါနီးအချိန်ကို ပြသည်။";
$explanation[] = "'-(으)ㄹ 뿐만 아니라' သည် ရှေ့အကြောင်းအရာတင်မကဘဲ နောက်အကြောင်းအရာပါ ရှိသည်ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'-나 보다' (သို့မဟုတ် '-(으)ㄴ/는 것 같다') သည် အခြေအနေကို ကြည့်ပြီး ခန့်မှန်းပြောဆိုခြင်းဖြစ်သည်။";
$explanation[] = "'-자고 하다' သည် 'လုပ်ကြစို့' ဟု တိုက်တွန်းသောစကားကို ပြန်ပြောပြခြင်း ဖြစ်သည်။";
$explanation[] = "'-아/어/여서 그런지' သည် 'အကြောင်းကြောင့်များလားမသိ' ဟု မသေချာသော အကြောင်းပြချက်ကို ပြောရာတွင် သုံးသည်။";
$explanation[] = "'-에 대해서' သည် 'နှင့် ပတ်သက်၍' ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'한테서 듣다' သည် တစ်စုံတစ်ယောက်ထံမှ ကြားသိရသည်ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "'-기로 하다' သည် အစီအစဉ်ဆွဲခြင်း သို့မဟုတ် ဆုံးဖြတ်ခြင်းကို ပြသည်။";
$explanation[] = "'-자마자' သည် တစ်ခုခု ဖြစ်ပြီးပြီးချင်းကို ပြသည်။";
$explanation[] = "'-아/어/여도 되다' သည် ခွင့်ပြုချက်ပေးခြင်းဖြစ်သည်။";
$explanation[] = "'-기 위해서' သည် ရည်ရွယ်ချက် ကို ပြသည်။";
$explanation[] = "'-(으)면서' သည် တစ်ပြိုင်နက်တည်း လုပ်ဆောင်ခြင်းကို ပြသည်။";
$explanation[] = "'-거나' သည် ရွေးချယ်မှု သို့မဟုတ် စာရင်းပြုစုခြင်း (or) ကို ပြသည်။";
$explanation[] = "'-(으)ㄴ 적이 있다' နှင့် '-지만' ပေါင်းစပ်ထားသည်။";
$explanation[] = "'-(으)려던 참이다'သည် စိတ်ကူးရှိခြင်း သို့မဟုတ် လုပ်ရန်ပြင်ဆင်နေခြင်းကို ပြသည်။";
$explanation[] = "'-아/어/여 놓다' သည် တစ်ခုခုလုပ်ထားခြင်းကို ပြသည်။ 'ပိတ်ထားပါ' ဟု ယဉ်ကျေးစွာ ခိုင်းစေခြင်း ဖြစ်သည်။";
$explanation[] = "'-게 되다' သည် ရလဒ်ကို ပြသည်။";
$explanation[] = "'-(이)라고 하다' သည် နာမ်ကို ပြန်ပြောပြခြင်းဖြစ်သည်။";
$explanation[] = "'-느니'သည် ရှေ့အရာကို လုပ်မယ့်အစား နောက်အရာကို လုပ်တာ ပိုကောင်းတယ် ဟု နှိုင်းယှဉ်ရာတွင် သုံးသည်။";
$explanation[] = "'-아/어/여 놓다'သည် ဖွင့်ထားလျက်သား အိပ်သည် ဟု အဓိပ္ပာယ်ရသည်။";
$explanation[] = "စာပိုဒ်ထဲတွင် '지금은 습관이 되었습니다' (အခုတော့ အကျင့်ဖြစ်သွားပါပြီ) ဟု ပါရှိသောကြောင့် အဖြေမှန်ဖြစ်သည်။";
$explanation[] = "လစဉ် နောက်ဆုံးပတ် ဗုဒ္ဓဟူးနေ့ (매월 마지막 주 수요일) တွင် စောပြန်နိုင်သည်ဟု ပါရှိသည်။";
$explanation[] = "အစားအစာ အစုံအလင် မစားနိုင်လို့ စိတ်မကောင်းဘူး (여러 가지 먹을 수 없어서 아쉽습니다) ဟု ပါရှိသည်။";
$explanation[] = "ကျေနပ်မှု/ပီတိ ခံစားရသည် (보람을 느낍니다) ဆိုသည်မှာ ဝမ်းမြောက်ခြင်း တမျိုးဖြစ်သည်။";
$explanation[] = "ကောက်ညှင်းဆန်မုန့်ကို အတူလုပ်ကြသည် (가족이 모여서 송편을 빚습니다) ဟု ပါရှိသည်။";


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
    <h4 align="center">Easy Korean - Level Three Course Exam</h3>

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

    <h6>B. 다음 글을 읽고 ㉠에 들어갈 알맞은 것을 고르십시오.</h6><br>

    <p>
    요즘 건강을 ( ㉠ ) 운동을 시작하는 사람들이 많습니다. 하지만 갑자기 무리한 운동을 하면 다칠 수 있습니다. 그래서 자신의 체력에 맞는 운동을 하는 것이 중요합니다.
    </p>

    <?php  
        //for question 11
        questionFormatTwo($question[10],$answer[10],11, $explanation[10]);
    ?>

    <p>
    저는 아침에 일어나서 물을 한 잔 마십니다. 그리고 아침 식사를 ( ㉠ ) 뉴스를 봅니다. 이렇게 하면 하루를 상쾌하게 시작할 수 있습니다.
    </p>

    <?php  
        //for question 12
        questionFormatTwo($question[11],$answer[11],12, $explanation[11]);
    ?>

    <p>
    도서관에서는 다른 사람들에게 방해가 되지 않도록 조용히 해야 합니다. 큰 소리로 ( ㉠ ) 전화를 받으면 안 됩니다.
    </p>

    <?php  
        //for question 13
        questionFormatTwo($question[12],$answer[12],13, $explanation[12]);
    ?>

    <p>
    저는 어렸을 때 피아노를 ( ㉠ ) 지금은 다 잊어버렸습니다. 다시 배울 기회가 있으면 좋겠습니다.
    </p>

    <?php  
        //for question 14
        questionFormatTwo($question[13],$answer[13],14, $explanation[13]);
    ?>

    <p>
    가: 이번 주말에 약속 있어요? / 나: 아니요, 특별한 약속은 없어요. 그냥 집에서 ( ㉠ ) 참이었어요.
    </p>

    <?php  
        //for question 15
        questionFormatTwo($question[14],$answer[14],15, $explanation[14]);
    ?>

    <p>
    회의 시간에는 휴대전화를 진동으로 하거나 꺼 ( ㉠ ). 이것은 기본적인 예절입니다.
    </p>

    <?php  
        //for question 16
        questionFormatTwo($question[15],$answer[15],16, $explanation[15]);
    ?>

    <p>
    매일 아침 식사를 거르지 않고 먹는 것이 중요합니다. 아침을 안 먹으면 점심을 많이 ( ㉠ ) 건강에 좋지 않습니다.
    </p>

    <?php  
        //for question 17
        questionFormatTwo($question[16],$answer[16],17, $explanation[16]);
    ?>

    <p>
    가: 민수 씨, 아까 김 선생님이 뭐라고 하셨어요? / 나: 내일 회의 시간은 2시가 아니라 3시( ㉠ ).
    </p>

    <?php  
        //for question 18
        questionFormatTwo($question[17],$answer[17],18, $explanation[17]);
    ?>

    <p>
    버스를 ( ㉠ ) 지하철을 타는 것이 더 빠릅니다. 출퇴근 시간에는 길이 많이 막히기 때문입니다.
    </p>

    <?php  
        //for question 19
        questionFormatTwo($question[18],$answer[18],19, $explanation[18]);
    ?>

    <p>
    날씨가 더워서 창문을 ( ㉠ ) 잤더니 감기에 걸렸습니다. 약을 먹었지만 아직도 머리가 아픕니다.
    </p>

    <?php  
        //for question 20
        questionFormatTwo($question[19],$answer[19],20, $explanation[19]);
    ?>

    <h5><u>읽기 ( READING )</u></h5><br>
    <h6>C. 다음 글을 읽고 내용과 같은 것을 고르십시오.</h6><br>
    <p>
    "저는 환경을 보호하기 위해서 일회용 컵 대신 텀블러를 사용합니다. 카페에 갈 때 텀블러를 가져가면 할인도 받을 수 있습니다. 처음에는 텀블러를 챙기는 것이 귀찮았지만 지금은 습관이 되었습니다."
    </p>

    <?php  
        //for question 21
        questionFormatTwo($question[20],$answer[20],21, $explanation[20]);
    ?>

    <p>
    "우리 회사는 다음 달부터 '가족의 날'을 만들기로 했습니다. 매월 마지막 주 수요일에는 직원들이 4시에 퇴근할 수 있습니다. 일찍 집에 가서 가족들과 함께 즐거운 시간을 보내라는 뜻입니다."
    </p>

    <?php  
        //for question 22
        questionFormatTwo($question[21],$answer[21],22, $explanation[21]);
    ?>

    <p>
    "최근 혼자 여행하는 사람들이 늘고 있습니다. 혼자 여행하면 가고 싶은 곳에 마음대로 갈 수 있어서 좋습니다. 하지만 맛있는 음식을 여러 가지 먹을 수 없어서 아쉽습니다."
    </p>

    <?php  
        //for question 23
        questionFormatTwo($question[22],$answer[22],23, $explanation[22]);
    ?>

    <p>
    "저는 주말마다 도서관 봉사활동을 합니다. 책을 정리하고 사람들이 찾는 책을 찾아줍니다. 힘들 때도 있지만 사람들이 고맙다고 말할 때 보람을 느낍니다."
    </p>

    <?php  
        //for question 24
        questionFormatTwo($question[23],$answer[23],24, $explanation[23]);
    ?>

    <p>
    "한국의 대표적인 명절인 추석에는 온 가족이 모여서 송편을 빚습니다. 그리고 보름달을 보면서 소원을 빕니다. 고향에 가는 길이 막혀서 힘들지만 가족들을 만날 생각에 즐겁습니다."
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
        var ansChecker=["11","21","31","41","51","61","71","81","91","101","111","121","131","141","151","161","171","181","191","201","211","221","231","241","251"];
       
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
            ajax.send("userId=<?php echo isset($userId) ? $userId : ''; ?>&major=korea&test=levelthree_exam&result="+result);

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
