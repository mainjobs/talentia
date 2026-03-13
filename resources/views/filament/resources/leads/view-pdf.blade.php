@php
    $url = asset('storage/' . $getState());
@endphp

<iframe
    src="{{ $url }}#toolbar=1"
    style="
        width:100%;
        height:80vh;
        border:none;
        border-radius:8px;
        display:block;
    "
></iframe>