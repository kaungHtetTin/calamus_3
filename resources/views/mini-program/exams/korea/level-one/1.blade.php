<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Level One Course Exam</title>


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

$question[] ="1. 약속 시간은 2시 25분입니다.  ";
$answer[]=["TRUE","FALSE",];

$question[] ="2. 이 사람은 약속 장소에 일찍 가고 싶었습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="3. 이 사람은 지하철을 타고 갔습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="4. 이 사람의 친구는 항상 약속 시간에 늦습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="5. 이 사람은 친구를 1시간이나 기다렸습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="6. 예나는 어제 하루종일 집에 있었습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="7. 예나는 식당에서 냉면을 시켰습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="8. 예나와 친구는 점심을 먹고 옷을 사러 시장에 갔습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="9. 예나는 친구와 같이 차를 타고 백화점에 갔습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="10. 예나는 커피숍에서 초콜릿 케이크를 먹었습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="11. 이 사람의 고향은 1년 내내 덥지 않습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="12. 한국에는 봄, 여름, 가을, 겨울이 있습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="13. 한국에는 가을에 날씨가 선선합니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="14. 사람들은 겨울에 꽃을 구경하러 산이나 공원에 갑니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="15. 이 사람은 다음주에 친구들과 같이 소풍을 갈 겁니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="16. 이 사람은 어제 꽃집에 갔습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="17. 꽃집에 카네이션 꽃이 많이 있었습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="18. 이 사람은 빨간색 카네이션 꽃을 한 다발 샀습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="19. 꽃은 한  다발에 육천원이었습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="20. 이 사람은 내일 여자친구에게 이꽃을 주려고 합니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="21. 이 사람의 가족은 모두 몇명입니까?";
$answer[]=["a.	3명","b. 4명","c. 5명","d. 6명"];

$question[] ="22. 이 사람의 아버지는 회사원입니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="23. 이 사람의 누나는 지금 일본에 살고 있습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="24. 두 사람은 다음주 금요일에 영화를 보러 갈 겁니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="25. 여자는 그날 저녁에 아르바이트가 없습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="26. 두 사람은 몇시에 영화를 볼 겁니까? ";
$answer[]=["a.	2시"," b. 3시","c. 4시","d. 5시"];

$question[] ="27. 남자가 오늘 제일 먼저 한 일은 무엇입니까?";
$answer[]=["a. 수영장에 갑니다","b. 공부합니다","c. 영화를 봅니다","d. 노래를 듣습니다"];

$question[] ="28. 오늘은 비가 많이 왔습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="29. 이 사람은 아침에 수영장에 갔습니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="30. 이 사람은 저녁에 영화를 봤습니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="31. 여자는 서울에 삽니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="32. 여자는 일주일에 2번 운동을 합니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="33. 여자는 밤에 빵을 자주 먹습니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="34. 밤에 음식이나 간식을 먹으면 소화가 잘 안 됩니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="35. 운동은 자주 하는 것보다 한번에 많이 하는 것이 좋습니다. ";
$answer[]=["TRUE","FALSE",];

$question[] ="36. 내일부터는 추석 연휴입니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="37. 팀장님은 내일 저녁에 고향에 갈 겁니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="38. 이링 씨는 고향 친구들과 같이 연휴를 보낼 겁니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="39. 한국사람들은 연휴에는 어른들께 세배를 합니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="40. 한국 사람들은 연휴에 친척들과 같이 여행을 합니다.";
$answer[]=["TRUE","FALSE",];

$question[] ="41. 늦습니다/ 미안해요.";
$answer[]=["a. 늦어서","b. 늦으면","c. 늦을때","d. 늦으러"];

$question[] ="42. 날씨가 더워요 / 아이스크림을 먹을까요?";
$answer[]=["a. 덥기 전에","b. 덥지만","c. 더우니까","d. 더운 후에"];

$question[] ="43. 친구와 같이 영화를 봤어요. / 집에 돌아왔어요.";
$answer[]=["a. 보려고","b. 본 후에","c. 봐서","d. 보면"];

$question[] ="44. 수영해요. / 준비운동을 하세요.";
$answer[]=["a. 하려면","b. 하고","c. 하기 때문에","d. 하기 전에"];

