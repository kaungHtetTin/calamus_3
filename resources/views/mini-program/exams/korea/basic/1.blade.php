@extends('layouts.mini-program')

@section('title', 'Easy Korean - Basic Course Exam')

@section('styles')
    <style>
        .exam-header {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        .question-section {
            background: #007bff;
            color: white;
            padding: 12px 18px;
            border-radius: 10px;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .timer-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            font-family: monospace;
        }
        .result-card {
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            color: white;
            font-weight: 800;
            margin: 20px 0;
            display: none;
        }
        .custom-control-label {
            padding: 8px 12px;
            border-radius: 8px;
            transition: background 0.2s;
            cursor: pointer;
            display: block;
        }
        .custom-control-input:checked ~ .custom-control-label {
            background: #e7f1ff;
            color: #0056b3;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    @include('mini-program.exams.questions_helper')

    <div class="timer-badge" id="timer">30:00</div>

    <div class="exam-header text-center">
        <h4 class="font-weight-bold mb-1">Calamus Education</h4>
        <h5 class="text-primary mb-3">Easy Korean - Basic Course Exam</h5>
        <div class="d-flex justify-content-between text-muted small px-2">
            <span><i class="fa fa-clock-o"></i> 30 min</span>
            <span><i class="fa fa-check-square-o"></i> 50 Marks</span>
        </div>
    </div>

    @php
        // Questions Data (Extracted from legacy file)
        $questions = [
            ["1. 눈이 나쁩니다. ( ...... )을 씁니다.", ["사전","수박","안경","지갑"]],
            ["2. 저는 ( ...... ) 에 갑니다. 밥을 먹습니다.", ["식당","서점","학교","회사"]],
            ["3. 비가 옵니다. 하지만 ( ...... ) 이 없습니다.", ["가방","우산","책","모자 "]],
            ["4. 우리 언니는 모델입니다. 키가 ( ...... ) 큽니다.", ["아주","아직","가끔"," 먼저"]],
            ["5. 저는 사과를 좋아합니다. 그래서 ( ...... ) 먹습니다.", ["아마","가장","보통","자주"]],
            ["6. 오빠가 아직 안 옵니다. 오빠를 ( ...... ).", ["있습니다","기다립니다","보냅니다","가르칩니다"]],
            ["7. 주스가 없습니다. 그래서 물을 ( ...... ).", ["합니다","모릅니다","좋습니다"," 마십니다"]],
            ["8. 도서관입니다. 책이 아주 ( ...... ).", ["많습니다","넓습니다","쉽습니다","가볍습니다"]],
            ["9. 친구의 생일입니다. 그래서 친구들과 같이 사진을( ...... ).", ["만납니다","빌립니다","찍습니다","배웁니다"]],
            ["10. 저는 매일 밤 12 시에 잡니다. 아침 7시에 ( ...... ).", ["옵니다","갑니다","일어납니다","먹습니다"]],
            
            // Grammar
            ["11. 시장에 갑니다. 고기 ( ...... ) 채소를 삽니다.", ["과","만","하고","도"]],
            ["12. 커피를 좋아합니다. 우유 ( ...... ) 좋아합니다.", ["는","도","를","가"]],
            ["13. 저녁을 ( ...... ) 텔레비전을 봅니다.", ["먹지만","먹으러","먹으니까","먹고"]],
            ["14. 저는 미국에 있습니다. 부모님 ( ...... ) 한국에 있습니다.", ["은","하고","는","도"]],
            ["15. 친구들을 파티에 ( ...... ) 싶습니다.", ["초대하고","가르치고","기다리고","만나고"]],
            ["16. 한국에는 사계절 ( ...... ) 있습니다.", ["이","은","는","가"]],
            ["17. 지금 비가 ( ...... ) 너무 덥습니다.", ["오니까","오면","오고","오지만"]],
            ["18. 집 근처 ( ...... ) 백화점이 없습니다.", ["고","하고","에","는"]],
            ["19. 축구 ( ...... ) 좋아하는 사람들이 많습니다.", ["를","을","도","에"]],
            ["20. 수업이 ( ...... ) 전화하세요.", ["끝나지만","끝나면","끝나서"," 끝나려고"]],

            // Visuals
            ["21-students.jpg", ["가수","학생","요리사","회사원"]],
            ["22-park.jpg", ["공원","도서관","회사","학교"]],
            ["23-spa.jpg", ["화장실","사무실","미용실","거실"]],
            ["24-apple.jpg", ["복숭아","바나나","수박","사과"]],
            ["25-shoes.jpg", ["시계","구두","책","의자"]],
            ["26-grape.jpg", ["망고","토마토","딸기","포도"]],
            ["27-family.jpg", ["아버지","어머니","가족","동생"]],
            ["28-korea.jpg", ["중국","일본","태국"," 한국"]],
            ["29-ring.jpg", ["귀걸이","반지","팔찌","목거리"]],
            ["30-green.jpg", ["초록색","빨간색","노란색","파란색"]],

            // Topics
            ["31. 딸기를 먹습니다. 딸기가 맛있습니다.", ["날씨","과일","생일","공부"]],
            ["32. 저는 김예원입니다. 이 사람은 최수빈입니다.", ["나이","이름","가족","시간"]],
            ["33. 내일은 토요일입니다. 놀이공원에 가겠습니다.", ["계획","날짜","약속","장소"]],
            ["34. 오빠가 있습니다. 언니도 있습니다.", ["취미","직업","친구","가족"]],
            ["35. 한국에는 봄, 여름, 가을, 겨울이 있습니다. 지금은 겨울입니다.", ["날씨","나라","계절","휴일"]],
            ["36. 오늘은 7월 1일입니다. 내일은 7월 2일입니다.", ["날짜","방학","하루"," 아침"]],
            ["37. 오늘은 하늘이 맑습니다. 덥지 않습니다.", [" 주말","날씨","봄","구름"]],
            ["38. 아버지는 의사입니다. 어머니는 선생님입니다.", ["학교","병원","집","직업"]],
            ["39. 누나는 미국에 있습니다. 저는 한국에 있습니다.", ["나라","방학","여행","장소"]],
            ["40. 운동을 좋아합니다. 친구들과 농구를 자주 합니다.", ["공부","시간","취미","쇼핑"]],

            // Dialogues
            ["회사원입니까?", ["아니요, 변호사입니다.","네, 회사원이 아닙니다.","아니요, 회사원입니다.","네, 대학생입니다."]],
            ["책이 있어요?", ["네, 책이 많아요.","아니요, 책이 없어요.","네, 책을 좋아해요.","아니요, 책을 읽어요."]],
            ["지금 무엇을 먹어요?", ["자주 먹어요.","집에서 먹어요.","언니하고 먹어요.","김밥을 먹어요."]],
            ["맛있게 드세요.", ["좋겠습니다.","잘 먹겠습니다.","모르겠습니다.","죄송합니다."]],
            ["생일 축하해요.", ["고마워요.","미안해요.","괜찮아요.","반가워요."]],
            ["안녕히 계세요.", ["안녕히 계세요","들어가세요.","안녕히 가세요.","어서 오세요."]],
            ["영화를 봐요?", ["네, 영화를 해요.","네, 영화가 아닙니다.","아니요, 영화가 재미있어요.","아니요, 영화를 안 봐요."]],
            ["수업이 어때요?", ["학교에 가요.","수업이 있어요.","아주 재미있어요.","지금 읽어요."]],
            ["언제 친구를 만나요?", ["학생을 만나요.","동생하고 만나요.","공원에서 만나요.","내일 만나요."]],
            ["이 빨간색 모자 어때요?", ["아주 예뻐요.","오늘 입어요.","제 모자예요.","어제 샀어요."]],
        ];
    @endphp

    <div class="question-section">다음을 보고 빈칸에 들어갈 알맞은 것을 고르십시오.</div>
    @for($i=0; $i<10; $i++)
        {!! questionFormatOne($questions[$i][0], $questions[$i][1], $i+1) !!}
    @endfor

    <div class="question-section">다음을 보고 빈칸에 들어갈 알맞은 문법을 고르십시오.</div>
    @for($i=10; $i<20; $i++)
        {!! questionFormatOne($questions[$i][0], $questions[$i][1], $i+1) !!}
    @endfor

    <div class="question-section">다음 사진을 보고 알맞은 어휘를 고르십시오.</div>
    @for($i=20; $i<30; $i++)
        {!! questionFormatTwo("https://www.calamuseducation.com/uploads/lessons/images/".$questions[$i][0], $questions[$i][1], $i+1) !!}
    @endfor

    <div class="question-section">무엇에 대한 이야기입니까? 알맞은 것을 고르십시오.</div>
    @for($i=30; $i<40; $i++)
        {!! questionFormatOne($questions[$i][0], $questions[$i][1], $i+1) !!}
    @endfor

    <div class="question-section">다음 대화를 보고 B 가 이어서 할 말을 고르십시오.</div>
    @for($i=40; $i<50; $i++)
        {!! questionFormatThree($questions[$i][0], $questions[$i][1], $i+1) !!}
    @endfor

    <div class="mt-4 mb-5">
        <button id="bt-checkAns" class="btn btn-primary btn-block py-3 font-weight-bold" style="border-radius: 12px;" onClick="checkAnswer()">Check Answer</button>
        <button id="bt-showAns" class="btn btn-outline-danger btn-block py-2 mt-2" style="border-radius: 10px; display:none;" onclick="showAnswer()">Show Correct Answers</button>
    </div>

    <div id="final-result" class="result-card"></div>
@endsection

@section('scripts')
<script>
    const ansChecker = ["13","21","32","41","54","62","74","81","93","103","113","122","134","141","151","161","174","183","191","202","212","221","233","244","252","264","273","284","292","301","312","322","331","344","353","361","372","384","391","403","411","422","434","442","451","463","474","483","494","501"];
    let second = 0;
    const timerEle = document.getElementById('timer');
    const stopTimer = setInterval(updateTimer, 1000);

    function updateTimer() {
        second++;
        let timeLeft = 1800 - second;
        if (timeLeft <= 0) {
            clearInterval(stopTimer);
            checkAnswer();
            return;
        }
        let m = Math.floor(timeLeft / 60);
        let s = timeLeft % 60;
        timerEle.innerHTML = `${m < 10 ? '0' + m : m}:${s < 10 ? '0' + s : s}`;
        if (timeLeft < 300) timerEle.style.background = '#dc3545';
    }

    function showAnswer() {
        ansChecker.forEach(ans => {
            const el = document.getElementById("right" + ans);
            if (el) el.style.cssText = 'background: #007bff; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;';
        });
    }

    function checkAnswer() {
        let score = 0;
        clearInterval(stopTimer);
        document.getElementById("bt-showAns").style.display = 'block';
        document.getElementById("bt-checkAns").style.display = 'none';

        for (let i = 1; i <= 50; i++) {
            for (let j = 1; j <= 4; j++) {
                const radio = document.getElementById(`ans${i}${j}`);
                if (radio && radio.checked) {
                    if (`ans${i}${j}` === `ans${ansChecker[i-1]}`) {
                        score++;
                        document.getElementById(`correct${i}${j}`).style.display = 'inline-block';
                    } else {
                        document.getElementById(`error${i}${j}`).style.display = 'inline-block';
                    }
                }
            }
        }

        showFinalResult(score);
        saveExamResult(score);
    }

    function showFinalResult(score) {
        const card = document.getElementById('final-result');
        card.style.display = 'block';
        let status = '';
        let color = '';

        if (score < 20) { status = 'Keep Practicing!'; color = '#dc3545'; }
        else if (score <= 30) { status = 'Good!'; color = '#fd7e14'; }
        else if (score <= 40) { status = 'Very Good!'; color = '#ffc107'; card.style.color = '#000'; }
        else { status = 'Excellent!'; color = '#28a745'; }

        card.style.backgroundColor = color;
        card.innerHTML = `<div class="h3 mb-1">${status}</div><div class="h5 mb-0">${score} / 50 Marks</div>`;
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }

    async function saveExamResult(score) {
        try {
            const response = await fetch("https://www.calamuseducation.com/calamus/api/exam/result/update", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `userId={{ $userId }}&major=korea&test=basic_exam&result=${score}`
            });
        } catch (e) {
            console.error("Result save failed", e);
        }
    }
</script>
@endsection
