@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($videos as $video)
            <x-video-card :video="$video" />
        @endforeach
    </div>

    <div class="mt-6">
        {{ $videos->links() }}
    </div>
</div>
@endsection