$question[] ="45. 한국에 가요. / 설악산에 꼭 가보세요.";
$answer[]=["a. 가면","b. 가기 전에","c. 가는 동안","d. 가러"];

$question[] ="46. 길이 너무 막혀서 아마 (           ).";
$answer[]=["a. 늦을 수 없어요.","b. 늦지 않아요.","c.  늦을 것 같아요.","d. 늦잖아요."];

$question[] ="47. 가 :  제 책 가지고 왔어요? <br> 나 : 미안해요. 잊어버렸어요. 내일은 꼭 가지고.";
$answer[]=["a.왔어요.","b. 올게요."," c. 올래요.","d. 올 수 없어요."];

$question[] ="48.  그 이야기를 누구 (              )들었어요?";
$answer[]=["a. 하고","b. 에게"," c. 한테서","d. 동안"];

$question[] ="49. 가   :  우리 주말에 공원에서 자전거를 탈까요? <br> 나   :  미안해요. 저는 자전거를 (                  ).";
$answer[]=["a. 타지 못해요.","b . 탈 수 있어요.","c. 탈게요.","d. 타고 싶어요."];

$question[] ="50. 오늘 아침에 밥을 먹어요./ 텔레비전 봤어요.";
$answer[]=["a. 먹어서","  b. 먹으려고"," c. 먹지만","d. 먹으면서"];




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
    <h4 align="center">Easy Korean - Level Once Course Exam</h3>

    <span >Allowed Time - 30 min</span>
    <span style="float:right">Marks - 50</span>
    <br>
    <br>
    
    <h5><u>읽기 ( READING )</u></h5><br>
    <h6>A.	다음을 읽고 질문에 답하십시오. </h6><br>
    <p>
    저는 오늘 친구와 약속이 있었습니다. 약속 시간은 2시반이었습니다. 약속 장소는 롯데 백화점 앞이었습니다. 제 친구는 약속을 잘 지킵니다. 그렇지만 저는 언제나 늦습니다. 그래서 오늘은 일찍 가고 싶었습니다. 저는 택시를 타고 명동으로 갔습니다. 약속 장소에 2시 25분에 도착했습니다. 그렇지만 친구는 없었습니다. 한 시간 기다렸지만 친구는 오지 않았습니다. 그 때 전화가 왔습니다. 친구였습니다. 친구는 잠실 롯데 백화점 앞에서 기다렸습니다.  
    </p>

    <?php  
        //for question 1 to 5
        for($i=0;$i<5;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <h6>B. 다음을 읽고 질문에 답하십시오. </h6><br>
    <p>
    어제는 일요일이었습니다. 학교 앞에서 친구와 약속이 있었습니다. 친구를 만나서 식당에 갔습니다. 날씨가 더워서 우리는 냉명을 먹었습니다. 점심을 먹고 커피숍에 갔습니다. 커피숍에서 차도 마시고 음악도 들었습니다. 그리고 달은 초콜릿 케이크도 먹었습니다. 차를 마시고 지하철을 타고 백화점에 갔습니다. 백화점에 가서 하얀색 바지를 샀습니다. 어제는 참 재미있었습니다.
    </p>

    <?php  
        //for question 6 to 10
        for($i=5;$i<10;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <h6>C. 다음을 읽고 질문에 답하십시오. </h6><br>
    <p>
        제 고향은 1년 내내 무척 덥습니다. 하지만 한국에는 사계절이 있어서 좋습니다. 저는 지난 해 가을에 한국에 왔습니다.날씨가 선선했습니다. 춥지도 않고 덥지도 않았습니다. 그리고 가을 산은 단풍이 들어서 아주 아름다웠습니다. 지금은 추운 겨울이 지나고 따뜻한 봄입니다. 여기저기에 예쁜 꽃도 많이 피었습니다. 그래서 사람들은 봄꽃을 구경하러 산이나 공원에 갑니다. 저도 다음 주말에 반 친구들과 소풍을 가겠습니다. 
    </p>

    <?php  
        //for question 11 to 15
        for($i=10;$i<15;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <h6>D. 다음을 읽고 질문에 답하십시오.  </h6><br>
    <p>
        저는 오후에 꽃을 사러 꽃집에 갔습니다. 꽃집에는 여러 가지 꽃이 있었습니다. 빨간색 카네이션 꽃이 많았습니다. 사람들은 빨간색 카네이션 꽃을 많이 샀습니다. 저도 빨간색 카네이션 꽃을 두 다발 샀습니다. 꽃은 한 다발에 6,000 원이었습니다. 꽃값이 조금 비쌌습니다. 주인 아주머니는 1,000원을 깎아 주셨습니다. 내일은 부모님께 이 꽃을 드리겠습니다. 
    </p>

    <?php  
        //for question 16 to 20
        for($i=15;$i<20;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <h5><u>듣기 ( LISTENING ) </u></h5><br>
    <h6>E. 대화를 듣고 질문에 답하십시오. </h6><br>
    <div align="center" id="teacherVoice">
        <br>
        <audio controls autoplay><source src="https://www.calamuseducation.com/uploads/lessons/audios/QuestionE.mp3" type="audio/mpeg"></audio>
        <br>
    </div>

    <?php  
        //for question 21 to 23
        questionFormatTwo($question[20],$answer[20],21);
        for($i=21;$i<23;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <h6>F. 대화를 듣고 질문에 답하십시오.</h6><br>
    <div align="center" id="teacherVoice">
        <br>
        <audio controls autoplay><source src="https://www.calamuseducation.com/uploads/lessons/audios/QuestionF.mp3" type="audio/mpeg"></audio>
        <br>
    </div>

    <?php  
        //for question 24 to 26
        for($i=23;$i<25;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
        questionFormatTwo($question[25],$answer[25],26);
    ?>

    <h6>G. 대화를 듣고 질문에 답하십시오.</h6><br>
    <div align="center" id="teacherVoice">
        <br>
        <audio controls autoplay><source src="https://www.calamuseducation.com/uploads/lessons/audios/QuestionG.mp3" type="audio/mpeg"></audio>
        <br>
    </div>

    <?php  
        //for question 27 to 30
        questionFormatTwo($question[26],$answer[26],27);
        for($i=27;$i<30;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <h6>H. 대화를 듣고 질문에 답하십시오.</h6><br>
    <div align="center" id="teacherVoice">
        <br>
        <audio controls autoplay><source src="https://www.calamuseducation.com/uploads/lessons/audios/QuestionH.mp3" type="audio/mpeg"></audio>
        <br>
    </div>

    <?php  
        //for question 31 to 35
        for($i=30;$i<35;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>

    <h6>I. 대화를 듣고 질문에 답하십시오.</h6><br>
    <div align="center" id="teacherVoice">
        <br>
        <audio controls autoplay><source src="https://www.calamuseducation.com/uploads/lessons/audios/QuestionI.mp3" type="audio/mpeg"></audio>
        <br>
    </div>

    <?php  
        //for question 36 to 40
        for($i=35;$i<40;$i++){
            questionFormatOne($question[$i],$answer[$i],$i+1);
        }
    ?>


    <h5><u>문법 ( GRAMMAR  )</u></h5><br>

    <?php  
        //for question 41 to 50
        for($i=40;$i<50;$i++){
            questionFormatTwo($question[$i],$answer[$i],$i+1);
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
        var ansChecker=["12","21","32","42","51","62","71","82","92","101","112","121","131","142","151","162","171","182","191","202","213","221","232","242","251","263","272","282","292","301","311","322","331","341","352","362","372","381","391","402","411","423","432","444","451","463","472","483","491","504"];
       
        function showAnswer(){
            for(var i=0,j=ansChecker.length;i<j;i++){
              var ansSpan =document.getElementById("right"+ansChecker[i]);
              ansSpan.setAttribute('style','background-color:blue;padding:5px;border-radius:3px;color:white; font-weight:bold;');
            }    
        }

        function checkAnswer(){

            var result=0;

            document.getElementById("bt-showAns").setAttribute('Style','');

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
            ajax.send("userId=<?php echo $userId?>&major=korea&test=levelone_exam&result="+result);

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

