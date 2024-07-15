<div class="request-coin @if(!request()->routeIs('home')) bg--base @endif">
    <div class="request-coin__icon">
        <img src="{{ asset('assets/money.png') }}" class="image-black">
        <img src="{{ asset('assets/money_white.png') }}" class="image-white">
    </div>
    <h5 class="request-coin__title">
        @lang("REQUEST FOR $general->cur_text")
    </h5>
</div>

<div class="offcanvas offcanvas-end section-bg" tabindex="-1" id="request-canvas">
    <div class="offcanvas-header bg--base text-white p-5 py-4">
      <h5 class="offcanvas-title">@lang("Requesting $general->cur_text")</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-5 @guest d-flex align-items-center  justify-content-center @endguest">
        @auth
        <form method="POST" action="{{ route('request.coin') }}">
            @csrf
            <div class="form-group">
               @php echo $general->request_instruction; @endphp
            </div>
            <div class="form-group">
                <label for="">@lang('Amount')</label>
                <div class="input-group">
                    <input type="number" step="any" name="amount" type="text" class="form--control form-control ps-2" required/>
                    <span class="input-group-text">{{ __($general->cur_text) }}</span>
                </div>
            </div>
            <button class="btn btn--base w-100">@lang('Submit')</button>
        </form>
        @else
        <div class="text-center">
            <img src="{{ asset('assets/shopping.png') }}" alt="">
            <div>
                <a href="{{ route('user.login') }}" class="text--base">@lang('Authentication')</a>
                <span>@lang("is a prerequisite for accessing the $general->cur_text request")</span>
            </div>
        </div>
        @endauth
    </div>
</div>

@push('script')
    <script>
        "use strict";
        (function ($) {
            $('.request-coin').on('click',function(e){
                var bsOffcanvas = new bootstrap.Offcanvas(document.getElementById('request-canvas'))
                bsOffcanvas.show()
            });

            @if(request()->routeIs('home'))
                $(window).on('scroll',function(e){
                    windowScroll();
                });
            @endif

            function windowScroll(){
                let scroll = window.scrollY;
                if(scroll > 310){
                    $('.request-coin').addClass('bg--base');
                }else{
                    $('.request-coin').removeClass('bg--base');
                }
            }

            @if(request()->routeIs('home'))
                windowScroll();
            @endif

        })(jQuery);
    </script>
@endpush

