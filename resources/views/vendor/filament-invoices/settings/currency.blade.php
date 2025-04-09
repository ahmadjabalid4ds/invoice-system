@if ($symbol == 'sar')
   <span style="margin-left: 0.5rem;
margin-right: 0.5rem;"> @include('sar') </span>
@else
<small class="text-md font-normal">  {{ $symbol }} </small>
@endif
