@extends('layouts.mini-program')

@section('title', $artist->name . ' - Calamus')

@section('styles')
    <style>
        .header-artist { 
            padding: 16px 0; 
            display: flex; 
            align-items: center; 
        }
        .back-link { 
            color: var(--text-title); 
            font-size: 0.9rem; 
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            text-decoration: none !important;
            border-radius: var(--radius-sm);
            background: var(--bg-card);
            border: 1px solid var(--border);
        }
        .artist-title { font-weight: 800; color: var(--text-title); margin: 0; font-size: 1.1rem; letter-spacing: -0.01em; }
        
        .song-card-list { 
            display: grid; 
            gap: 8px; 
        }
        .song-compact { 
            background: var(--bg-card); 
            border-radius: var(--radius-md); 
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .song-compact:active { border-color: var(--accent); background: #fdfdfd; }
        .song-name { flex: 1; min-width: 0; }
        .song-name h6 { 
            margin: 0; 
            color: var(--text-title); 
            font-weight: 700; 
            font-size: 0.9rem; 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 420px) {
            .song-compact { align-items: flex-start; }
            .song-name h6 {
                white-space: normal;
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
                overflow: hidden;
                text-overflow: ellipsis;
                word-break: break-word;
                line-height: 1.25;
            }
            .vote-pill { margin-top: 2px; }
        }
        
        .vote-pill { 
            display: flex; 
            align-items: center; 
            background: var(--bg-page);
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }
        .vote-num { font-weight: 800; font-size: 0.8rem; margin-right: 6px; color: var(--text-body); }
        .vote-thumb { font-size: 0.85rem; color: var(--text-muted); }
        
        .voted { border-color: var(--accent); background: var(--accent-soft); }
        .voted .vote-pill { background: var(--accent); border-color: var(--accent); }
        .voted .vote-thumb { color: #fff; }
        .voted .vote-num { color: #fff; }

        .fab-add {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 1000;
        }

        .modal-mini { border-radius: var(--radius-lg); border: none; overflow: hidden; }
        .modal-mini .modal-header { border-bottom: none; padding: 20px 20px 10px; }
        .modal-mini .modal-title { font-weight: 800; font-size: 1rem; }
        .modal-mini .modal-footer { border-top: none; padding: 10px 20px 20px; }
        .modal-mini .form-control { 
            border-radius: var(--radius-md); 
            border: 1px solid var(--border); 
            background: var(--bg-page); 
            padding: 12px;
            font-size: 0.9rem; 
        }
        .btn-submit {
            background: var(--accent);
            border: none;
            border-radius: var(--radius-md);
            padding: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            color: #fff;
        }
    </style>
@endsection

@section('content')
    <div class="header-artist">
        <a href="{{ route('mini-program.song-request.index', ['major' => $major, 'userId' => $userId]) }}" class="back-link tap-active">
            <i class="fa fa-chevron-left"></i>
        </a>
        <h3 class="artist-title">{{ $artist->name }}</h3>
    </div>

    <div class="song-card-list">
        @forelse($artist->requestedSongs as $song)
            @php
                $voters = json_decode($song->is_voted, true) ?: [];
                $hasVoted = in_array($userId, $voters);
            @endphp
            <div class="song-compact tap-active {{ $hasVoted ? 'voted' : '' }}" onclick="vote({{ $song->id }}, this)">
                <div class="song-name">
                    <h6>{{ $song->name }}</h6>
                </div>
                <div class="vote-pill">
                    <span class="vote-num" id="count-{{ $song->id }}">{{ $song->vote }}</span>
                    <i class="fa {{ $hasVoted ? 'fa-check-circle' : 'fa-thumbs-up' }} vote-thumb"></i>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <p class="text-muted small">No songs requested yet.</p>
            </div>
        @endforelse
    </div>

    <div class="fab-add tap-active" onclick="openRequestModal()">
        <i class="fa fa-plus"></i>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mx-3">
            <div class="modal-content modal-mini">
                <div class="modal-header">
                    <h5 class="modal-title">Request Song</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body py-2">
                    <input type="text" id="songName" class="form-control" placeholder="Song title...">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-submit btn-block tap-active" onclick="submitRequest()">Send Request</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const userId = '{{ $userId }}';
    const csrfToken = '{{ csrf_token() }}';

    function vote(songId, element) {
        if ($(element).hasClass('voted')) return;
        if (!userId) return;

        $.post('{{ route("mini-program.song-request.vote") }}', {
            _token: csrfToken,
            songId: songId,
            userId: userId
        }, function(response) {
            if (response.success) {
                $(`#count-${songId}`).text(response.newVoteCount);
                $(element).addClass('voted');
                $(element).find('.vote-thumb').removeClass('fa-thumbs-up').addClass('fa-check-circle');
            }
        });
    }

    function openRequestModal() {
        if (!userId) return;
        $('#songName').val('');
        $('#requestModal').modal('show');
    }

    function submitRequest() {
        const artistId = '{{ $artist->id }}';
        const songName = $('#songName').val().trim();
        if (!songName) return;

        $.post('{{ route("mini-program.song-request.store") }}', {
            _token: csrfToken,
            artistId: artistId,
            songName: songName,
            userId: userId
        }, function(response) {
            if (response.success) {
                $('#requestModal').modal('hide');
                location.reload();
            } else {
                alert(response.message);
            }
        });
    }
</script>
@endsection
