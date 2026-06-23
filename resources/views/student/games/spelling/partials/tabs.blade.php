{{-- Sub-nav inside the Spelling Meow game: Play / My Progress. ($active = play|progress) --}}
@php($active = $active ?? 'play')
<div class="mb-6 flex justify-center">
    <div class="inline-flex rounded-xl bg-gray-100 p-1">
        <a href="{{ route('student.games.spelling.play') }}"
           @class([
               'rounded-lg px-4 py-1.5 text-sm font-bold transition-colors duration-200 cursor-pointer',
               'bg-white text-primary-dark shadow-sm' => $active === 'play',
               'text-gray-500 hover:text-primary-dark' => $active !== 'play',
           ])>Play</a>
        <a href="{{ route('student.games.spelling.progress') }}"
           @class([
               'rounded-lg px-4 py-1.5 text-sm font-bold transition-colors duration-200 cursor-pointer',
               'bg-white text-primary-dark shadow-sm' => $active === 'progress',
               'text-gray-500 hover:text-primary-dark' => $active !== 'progress',
           ])>My Progress</a>
    </div>
</div>
