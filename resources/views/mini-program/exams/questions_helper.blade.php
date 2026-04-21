<?php
if (!function_exists('questionFormatOne')) {
    function questionFormatOne($question, $answer, $no) {
        echo "<div class='question-container'>";
        echo "<p class='question-text'>{$no}. {$question}</p>";
        echo "<div class='options-grid'>";
        foreach ($answer as $i => $text) {
            $ansNo = $i + 1;
            echo "
                <div class='option-item'>
                    <input type='radio' id='ans{$no}{$ansNo}' name='q{$no}' class='option-input'>
                    <label class='option-label tap-active' for='ans{$no}{$ansNo}'>
                        <span class='option-text' id='right{$no}{$ansNo}'>{$text}</span>
                        <div class='status-icons'>
                            <i id='correct{$no}{$ansNo}' class='fa fa-check-circle text-success' style='display:none;'></i>
                            <i id='error{$no}{$ansNo}' class='fa fa-times-circle text-danger' style='display:none;'></i>
                        </div>
                    </label>
                </div>
            ";
        }
        echo "</div></div>";
    }
}

if (!function_exists('questionFormatTwo')) {
    function questionFormatTwo($imgUrl, $answer, $no) {
        echo "<div class='question-container'>";
        echo "<p class='question-text'>{$no}.</p>";
        echo "<div class='row align-items-center g-2 mb-2'>";
        echo "<div class='col-4'><img src='{$imgUrl}' class='img-fluid rounded-md border'></div>";
        echo "<div class='col-8'>";
        foreach ($answer as $i => $text) {
            $ansNo = $i + 1;
            echo "
                <div class='option-item mb-1'>
                    <input type='radio' id='ans{$no}{$ansNo}' name='q{$no}' class='option-input'>
                    <label class='option-label tap-active' for='ans{$no}{$ansNo}'>
                        <span class='option-text' id='right{$no}{$ansNo}'>{$text}</span>
                        <div class='status-icons'>
                            <i id='correct{$no}{$ansNo}' class='fa fa-check-circle text-success' style='display:none;'></i>
                            <i id='error{$no}{$ansNo}' class='fa fa-times-circle text-danger' style='display:none;'></i>
                        </div>
                    </label>
                </div>
            ";
        }
        echo "</div></div></div>";
    }
}

if (!function_exists('questionFormatThree')) {
    function questionFormatThree($dialogue, $answer, $no) {
        echo "<div class='question-container'>";
        echo "<p class='question-text'>{$no}.</p>";
        echo "<div class='dialogue-box mb-2'>
                <div class='dialogue-line'>A: {$dialogue}</div>
                <div class='dialogue-line'>B: <span class='placeholder'>.........</span></div>
              </div>";
        echo "<div class='options-grid'>";
        foreach ($answer as $i => $text) {
            $ansNo = $i + 1;
            echo "
                <div class='option-item'>
                    <input type='radio' id='ans{$no}{$ansNo}' name='q{$no}' class='option-input'>
                    <label class='option-label tap-active' for='ans{$no}{$ansNo}'>
                        <span class='option-text' id='right{$no}{$ansNo}'>{$text}</span>
                        <div class='status-icons'>
                            <i id='correct{$no}{$ansNo}' class='fa fa-check-circle text-success' style='display:none;'></i>
                            <i id='error{$no}{$ansNo}' class='fa fa-times-circle text-danger' style='display:none;'></i>
                        </div>
                    </label>
                </div>
            ";
        }
        echo "</div></div>";
    }
}
?>

<style>
    .question-container {
        background: var(--bg-card);
        padding: 14px;
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
        margin-bottom: 12px;
    }
    .question-text {
        font-weight: 800;
        color: var(--text-title);
        font-size: 0.95rem;
        margin-bottom: 10px;
        line-height: 1.4;
    }
    .options-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }
    .option-item {
        position: relative;
    }
    .option-input {
        position: absolute;
        opacity: 0;
    }
    .option-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        background: var(--bg-page);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        cursor: pointer;
        margin: 0;
        width: 100%;
        min-height: 44px;
        transition: all 0.2s;
    }
    .option-input:checked + .option-label {
        background: var(--accent-soft);
        border-color: var(--accent);
    }
    .option-text {
        font-weight: 600;
        color: var(--text-body);
        font-size: 0.85rem;
    }
    .option-input:checked + .option-label .option-text {
        color: var(--accent);
    }
    .dialogue-box {
        background: var(--bg-page);
        padding: 10px;
        border-radius: var(--radius-sm);
        border-left: 3px solid var(--accent);
    }
    .dialogue-line {
        font-size: 0.85rem;
        color: var(--text-body);
        font-weight: 500;
    }
    .placeholder {
        color: var(--text-muted);
        letter-spacing: 1px;
    }
    .status-icons {
        display: flex;
        align-items: center;
        font-size: 0.8rem;
    }
    
    @media (max-width: 480px) {
        .options-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
