@extends('layouts.mini-program')

@section('title', ucfirst($major) . ' Exams - Calamus')

@section('styles')
    <style>
        .header-section { 
            padding: 20px 0 16px; 
            margin-bottom: 8px;
        }
        .header-section h4 { 
            font-size: 1.4rem;
            font-weight: 900;
            color: var(--text-title);
            margin-bottom: 2px;
        }
        .header-section p {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .exam-list {
            display: grid;
            gap: 10px;
        }
        .exam-item { 
            background: var(--bg-card); 
            border-radius: var(--radius-md); 
            padding: 14px 16px;
            display: flex;
            align-items: center;
            text-decoration: none !important;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .exam-item:active { 
            border-color: var(--accent);
            background: #fdfdfd;
        }
        
        .exam-icon {
            width: 40px;
            height: 40px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .exam-info { flex: 1; min-width: 0; }
        .exam-name { 
            font-weight: 700; 
            color: var(--text-title); 
            font-size: 0.95rem; 
            display: block;
            margin-bottom: 2px;
        }
        .exam-stats { 
            font-size: 0.7rem; 
            color: var(--text-muted); 
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .exam-stats i { margin-right: 4px; opacity: 0.6; }
        
        .btn-go { 
            color: var(--text-muted);
            font-size: 0.8rem;
            opacity: 0.4;
        }

        .external-exam {
            background: #f0fdf4;
            border-color: #bcf0da;
        }
        .external-exam .exam-icon {
            background: #dcfce7;
            color: #10b981;
        }
        .external-exam .exam-name { color: #065f46; }
    </style>
@endsection

@section('content')
    <div class="header-section text-center">
        <h4>{{ ucfirst($major) }} Exams</h4>
        <p>Test your progress</p>
    </div>

    <div class="exam-list">
        @php
            if($major == 'korea') {
                $exams = [
                    ['title' => 'Basic Course Exam', 'category' => 'basic', 'id' => 1, 'marks' => 50, 'time' => '30m', 'icon' => 'star'],
                    ['title' => 'Level One Exam', 'category' => 'level-one', 'id' => 1, 'marks' => 50, 'time' => '30m', 'icon' => 'shield'],
                    ['title' => 'Level Two Exam', 'category' => 'level-two', 'id' => 1, 'marks' => 25, 'time' => '30m', 'icon' => 'trophy'],
                    ['title' => 'Level Three Exam', 'category' => 'level-three', 'id' => 1, 'marks' => 25, 'time' => '30m', 'icon' => 'diamond'],
                    ['title' => 'Level Four Exam', 'category' => 'level-four', 'id' => 1, 'marks' => 25, 'time' => '30m', 'icon' => 'bolt'],
                ];
            } else {
                $exams = [
                    ['title' => 'Cambridge Level Test', 'category' => 'level-test', 'id' => 1, 'marks' => 25, 'time' => '10m', 'icon' => 'check-square'],
                    ['title' => 'Basic Course Exam', 'category' => 'basic', 'id' => 1, 'marks' => 50, 'time' => '15m', 'icon' => 'star'],
                ];
            }
        @endphp

        @foreach($exams as $exam)
            <a href="{{ route('mini-program.exams.show', ['major' => $major, 'category' => $exam['category'], 'id' => $exam['id'], 'userId' => $userId]) }}" class="exam-item tap-active">
                <div class="exam-icon">
                    <i class="fa fa-{{ $exam['icon'] }}"></i>
                </div>
                <div class="exam-info">
                    <span class="exam-name">{{ $exam['title'] }}</span>
                    <div class="exam-stats">
                        <span class="mr-3"><i class="fa fa-file-text-o"></i> {{ $exam['marks'] }}</span>
                        <span><i class="fa fa-clock-o"></i> {{ $exam['time'] }}</span>
                    </div>
                </div>
                <div class="btn-go">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </a>
        @endforeach

        @if($major == 'english')
            <a href="https://www.calamuseducation.com/uploads/lessons/easyenglish/english-level-test-2.html" class="exam-item external-exam tap-active">
                <div class="exam-icon">
                    <i class="fa fa-external-link"></i>
                </div>
                <div class="exam-info">
                    <span class="exam-name">CEFR Level Test</span>
                    <div class="exam-stats">
                        <span><i class="fa fa-globe"></i> External Exam</span>
                    </div>
                </div>
                <div class="btn-go">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </a>
        @endif
    </div>
@endsection
