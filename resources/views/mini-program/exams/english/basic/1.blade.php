@extends('layouts.mini-program')

@section('title', 'Basic Exam 1 - Calamus')

@section('styles')
    @include('mini-program.exams.questions_helper')
    <style>
        .exam-header {
            padding: 16px 0;
            text-align: center;
        }
        .exam-title { font-weight: 900; color: var(--text-title); margin-bottom: 2px; font-size: 1.2rem; }
        .exam-meta { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; }
        .timer-badge {
            position: fixed;
            top: 10px;
            right: 12px;
            background: var(--accent);
            color: #fff;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-weight: 800;
            font-size: 0.85rem;
            z-index: 1001;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        
        .action-bar {
            position: sticky;
            bottom: 12px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            padding: 10px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            display: flex;
            gap: 8px;
            z-index: 1000;
            margin-top: 20px;
        }
        .btn-check {
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 12px;
            font-weight: 700;
            flex: 1;
            font-size: 0.9rem;
        }
        .btn-show {
            background: #fecaca;
            color: #b91c1c;
            border: none;
            border-radius: var(--radius-sm);
            padding: 12px;
            font-weight: 700;
            flex: 1;
            font-size: 0.9rem;
        }
        #final-result {
            padding: 16px;
            border-radius: var(--radius-md);
            margin-top: 16px;
            font-weight: 800;
            text-align: center;
            color: white;
            display: none;
            font-size: 0.95rem;
        }
    </style>
@endsection

@section('content')
    <div class="timer-badge" id="timer">15:00</div>

    <div class="exam-header">
        <h4 class="exam-title">Basic Exam 1</h4>
        <div class="exam-meta">
            <span>15m • 50 Marks</span>
        </div>
    </div>

    <div class="questions-list">
        <?php
            $questions = [
                ["1. -----am a student.", ["a. He", "b. She", "c. I"]],
                ["2. It is ----- orange.", ["an", "a", "the"]],
                ["3. A: Hello! B: ----", ["a. Hi.", "b. Good night.", "c. Have a nice day."]],
                ["4. Maung Maung is my brother. He ----- 4 years old.", ["a. is", "b. are", "c. am"]],
                ["5. Daw Theingi is my mother. ----- is a housewife.", ["a. They", "b. She", "c. He"]],
                ["6. A: What do you do? B: -----", ["a. I am 30 years old.", "b. I am a teacher.", "c. I like cakes."]],
                ["7. Si Si and Ni Ni are friends. ----- are students.", ["a. She", "b. I", "c. They"]],
                ["8. Myanmar ----- a small country.", ["a. am", "b. is", "c. are"]],
                ["9. The moon ----- around the earth.", ["a. rotated", "b. rotating", "c. rotates"]],
                ["10. Si Si ----- my house yesterday.", ["a. visits", "b. visited", "c. visiting"]],
                ["11. My sister is ----- right now.", ["a. study", "b. studies", "c. studying"]],
                ["12. She is a ----- girl.", ["a. beautiful", "b. more beautiful", "c. most beautiful"]],
                ["13. There are 10 ----- in the basket.", ["a. egg", "b. apples", "c. apple"]],
                ["14. Nyi Nyi is ----- than Maung Maung.", ["a. tall", "b. taller", "c. tallest"]],
                ["15. I didn't eat much ----- .", ["a. rice", "b. rices", "c. water"]],
                ["16. A: See you later. B: -----", ["a. Yes, please.", "b. Good evening.", "c. Good bye!"]],
                ["17. Cherry Cinema is the ------ cinema in my town.", ["a. big", "b. bigger", "c. biggest"]],
                ["18. A: What's your favourite sport? B: -----", ["a. I like jeans.", "b. No, I don't.", "c. I like volleyball."]],
                ["19. A: What do you do for fun? B: I like to go ----- .", ["a. swimming", "b. doctor", "c. hamburger"]],
                ["20. My mother ----- for us everyday.", ["a. cook", "b. cooks", "c. cooking"]],
                ["21. There are a ----- pencils.", ["a. much", "b. many", "c. few"]],
                ["22. A: Would you like some tea? B: ----- .", ["a. No, thank you.", "b. I don't think so.", "c. Sorry."]],
                ["23. We ----- dinner together last night.", ["a. eated", "b. ate", "c. eaten"]],
                ["24. A: are you feeling sick. B: ------", ["a. I'm sorry.", "b. I have got a toothache.", "c. Sure."]],
                ["25. Don't make a noise! My sister ----- .", ["a. study", "b. was studying", "c. is studying"]],
                ["26. It was hot so I ----- the window.", ["a. open", "b. opens", "c. opened"]],
                ["27. We ----- to the cinema tonight.", ["a. are going", "b. go", "c. went"]],
                ["28. Do you like coffee ----- tea?", ["a. and", "b. or", "c. so"]],
                ["29. Our class starts ----- 9 am.", ["a. at", "b. on", "c. in"]],
                ["30. I go to school ----- eating my breakfast.", ["a. and ", "b. after ", "c. so"]],
                ["31. I ----- go to the club because I don't like noisy music.", ["a. always", "b. sometimes", "c. never"]],
                ["32. She was 22 years old ----- 2016 .", ["a. on", "b. in", "c. at"]],
                ["33. They are speaking ----- .", ["a. loud", "b. louder", "c. loudly."]],
                ["34. Let's throw a party ----- Sunday.", ["a. at ", "b. on ", "c. in"]],
                ["35. It ----- on comming Thursday.", ["a. rain", "b. rains", "c. will rain"]],
                ["36. The team ----- won the match.", ["a. easily ", "b. easy", "c. ease"]],
                ["37. ----- you text me tomorrow?", ["a. Are", "b. Will", "c. Do"]],
                ["38. I ----- tell anyone.I promise.", ["a. didn't ", "b. won't", "c. don't"]],
                ["39. Si Si goes to school ----- bus.", ["a. in", "b. on", "c. by"]],
                ["40. I can speak ----- .", ["a. Spain", "b. Spanish", "c. England"]],
                ["41. She is angry -----she is silent.", ["a. but ", "b. because ", "c. before"]],
                ["42. Let's go ----- the park.", ["a. at", "b. on", "c. to "]],
                ["43. You need to do exercise ----- you want to lose weight.", ["a. so", "b. and", "c. if"]],
                ["44. This is an ----- dress.", ["a. easy ", "b. elegant", "c. comfortable"]],
                ["45. Nyi Nyi lives in a ----- mansion.", ["a. attractive ", "b. homely", "c. huge"]],
                ["46. I bought milk ----- the market.", ["a. from", "b. on", "c. to"]],
                ["47. There are lamp-posts ----- the street.", ["a. along", "b. on", "c. in"]],
                ["48. Today is a  ----- and sunny day.", ["a. cold", "b. hot", "c. humid"]],
                ["49. She is brushing her teeth ----- the mirror.", ["a. in front of", "b. behind", "c. above"]],
                ["50. Our field trip was very ----- and we had a great time.", ["a. interesting ", "b. boring", "c. Tiring"]]
            ];

            foreach($questions as $index => $q) {
                questionFormatOne($q[0], $q[1], $index + 1);
            }
        ?>
    </div>

    <div class="action-bar">
        <button id="bt-checkAns" class="btn-check tap-active" onClick="checkAnswer()">Check Answer</button>
        <button id="bt-showAns" class="btn-show tap-active" onclick="showAnswer();" style="display:none;">Show Answer</button>
    </div>

    <div id="final-result"></div>
