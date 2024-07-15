@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-inner user-dashboard">
        <div class="row @if (!$user->ts || $user->kv == Status::KYC_PENDING || $user->kv == Status::KYC_PENDING) mt-4 @endif g-3">
            <div class="col-xxl-9 col-xl-8 col-lg-6">
                <div class="row gy-4 mb-4">
                    <div class="col-xxl-3 col-sm-6">
                        <div class="dashbaord-widget">
                            <div class="shape">
                                <i class="las la-shopping-cart"></i>
                            </div>
                            <div class="dashbaord-widget__header">
                                <a href="{{ route('user.lottery.purchase.history') }}" class="title">@lang('Purchase Ticket')</a>
                            </div>
                            <div class="dashbaord-widget__amount">
                                <h4>{{ getAmount($widget['total_purchase']) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="dashbaord-widget">
                            <div class="shape">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="dashbaord-widget__header">
                                <a  href="{{route('user.lottery.winning.history')}}" class="title">@lang('Total Win')</a>
                            </div>
                            <div class="dashbaord-widget__amount">
                                <h4>{{ getAmount($widget['total_win']) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="dashbaord-widget">
                            <div class="shape">
                                <i class="fa fa-spinner"></i>
                            </div>
                            <div class="dashbaord-widget__header">
                                <a href="{{ route('user.lottery.draw.pending') }}"  class="title">@lang('Pending Draw')</a>
                            </div>
                            <div class="dashbaord-widget__amount">
                                <h4>{{ getAmount($widget['pending_draw']) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="dashbaord-widget">
                            <div class="shape">
                                <i class="fas fa-bars"></i>
                            </div>
                            <div class="dashbaord-widget__header">
                                <a class="title">@lang('Total Draw')</a>
                            </div>
                            <div class="dashbaord-widget__amount">
                                <h4>{{ getAmount($widget['total_draw']) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row gy-4 mb-4">
                    <div class="col-xxl-3 col-sm-6">
                        <div class="dashbaord-widget">
                            <div class="shape">
                                <i class="las la-comment-dollar"></i>
                            </div>
                            <div class="dashbaord-widget__header">
                                <a href="{{ route('user.deposit.history') }}" class="title">@lang('Purchase') {{coinName()}}</a>
                            </div>
                            <div class="dashbaord-widget__amount">
                                <h4>{{ getAmount($widget['deposited']) }} {{ __($general->cur_text)}}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="dashbaord-widget">
                            <div class="shape">
                                <i class="las la-comments-dollar"></i>
                            </div>
                            <div class="dashbaord-widget__header">
                                <a  href="{{ route('user.withdraw.history') }}" class="title">@lang('Total Withdraw')</a>
                            </div>
                            <div class="dashbaord-widget__amount">
                                <h4>{{ getAmount($widget['withdrawn'] ) }} {{ __($general->cur_text)}}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="dashbaord-widget">
                            <div class="shape">
                                <i class="las la-ticket-alt"></i>
                            </div>
                            <div class="dashbaord-widget__header">
                                <a href="{{ route('ticket.index') }}"  class="title">@lang('Total Support Ticket')</a>
                            </div>
                            <div class="dashbaord-widget__amount">
                                <h4>{{ getAmount($widget['ticket']) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <div class="dashbaord-widget">
                            <div class="shape">
                                <i class="las la-arrows-alt-h"></i>
                            </div>
                            <div class="dashbaord-widget__header">
                                <a class="title" href="{{route('user.transactions')}}">@lang('Total Transaction')</a>
                            </div>
                            <div class="dashbaord-widget__amount">
                                <h4>{{ getAmount($widget['transaction']) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                @include($activeTemplate . 'partials.winning_table', [
                    'winners' => $winners,
                    'pagination' => false,
                ])
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6">
                <div class="dashboard-right-widget h-100">
                    <div class="text-center mb-2">
                        <h2>{{getAmount($widget['total_referrals']) }}</h2>
                        <p>@lang('Direct Referrals')</p>
                    </div>
                    <div class="mt-3">
                        <p class="fs--16px referrr-title">@lang('Latest Referrals')</p>
                        <ul class="list-group list-group-flush">
                            @if ($latestReferrals ->count())
                                <li class="list-group-item d-flex justify-content-between flex-wrap px-0">
                                    <span>@lang('User')</span>
                                    <span>@lang('Join At')</span>
                                </li>
                                @foreach ($latestReferrals as $latestReferral)
                                <li class="list-group-item d-flex justify-content-between flex-wrap px-0">
                                    <span>{{ __($latestReferral->fullname) }}</span>
                                    <span>{{ showDateTime($latestReferral->created_at) }}</span>
                                </li>
                                @endforeach
                                @else
                                <li class="list-group-flush mt-3">
                                    <h4>@lang('No referrals users found')</h4>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .dashboard-container {
            max-width: unset !important;
        }
        .referrr-title {
            position: relative;
        }
        .referrr-title:after {
            position: absolute;
            content: "";
            left: 0;
            top: 1.563rem;
            background: hsl(var(--base));
            width: 3.938rem;
            height: 0.25rem;
            border-radius: 0.625rem;
        }
    </style>
@endpush
