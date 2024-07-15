@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="py-80 section-bg">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-7 col-xl-6">
                    <div class="card custom--card">
                        <div class="card-header">
                            <h6 class="card-title">
                                @lang('Complete your profile by providing the data below')
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('user.data.submit') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang('First Name')</label>
                                            <input class="form-control form--control" name="firstname" required type="text" value="{{ old('firstname') }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang('Last Name')</label>
                                            <input class="form-control form--control" name="lastname" required type="text" value="{{ old('lastname') }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang('Address')</label>
                                            <input class="form-control form--control" name="address" type="text" value="{{ old('address') }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang('State')</label>
                                            <input class="form-control form--control" name="state" type="text" value="{{ old('state') }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang('Zip Code')</label>
                                            <input class="form-control form--control" name="zip" type="text" value="{{ old('zip') }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label">@lang('City')</label>
                                            <input class="form-control form--control" name="city" type="text" value="{{ old('city') }}">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn--base btn--md w-100" type="submit">
                                            @lang('Submit')
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
