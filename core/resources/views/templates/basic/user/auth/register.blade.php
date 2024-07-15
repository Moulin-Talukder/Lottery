@extends($activeTemplate . 'layouts.app')
@section('panel')
    @php
        $policyPages = getContent('policy_pages.element', false, null, true);
        $content = getContent('register.content', true);
    @endphp
    <section class="account">
        <div class="account-inner">
            <div class="account-left">
                <div class="account-left__shape">
                    <img alt="" src="{{ getImage($activeTemplateTrue . 'images/shapes/login-1.png') }}">
                </div>
                <div class="account-left__content">
                    <h1 class="account-left__title">{{ __(@$content->data_values->heading) }}</h1>
                    <p class="account-left__desc">
                        <span class="account-left__icon"> <i class="fas fa-quote-left"></i> </span>
                        {{ __(@$content->data_values->subheading) }}
                        <span class="account-left__icon"> <i class="fas fa-quote-right"></i> </span>
                    </p>
                </div>
                <div class="account-left__thumb">
                    <img alt="" src="{{ getImage('assets/images/frontend/register/' . @$content->data_values->image, '560x420') }}">
                </div>
            </div>
            <div class="account-right-wrapper">
                <a class="account-right-wrapper__logo" href="{{ route('home') }}">
                    <img alt="" src="{{ getImage(getFilePath('logoIcon') . '/logo_dark.png') }}">
                </a>
                <div class="account-right signup">
                    <div class="account-content">
                        <div class="account-form">
                            <h2 class="account-form__title">{{ __(@$content->data_values->form_title) }}</h2>
                            <p class="account-form__desc">{{ __(@$content->data_values->form_subtitle) }}</p>
                            <form action="{{ route('user.register') }}" class="verify-gcaptcha" method="post">
                                @csrf
                                <div class="row">
                                    @if (session()->get('reference'))
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form--label">@lang('Referred By')</label>
                                                <input class="form--control" disabled type="text" value="{{ session()->get('reference') }}">
                                            </div>
                                        </div>
                                    @endif

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form--label">@lang('Username')</label>
                                            <input class="form--control checkUser" name="username" required type="text" value="{{ old('username') }}">
                                            <small class="text--danger usernameExist"></small>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form--label">@lang('E-Mail Address')</label>
                                            <input class="form--control checkUser" name="email" required type="email" value="{{ old('email') }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form--label">@lang('Country')</label>
                                            <div class="form--select">
                                                <select class="form--control" name="country">
                                                    @foreach ($countries as $key => $country)
                                                        <option data-code="{{ $key }}" data-mobile_code="{{ $country->dial_code }}" value="{{ $country->country }}">{{ __($country->country) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form--label">@lang('Mobile')</label>
                                            <div class="input-group">
                                                <span class="input-group-text mobile-code"></span>
                                                <input name="mobile_code" type="hidden">
                                                <input name="country_code" type="hidden">
                                                <input class="form-control form--control checkUser" name="mobile" required type="number" value="{{ old('mobile') }}">
                                            </div>
                                            <small class="text--danger mobileExist"></small>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">

                                            <label class="form--label">@lang('Password')</label>
                                            <div class="position-relative">
                                                <input class="form-control form--control" name="password" required type="password">
                                                <div class="password-show-hide far fa-eye toggle-password fa-eye-slash" id="#password"></div>
                                            </div>
                                            @if ($general->secure_password)
                                                <div class="input-popup">
                                                    <p class="error lower">@lang('1 small letter minimum')</p>
                                                    <p class="error capital">@lang('1 capital letter minimum')</p>
                                                    <p class="error number">@lang('1 number minimum')</p>
                                                    <p class="error special">@lang('1 special character minimum')</p>
                                                    <p class="error minimum">@lang('6 character password')</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form--label">@lang('Confirm Password')</label>
                                            <div class="position-relative">
                                                <input class="form-control form--control" id="password_confirmation" name="password_confirmation" required type="password">
                                                <div class="password-show-hide fas fa-eye toggle-password fa-eye-slash" id="#password_confirmation"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <x-captcha :path="$activeTemplate . 'partials'" />

                                    @if($general->agree)
                                    <div class="form-group col-12">
                                        <input type="checkbox" id="agree" @checked(old('agree')) name="agree" required>
                                        <label for="agree">@lang('I agree with')</label> <span>@foreach($policyPages as $policy) <a class="text--base" href="{{ route('policy.pages',[slug($policy->data_values->title),$policy->id]) }}" target="_blank">{{ __($policy->data_values->title) }}</a> @if(!$loop->last), @endif @endforeach</span>
                                    </div>
                                    @endif
                                    <div class="col-sm-12 form-group">
                                        <button class="btn btn--base w-100" type="submit">@lang('Register')</button>
                                    </div>
                                    @php
                                        $credentials = $general->socialite_credentials;
                                    @endphp
                                    @if ($credentials->google->status == Status::ENABLE || $credentials->facebook->status == Status::ENABLE || $credentials->linkedin->status == Status::ENABLE)
                                        @if ($credentials->google->status == Status::ENABLE)
                                            <div class="col-sm-4 form-group">
                                                <div class="continue-google">
                                                    <a class="btn w-100" href="{{ route('user.social.login', 'google') }}">
                                                        <span class="google-icon"> <img alt="" src="{{ getImage($activeTemplateTrue . 'images/icon/google.png') }}"></span>
                                                        @lang('Login with Google')
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($credentials->facebook->status == Status::ENABLE)
                                            <div class="col-sm-4 form-group">
                                                <div class="continue-google">
                                                    <a class="btn w-100" href="{{ route('user.social.login', 'facebook') }}">
                                                        <span class="google-icon"><img alt="" src="{{ getImage($activeTemplateTrue . 'images/icon/facebook.png') }}"></span>
                                                        @lang('Login with Facebook')
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($credentials->linkedin->status == Status::ENABLE)
                                            <div class="col-sm-4 form-group">
                                                <div class="continue-google">
                                                    <a class="btn w-100" href="{{ route('user.social.login', 'linkedin') }}">
                                                        <span class="google-icon"><img alt="" src="{{ getImage($activeTemplateTrue . 'images/icon/linkedin.png') }}"></span>
                                                        @lang('Login with Linkedin')
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                    <p class="account-form__text"> @lang('Already have an account?') <a class="account-form__text-link" href="{{ route('user.login') }}">@lang('Log In')</a> </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="row mt-auto">
                    <div class="col-md-6">
                        <div class="account-form__footer">{{ __(@$content->data_values->footer_text) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div aria-hidden="true" aria-labelledby="existModalCenterTitle" class="modal custom--modal fade" id="existModalCenter" role="dialog" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="existModalLongTitle">@lang('You are with us')</h5>
                    <span aria-label="Close" class="close" data-bs-dismiss="modal" type="button">
                        <i class="las la-times"></i>
                    </span>
                </div>
                <div class="modal-body">
                    <h6 class="text-center">@lang('You already have an account please Login ')</h6>
                </div>
                <div class="modal-footer">
                    <button class="btn btn--dark btn--sm" data-bs-dismiss="modal" type="button">@lang('Close')</button>
                    <a class="btn btn--base btn--sm" href="{{ route('user.login') }}">@lang('Login')</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@if ($general->secure_password)
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif
@push('script')
    <script>
        "use strict";
        (function($) {
            @if ($mobileCode)
                $(`option[data-code={{ $mobileCode }}]`).attr('selected', '');
            @endif

            $('select[name=country]').change(function() {
                $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
            });
            $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
            $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
            $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));

            $('.checkUser').on('focusout', function(e) {
                var url = '{{ route('user.checkUser') }}';
                var value = $(this).val();
                var token = '{{ csrf_token() }}';
                if ($(this).attr('name') == 'mobile') {
                    var mobile = `${$('.mobile-code').text().substr(1)}${value}`;
                    var data = {
                        mobile: mobile,
                        _token: token
                    }
                }
                if ($(this).attr('name') == 'email') {
                    var data = {
                        email: value,
                        _token: token
                    }
                }
                if ($(this).attr('name') == 'username') {
                    var data = {
                        username: value,
                        _token: token
                    }
                }
                $.post(url, data, function(response) {
                    if (response.data != false && response.type == 'email') {
                        $('#existModalCenter').modal('show');
                    } else if (response.data != false) {
                        $(`.${response.type}Exist`).text(`${response.type} already exist`);
                    } else {
                        $(`.${response.type}Exist`).text('');
                    }
                });
            });
        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        .account-form {
            max-width: 100%;
        }

        .continue-google a {
            font-size: 0.875rem !important;
        }
    </style>
@endpush
