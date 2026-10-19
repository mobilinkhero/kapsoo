@php $lastDate = null; @endphp
@foreach($messages as $m)
    @php
        $day = $m->sent_at?->toDateString();
        $label = $m->sent_at
            ? ($m->sent_at->isToday() ? 'Today' : ($m->sent_at->isYesterday() ? 'Yesterday' : $m->sent_at->format('d M Y')))
            : null;
    @endphp
    @if($day && $day !== $lastDate)
        <div class="wai-date"><span>{{ $label }}</span></div>
        @php $lastDate = $day; @endphp
    @endif
    <div class="wai-row {{ $m->direction === 'outbound' ? 'out' : 'in' }}" data-mid="{{ $m->id }}">
        <div class="wai-bubble {{ $m->direction === 'outbound' ? 'out' : 'in' }}">
            @if($m->type === 'image' && $m->media_url)
                <div class="wai-media-wrap">
                    <img class="media" src="{{ $m->media_url }}" alt="" onclick="openPreview('{{ $m->media_url }}','image')" style="cursor:zoom-in;">
                    <a class="wai-dl" href="/media/download?url={{ urlencode($m->media_url) }}" download title="Download">⬇</a>
                </div>
            @endif
            @if($m->type === 'video' && $m->media_url)
                <div class="wai-media-wrap">
                    <video src="{{ $m->media_url }}" controls onclick="openPreview('{{ $m->media_url }}','video')" style="cursor:zoom-in;"></video>
                    <a class="wai-dl" href="/media/download?url={{ urlencode($m->media_url) }}" download title="Download">⬇</a>
                </div>
            @endif
            @if($m->type === 'audio' && $m->media_url)
                <div class="wai-voice">
                    <span class="wai-voice-ic">🎤</span>
                    <audio src="{{ $m->media_url }}" controls></audio>
                    <a class="wai-dl-inline" href="/media/download?url={{ urlencode($m->media_url) }}" download title="Download">⬇</a>
                </div>
            @endif
            @if($m->type === 'sticker' && $m->media_url)
                <div class="wai-media-wrap">
                    <img class="wai-sticker" src="{{ $m->media_url }}" alt="" onclick="openPreview('{{ $m->media_url }}','image')" style="cursor:zoom-in;">
                    <a class="wai-dl" href="/media/download?url={{ urlencode($m->media_url) }}" download title="Download">⬇</a>
                </div>
            @endif
            @if($m->type === 'document' && $m->media_url)
                @if(str_ends_with(strtolower($m->media_url), '.pdf'))
                    <div class="wai-media-wrap">
                        <iframe src="{{ $m->media_url }}" class="wai-pdf" loading="lazy"></iframe>
                        <a class="wai-dl" href="/media/download?url={{ urlencode($m->media_url) }}" download title="Download">⬇</a>
                    </div>
                @else
                    <a class="wai-file" href="/media/download?url={{ urlencode($m->media_url) }}" download>📄 Download file</a>
                @endif
            @endif
            @if($m->type === 'location')
                <a class="wai-loc" href="https://maps.google.com/?q={{ urlencode($m->body ?? '') }}" target="_blank">📍 View on map</a>
            @endif
            @php $hideBody = in_array($m->type, ['image','video','audio','sticker','document']) && is_string($m->body) && (str_contains($m->body, 'http') || str_contains(strtolower($m->body), 'attached')); @endphp
            @if($m->body && $m->type !== 'location' && ! $hideBody)
                <div class="cap">{{ $m->body }}</div>
            @endif
            <div class="wai-meta">
                <span>{{ $m->sent_at?->format('H:i') }}</span>
                @if($m->direction === 'outbound')
                    <span class="tick">{{ in_array($m->status, ['read', 'delivered']) ? '✓✓' : '✓' }}</span>
                @endif
            </div>
            @if($m->direction === 'inbound' && !empty($m->ai_suggested_reply))
                <div class="wai-feedback" data-mid="{{ $m->id }}" style="margin-top:4px;font-size:11px;color:#667781;">
                    AI suggestion: {{ \Illuminate\Support\Str::limit($m->ai_suggested_reply, 60) }}
                    <button type="button" class="fb-up" data-rating="1" style="border:0;background:transparent;cursor:pointer;">👍</button>
                    <button type="button" class="fb-down" data-rating="-1" style="border:0;background:transparent;cursor:pointer;">👎</button>
                    <span class="fb-status" style="margin-left:4px;"></span>
                </div>
            @endif
        </div>
    </div>
@endforeach