@endsection

@section('scripts')
<script>
    var ansChecker=['13','21','31','41','52','62','73','82','93','102','113','121','132','142','151','163','173','183','191','202','213','221','232','242','253','263','271','282','291','302','313','322','333','342','353','361','372','382','393','402','411','423','433','442','453','461','471','482','491','501'];
    
    function showAnswer(){
        for(var i=0,j=ansChecker.length;i<j;i++){
            var ansSpan = document.getElementById("right"+ansChecker[i]);
            if(ansSpan) {
                const label = ansSpan.closest('.option-label');
                if(label) {
                    label.style.background = 'var(--accent-soft)';
                    label.style.borderColor = 'var(--accent)';
                    ansSpan.style.color = 'var(--accent)';
                    ansSpan.style.fontWeight = '800';
                }
            }
        }    
    }

    function checkAnswer(){
        var result=0;
        document.getElementById("bt-showAns").style.display = 'block';

        for(var i=0;i<50;i++){
            var no=i+1;
            for(var j=1;j<5;j++){
                var ansInput=document.getElementById("ans"+no+j);
                if(ansInput){
                    if(ansInput.checked){
                        var inputId=ansInput.getAttribute("id");
                        if(inputId=="ans"+ansChecker[i]){
                            result++;
                            document.getElementById('correct'+no+j).style.display = 'inline-block';
                        }else{
                            document.getElementById('error'+no+j).style.display = 'inline-block';
                        }
                    }
                }
            }
        }

        document.getElementById("bt-checkAns").style.display = 'none';
        showFinalResult(result);
        saveExamResult(result);
        clearInterval(stopTimer);
    }

    function showFinalResult(result){
        var finalResult=document.getElementById('final-result');
        finalResult.style.display = 'block';
        if(result<8){
            finalResult.style.backgroundColor = '#ef4444';
            finalResult.innerHTML="Basic Level • "+result+"/50";
        }else if(result>=8 && result<=13){
            finalResult.style.backgroundColor = '#f59e0b';
            finalResult.innerHTML="Elementary Level • "+result+"/50";
        }else if(result>13 && result<=20){
            finalResult.style.backgroundColor = '#fbbf24';
            finalResult.style.color = '#111827';
            finalResult.innerHTML="Pre-Intermediate Level • "+result+"/50";
        }else{
            finalResult.style.backgroundColor = '#10b981';
            finalResult.innerHTML="Intermediate Level • "+result+"/50";
        }
        finalResult.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    function saveExamResult(result){
        $.post("https://www.calamuseducation.com/calamus/api/exam/result/update", {
            userId: '{{ $userId }}',
            major: 'english',
            test: 'basic_exam',
            result: result
        });
    }

    var totalSeconds = 900;
    var timerEle = document.getElementById('timer');
    var stopTimer = setInterval(updateTimer, 1000);

    function updateTimer(){
        totalSeconds--;
        if(totalSeconds <= 0){
            clearInterval(stopTimer);
            timerEle.style.background = '#ef4444';
            timerEle.innerHTML = "00:00";
            checkAnswer();
        } else {
            var m = Math.floor(totalSeconds / 60);
            var s = totalSeconds % 60;
            timerEle.innerHTML = (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);
            if(totalSeconds < 60) {
                timerEle.style.background = '#ef4444';
            }
        }
    }
</script>
@endsection