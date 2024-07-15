@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="blog-section py-80">
        <div class="container">
            @include($activeTemplate . 'partials.blog_cards', ['blogs' => $blogs])

            @if ($blogs->hasPages())
                <div class="paginate-links">
                    {{ paginateLinks($blogs) }}
                </div>
            @endif
        </div>
    </div>
    @if ($sections->secs != null)
        @foreach (json_decode($sections->secs) as $sec)
            @include($activeTemplate . 'sections.' . $sec)
        @endforeach
    @endif
@endsection
