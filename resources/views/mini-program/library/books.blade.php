@extends('layouts.mini-program')

@section('title', $category . ' - ' . ucfirst($major) . ' Library')

@section('styles')
    <style>
        .header-section { 
            padding: 16px 0; 
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .btn-back {
            color: var(--text-title);
            font-size: 0.9rem;
            margin-right: 12px;
            text-decoration: none !important;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-card);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        .header-info h4 { 
            font-weight: 800; 
            color: var(--text-title);
            margin: 0;
            font-size: 1.1rem;
            letter-spacing: -0.01em;
        }

        .search-container {
            margin-bottom: 12px;
        }
        .search-box {
            position: relative;
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }
        .search-box input {
            border: none;
            padding: 10px 14px 10px 38px;
            font-size: 0.85rem;
            width: 100%;
            background: transparent;
            font-weight: 500;
        }
        .search-box input:focus { outline: none; }
        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .item-list {
            display: grid;
            gap: 8px;
        }
        .item-row { 
            background: var(--bg-card); 
            border-radius: var(--radius-md); 
            padding: 10px 14px;
            display: flex;
            align-items: center;
            text-decoration: none !important;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .item-row:active { 
            border-color: var(--accent);
            background: #fdfdfd;
        }
        .book-content { 
            flex: 1; 
            display: flex;
            align-items: center;
            min-width: 0;
        }
        .book-cover {
            width: 36px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--accent-soft);
            margin-right: 12px;
            flex-shrink: 0;
            border: 1px solid var(--border);
        }
        .book-details {
            min-width: 0;
        }
        .book-title { 
            font-weight: 700; 
            color: var(--text-title); 
            font-size: 0.85rem; 
            display: block;
            margin-bottom: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .book-meta {
            font-size: 0.7rem; 
            color: var(--text-muted); 
            font-weight: 500;
        }
        .btn-action { 
            color: var(--accent);
            font-size: 0.9rem;
            margin-left: 12px;
            flex-shrink: 0;
        }
    </style>
@endsection

@section('content')
    <div class="header-section">
        <a href="{{ route('mini-program.library.index', ['major' => $major, 'userId' => $userId]) }}" class="btn-back tap-active">
            <i class="fa fa-chevron-left"></i>
        </a>
        <div class="header-info">
            <h4>{{ $category }}</h4>
        </div>
    </div>

    <div class="search-container">
        <div class="search-box">
            <i class="fa fa-search"></i>
            <input type="text" id="bookSearch" placeholder="Search books...">
        </div>
    </div>

    <div class="item-list">
        @forelse($books as $book)
            @php $pdfLink = "https://www.calamuseducation.com/" . $book->pdf_file; @endphp
            <a href="{{ $pdfLink }}" target="_blank" onclick="event.preventDefault(); openInBrowser('{{ $pdfLink }}');" class="item-row book-entry tap-active" data-title="{{ strtolower($book->title) }}">
                <div class="book-content">
                    @if($book->cover_image)
                        <img src="{{ $book->cover_image }}" class="book-cover" alt="Cover">
                    @else
                        <div class="book-cover d-flex align-items-center justify-content-center">
                            <i class="fa fa-book text-muted" style="font-size: 0.7rem;"></i>
                        </div>
                    @endif
                    <div class="book-details">
                        <span class="book-title">{{ $book->title }}</span>
                        <span class="book-meta">PDF • Tap to read</span>
                    </div>
                </div>
                <div class="btn-action">
                    <i class="fa fa-arrow-circle-o-right"></i>
                </div>
            </a>
        @empty
            <div class="text-center py-5">
                <p class="text-muted small">No books found.</p>
            </div>
        @endforelse
    </div>
@endsection

@section('scripts')
<script>
    function openInBrowser(link) { 
        window.location = link; 
        AndroidInterface.openBrowser(link); 
    }

    $('#bookSearch').on('input', function() {
        const query = $(this).val().toLowerCase();
        $('.book-entry').each(function() {
            const title = $(this).data('title');
            $(this).toggle(title.includes(query));
        });
    });
</script>
@endsection
