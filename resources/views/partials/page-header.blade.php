<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-[22px] font-bold tracking-tight text-[#17211c]">{{ $title }}</h1>
        @isset($subtitle)
            <p class="mt-1 text-sm text-[#617068]">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">{!! $actions !!}</div>
    @endisset
</div>
<div class="mb-6 h-px w-full rounded-full"
     style="background: linear-gradient(90deg,#287857 0%,rgba(40,120,87,0.15) 40%,transparent 100%);"></div>
