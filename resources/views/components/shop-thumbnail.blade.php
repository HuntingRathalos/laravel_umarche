<div class="">
  @if (empty($filename))
      <img src="{{ asset('images/no_image.jpg') }}" alt="">
   @else
      <img src="{{ asset('storage/shops/'.$filename) }}" alt="">
  @endif
</div>
