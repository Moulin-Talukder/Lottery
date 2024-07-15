@php
    if (isset($seoContents) && count($seoContents)) {
        $seoContents = json_decode(json_encode($seoContents, true));
        $socialImageSize = explode('x', $seoContents->image_size);
    } elseif ($seo) {
        $seoContents = $seo;
        $socialImageSize = explode('x', getFileSize('seo'));
        $seoContents->image = getImage(getFilePath('seo') . '/' . $seo->image);
    } else {
        $seoContents = null;
    }
    
@endphp

<meta Content="{{ $general->sitename(__($pageTitle)) }}" name="title">

@if ($seoContents)
    <meta content="{{ $seoContents->meta_description ?? $seoContents->description }}" name="description">
    <meta content="{{ implode(',', $seoContents->keywords) }}" name="keywords">
    <link href="{{ getImage(getFilePath('logoIcon') . '/favicon.png') }}" rel="shortcut icon" type="image/x-icon">

    {{-- <!-- Apple Stuff --> --}}
    <link href="{{ getImage(getFilePath('logoIcon') . '/logo.png') }}" rel="apple-touch-icon">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="black" name="apple-mobile-web-app-status-bar-style">
    <meta content="{{ $general->sitename($pageTitle) }}" name="apple-mobile-web-app-title">
    {{-- <!-- Google / Search Engine Tags --> --}}
    <meta content="{{ $general->sitename($pageTitle) }}" itemprop="name">
    <meta content="{{ $seoContents->description }}" itemprop="description">
    <meta content="{{ $seoContents->image }}" itemprop="image">
    {{-- <!-- Facebook Meta Tags --> --}}
    <meta content="website" property="og:type">
    <meta content="{{ $seoContents->social_title }}" property="og:title">
    <meta content="{{ $seoContents->social_description }}" property="og:description">
    <meta content="{{ $seoContents->image }}" property="og:image" />
    <meta content="{{ pathinfo($seoContents->image)['extension'] }}" property="og:image:type" />
    <meta content="{{ $socialImageSize[0] }}" property="og:image:width" />
    <meta content="{{ $socialImageSize[1] }}" property="og:image:height" />
    <meta content="{{ url()->current() }}" property="og:url">
    {{-- <!-- Twitter Meta Tags --> --}}
    <meta content="summary_large_image" name="twitter:card">
@endif
