<div class="fi-in-entry-wrp overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-sm">
    @php
        $url = \Illuminate\Support\Facades\Storage::url($getState());
    @endphp
    
    <iframe 
        src="{{ $url }}" 
        class="w-full h-[800px] rounded-lg"
        style="border: none;"
        loading="lazy">
    </iframe>
</div>