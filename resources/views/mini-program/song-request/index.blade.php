@extends('layouts.mini-program')

@section('title', 'Artists - Calamus')

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

        .search-container { 
            margin-bottom: 12px;
        }
        .search-box {
            position: relative;
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }
        .search-input { 
            border: none;
            padding: 10px 14px 10px 38px;
            font-size: 0.85rem;
            width: 100%;
            background: transparent;
            font-weight: 500;
        }
        .search-input:focus { outline: none; }
        .search-icon { 
            position: absolute; 
            left: 14px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: var(--accent); 
            font-size: 0.85rem; 
        }

        .artist-list { 
            display: grid; 
            gap: 8px; 
        }
        .artist-item { 
            background: var(--bg-card); 
            border-radius: var(--radius-md); 
            padding: 10px 14px;
            display: flex;
            align-items: center;
            text-decoration: none !important;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .artist-item:active { 
            border-color: var(--accent);
            background: #fdfdfd;
        }
        
        .artist-avatar {
            width: 38px;
            height: 38px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1rem;
            font-weight: 800;
            flex-shrink: 0;
        }
        
        .artist-info { flex: 1; min-width: 0; }
        .artist-info h6 { 
            margin: 0; 
            color: var(--text-title); 
            font-weight: 700; 
            font-size: 0.9rem; 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .artist-info p { 
            margin: 0; 
            font-size: 0.7rem; 
            color: var(--text-muted); 
            font-weight: 500;
        }
        .artist-icon { 
            color: var(--text-muted); 
            font-size: 0.75rem; 
            opacity: 0.4;
        }
    </style>
@endsection

@section('content')
    <div class="header-section text-center">
        <h4>Music Corner</h4>
        <p>Request songs from artists</p>
    </div>

    <div class="search-container">
        <div class="search-box">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="artistSearch" class="search-input" placeholder="Search artists...">
        </div>
    </div>

    <div class="artist-list">
        @foreach($artists as $artist)
            <a href="{{ route('mini-program.song-request.artist', ['id' => $artist->id, 'major' => $major, 'userId' => $userId]) }}" class="artist-item artist-entry tap-active" data-name="{{ strtolower($artist->name) }}">
                <div class="artist-avatar">
                    {{ substr($artist->name, 0, 1) }}
                </div>
                <div class="artist-info">
                    <h6>{{ $artist->name }}</h6>
                    <p>Tap to request songs</p>
                </div>
                <div class="artist-icon">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </a>
        @endforeach
    </div>
@endsection

@section('scripts')
<script>
    $('#artistSearch').on('input', function() {
        const query = $(this).val().toLowerCase();
        $('.artist-entry').each(function() {
            const name = $(this).data('name');
            $(this).toggle(name.includes(query));
        });
    });
</script>
@endsection
