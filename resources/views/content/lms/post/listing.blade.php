<div class="row" id="dd-with-handle">
    <ul class="list-group" id="handle-list-1" data-type='{{ $list['type'] }}'>
        @foreach($list['lvl1'] as $lvl1)
            <li class="list-group-item" id='item-{{ $lvl1->id }}'><span class="handle me-50">+</span> <a data-type="{{ $list['type'] }}"
                    href='{{ route('lms.'.$list['type'].($list['type'] === 'quizzes'?'.edit':'.show'), $lvl1->id) }}'>{{ $lvl1->title }}</a></li>
        @endforeach
    </ul>
</div>
