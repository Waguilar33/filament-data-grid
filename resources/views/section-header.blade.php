@php
    $headingTag ??= 'h3';
@endphp

<header class="fi-section-header">
    {{ \Filament\Support\generate_icon_html($icon, attributes: (new \Filament\Support\View\ComponentAttributeBag)->color(\Filament\Support\View\Components\SectionComponent\IconComponent::class, 'gray'), size: \Filament\Support\Enums\IconSize::Large) }}

    @if (filled($heading) || filled($description))
        <div class="fi-section-header-content fi-section-header-content-has-heading">
            <div class="fi-section-header-text-ctn">
                @if (filled($heading))
                    <{{ $headingTag }} class="fi-section-header-heading">{{ $heading }}</{{ $headingTag }}>
                @endif

                @if (filled($description))
                    <p class="fi-section-header-description">{{ $description }}</p>
                @endif
            </div>
        </div>
    @endif
</header>
