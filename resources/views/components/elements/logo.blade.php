<a href="{{ url('/'.app()->getLocale()) }}" style="height:{{ $height ?? '30' }}px; width:auto; display:block" aria-label="{{ config('app.name') }} Logo" class="group/logo active:scale-95">
    @if($isImage)
        <img src="{{ url($imageSrc) }}" style="height:100%; width:auto" alt="" />
    @else
        {!! str_replace('<svg', '<svg style="height:100%; width:auto" class="logo--header group-hover/logo:rotate-5 group-hover/logo:scale-120 transition-transform duration-300 ease-in-out"', $svgString) !!}
    @endif
</a>
