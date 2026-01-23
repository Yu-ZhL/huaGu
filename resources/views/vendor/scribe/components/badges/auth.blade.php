@if($authenticated)@component('scribe::components.badges.base', ['colour' => "darkred", 'text' => '需要认证'])
    @endcomponent
@endif