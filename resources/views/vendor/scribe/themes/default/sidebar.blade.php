@php
    use Knuckles\Scribe\Tools\Utils as u;
@endphp
<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{!! $assetPathPrefix !!}images/navbar.png" alt="navbar-image" />
    </span>
</a>
<div class="tocify-wrapper">
    @if($metadata['logo'] != false)
        <img src="{{ $metadata['logo'] }}" alt="logo" class="logo" style="padding-top: 10px; max-width: 50%; display: block; margin: 0 auto;" />
    @endif

    @isset($metadata['example_languages'])
        <div class="lang-selector">
            @foreach($metadata['example_languages'] as $name => $lang)
                @php if (is_numeric($name))
                $name = $lang; @endphp
                <button type="button" class="lang-button" data-language-name="{{ $lang }}">{{ $name }}</button>
            @endforeach
        </div>
    @endisset

    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="{{ u::trans("scribe::labels.search") }}">
    </div>

    <div id="toc">
        @foreach($headings as $h1)
            @php
                $h1Id = md5($h1['name']);
                if ($h1['name'] === '系统简介') {
                    $h1Id = 'introduction';
                } elseif ($h1['name'] === '用户认证指南') {
                    $h1Id = 'authentication';
                }
            @endphp
            <ul id="tocify-header-{{ $h1Id }}" class="tocify-header">
                <li class="tocify-item level-1" data-unique="{!! $h1Id !!}">
                    <a href="#{!! $h1Id !!}">{!! $h1['name'] !!}</a>
                </li>
                @if(count($h1['subheadings']) > 0)
                    <ul id="tocify-subheader-{!! $h1Id !!}" class="tocify-subheader">
                        @foreach($h1['subheadings'] as $h2)
                            <li class="tocify-item level-2" data-unique="{!! md5($h2['name']) !!}">
                                <a href="#{!! md5($h2['name']) !!}">{!! $h2['name'] !!}</a>
                            </li>
                            @if(count($h2['subheadings']) > 0)
                                <ul id="tocify-subheader-{!! md5($h2['name']) !!}" class="tocify-subheader">
                                    @foreach($h2['subheadings'] as $h3)
                                        <li class="tocify-item level-3" data-unique="{!! md5($h3['name']) !!}">
                                            <a href="#{!! md5($h3['name']) !!}">{!! $h3['name'] !!}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </ul>
        @endforeach
    </div>

    <ul class="toc-footer" id="toc-footer">
        @if($metadata['postman_collection_url'])
            <li style="padding-bottom: 5px;"><a
                    href="{!! $metadata['postman_collection_url'] !!}">{!! u::trans("scribe::links.postman") !!}</a></li>
        @endif
        @if($metadata['openapi_spec_url'])
            <li style="padding-bottom: 5px;"><a
                    href="{!! $metadata['openapi_spec_url'] !!}">{!! u::trans("scribe::links.openapi") !!}</a></li>
        @endif
        <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>{{ $metadata['last_updated'] }}</li>
    </ul>
</div>