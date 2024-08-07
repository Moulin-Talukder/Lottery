@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="section-bg py-80">
        <div class="container">
            <div class="d-flex justify-content-center">
                <div class="verification-code-wrapper">
                    <div class="verification-area">
                        <h5 class="pb-3 text-center border-bottom">@lang('Verify Mobile Number')</h5>
                        <form action="{{ route('user.verify.mobile') }}" class="submit-form" method="POST">
                            @csrf
                            <p class="verification-text mb-2">@lang('A 6 digit verification code sent to your mobile number') : +{{ showMobileNumber(auth()->user()->mobile) }}</p>
                            @include($activeTemplate . 'partials.verification_code')
                            <div class="mb-3">
                                <button class="btn btn--base btn--md w-100" type="submit">@lang('Submit')</button>
                            </div>
                            <div class="form-group">
                                <p>
                                    @lang('If you don\'t get any code'), <a class="forget-pass text--base" href="{{ route('user.send.verify.code', 'phone') }}"> @lang('Try again')</a>
                                </p>
                                @if ($errors->has('resend'))
                                    <br />
                                    <small class="text--danger">{{ $errors->first('resend') }}</small>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
