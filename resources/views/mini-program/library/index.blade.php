@extends('layouts.mini-program')

@section('title', ucfirst($major) . ' Library - Calamus')

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
        
        .item-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .item-row { 
            background: var(--bg-card); 
            border-radius: var(--radius-md); 
            padding: 14px 16px;
            display: flex;
            align-items: center;
            text-decoration: none !important;
            border: 1px solid var(--border);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .item-row:active { 
            background: #fdfdfd;
            border-color: var(--accent);
            transform: scale(0.98);
        }
        .category-icon {
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
        .item-info { flex: 1; min-width: 0; }
        .item-name { 
            font-weight: 700; 
            color: var(--text-title); 
            font-size: 0.95rem; 
            display: block;
            margin-bottom: 1px;
        }
        .item-meta { 
            font-size: 0.75rem; 
            color: var(--text-muted); 
            font-weight: 500;
        }
        .btn-go { 
            color: var(--text-muted);
            font-size: 0.8rem;
            opacity: 0.5;
        }
    </style>
@endsection

@section('content')
    <div class="header-section text-center">
        <h4>{{ ucfirst($major) }} Library</h4>
        <p>Curated learning materials</p>
    </div>

    <div class="item-list">
        @forelse($categories as $category)
            <a href="{{ route('mini-program.library.category', ['major' => $major, 'category' => $category, 'userId' => $userId]) }}" class="item-row tap-active">
                <div class="category-icon">
                    <i class="fa fa-folder-open"></i>
                </div>
                <div class="item-info">
                    <span class="item-name">{{ $category }}</span>
                    <span class="item-meta">Browse collection</span>
                </div>
                <div class="btn-go">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </a>
        @empty
            <div class="text-center py-5">
                <p class="text-muted small">No categories found.</p>
            </div>
        @endforelse
    </div>
@endsection
