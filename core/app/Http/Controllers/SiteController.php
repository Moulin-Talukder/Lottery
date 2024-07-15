<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\CoinRequest;
use App\Models\Frontend;
use App\Models\Language;
use App\Models\Lottery;
use App\Models\Page;
use App\Models\Phase;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class SiteController extends Controller
{
    public function index()
    {
        $reference = @$_GET['reference'];

        if ($reference) {
            session()->put('reference', $reference);
        }

        $pageTitle = 'Home';
        $sections  = Page::where('tempname', $this->activeTemplate)->where('slug', '/')->first();
        return view($this->activeTemplate . 'home', compact('pageTitle', 'sections'));
    }

    public function lotteryTickets()
    {
        $pageTitle = 'Lottery Tickets';
        $lotteries = Lottery::active()->whereHas('winningSettings')->whereHas('phases', function ($query) {
            $query->active()->whereDate('draw_date', '>=', now())->where('is_set_winner', Status::NO);
        })->with('winningSettings', 'activePhase')->paginate(getPaginate());

        $sections  = Page::where('tempname', $this->activeTemplate)->where('slug', '/lottery-tickets')->first();

        return view($this->activeTemplate . 'lottery.list', compact('pageTitle', 'lotteries', 'sections'));
    }

    public function playLottery($slug, $id)
    {
        $lottery = Lottery::with(['activePhase', 'multiDrawOptions' => function ($options) {
            $options->active();
        }])->active()->whereHas('activePhase')->findOrFail($id);

        $pageTitle = 'Play ' . $lottery->name;
        return view($this->activeTemplate . 'lottery.play', compact('pageTitle', 'lottery'));
    }

    public function getSingleTicket(Request $request)
    {
        $lottery = Lottery::where('id', $request->lottery_id)->first();
        if (!$lottery) {
            return response()->json([
                'status' => false,
                'message' => 'Lottery not found'
            ]);
        }

        if ($lottery->ball_start_from) {
            $normalBallLimit = $lottery->no_of_ball + 1;
        } else {
            $normalBallLimit = $lottery->no_of_ball;
        }

        if ($lottery->pw_ball_start_from) {
            $pwBallLimit = $lottery->no_of_pw_ball + 1;
        } else {
            $pwBallLimit = $lottery->no_of_pw_ball;
        }

        $html = '';
        for ($i = 1; $i <= $request->difference; $i++) {
            $lotteryNumber = $request->last_ticket + $i;
            $html .= view($this->activeTemplate . 'lottery.single_ticket', compact('lottery', 'lotteryNumber', 'normalBallLimit', 'pwBallLimit'))->render();
        }

        return response()->json([
            'status' => true,
            'message' => 'success',
            'html' => $html
        ]);
    }

    public function pages($slug)
    {
        $page = Page::where('tempname', $this->activeTemplate)->where('slug', $slug)->firstOrFail();
        $pageTitle = $page->name;
        $sections = $page->secs;
        return view($this->activeTemplate . 'pages', compact('pageTitle', 'sections'));
    }

    public function faqs()
    {
        $pageTitle = 'FAQ';
        $sections = Page::where('tempname', $this->activeTemplate)->where('slug', 'faqs')->first();
        return view($this->activeTemplate . 'faqs', compact('pageTitle', 'sections'));
    }

    public function results()
    {
        $pageTitle = 'Lottery Results';
        $results   = Phase::with('lottery:id,name,image,price', 'lottery.winningSettings')->completed()->orderBy('draw_at', 'desc')->paginate(getPaginate());
        $sections  = Page::where('tempname', $this->activeTemplate)->where('slug', 'results')->first();
        return view($this->activeTemplate . 'results', compact('pageTitle', 'sections', 'results'));
    }

    public function contact()
    {
        $pageTitle = "Contact Us";
        $sections = Page::where('tempname', $this->activeTemplate)->where('slug', 'contact')->first();
        return view($this->activeTemplate . 'contact', compact('pageTitle', 'sections'));
    }


    public function contactSubmit(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        $request->session()->regenerateToken();

        $random = getNumber();

        $ticket = new SupportTicket();
        $ticket->user_id = auth()->id() ?? 0;
        $ticket->name = $request->name;
        $ticket->email = $request->email;
        $ticket->priority = Status::PRIORITY_MEDIUM;


        $ticket->ticket = $random;
        $ticket->subject = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = Status::TICKET_OPEN;
        $ticket->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = auth()->user() ? auth()->user()->id : 0;
        $adminNotification->title = 'A new support ticket has opened ';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message = $request->message;
        $message->save();

        $notify[] = ['success', 'Ticket created successfully!'];

        return to_route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function policyPages($slug, $id)
    {
        $policy = Frontend::where('id', $id)->where('data_keys', 'policy_pages.element')->firstOrFail();
        $pageTitle = $policy->data_values->title;
        return view($this->activeTemplate . 'policy', compact('policy', 'pageTitle'));
    }

    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        if (!$language) $lang = 'en';
        session()->put('lang', $lang);
        return back();
    }

    public function blogs()
    {
        $pageTitle = 'Blogs';
        $sections  = Page::where('tempname', $this->activeTemplate)->where('slug', 'blogs')->first();
        $blogs     = Frontend::where('data_keys', 'blog.element')->paginate(getPaginate(15));
        return view($this->activeTemplate . 'blogs', compact('pageTitle', 'sections', 'blogs'));
    }

    public function blogDetails($slug, $id)
    {
        $blog = Frontend::where('id', $id)->where('data_keys', 'blog.element')->firstOrFail();
        $latestBlogs = Frontend::where('id', '!=', $id)->where('data_keys', 'blog.element')->orderBy('id', 'desc')->limit(6)->get();
        $pageTitle = "Blog Details";

        $seoContents['keywords']           = $blog->meta_keywords ?? [];
        $seoContents['social_title']       = $blog->data_values->title;
        $seoContents['description']        = strip_tags($blog->data_values->description);
        $seoContents['social_description'] = strip_tags($blog->data_values->description);
        $seoContents['image']              = getImage('assets/images/frontend/blog/' . @$blog->data_values->image, '1000x700');
        $seoContents['image_size']         = '1000x700';

        return view($this->activeTemplate . 'blog_details', compact('blog', 'pageTitle', 'latestBlogs', 'seoContents'));
    }


    public function cookieAccept()
    {
        $general = gs();
        Cookie::queue('gdpr_cookie', $general->site_name, 43200);
    }

    public function cookiePolicy()
    {
        $pageTitle = 'Cookie Policy';
        $cookie = Frontend::where('data_keys', 'cookie.data')->first();
        return view($this->activeTemplate . 'cookie', compact('pageTitle', 'cookie'));
    }

    public function placeholderImage($size = null)
    {
        $imgWidth = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text = $imgWidth . '×' . $imgHeight;
        $fontFile = realpath('assets/font/RobotoMono-Regular.ttf');
        $fontSize = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 175, 175, 175);
        imagefill($image, 0, 0, $bgFill);
        $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance()
    {
        $pageTitle = 'Maintenance Mode';
        $general = gs();
        if ($general->maintenance_mode == Status::DISABLE) {
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->first();
        return view($this->activeTemplate . 'maintenance', compact('pageTitle', 'maintenance'));
    }

    public function requestCoin(Request $request)
    {
        abort_if(!gs('request_for_coin'), 404);

        $request->validate([
            'amount' => 'required|numeric|gt:0'
        ]);

        $maximumCoin = gs('request_amount');

        if ($maximumCoin < $request->amount) {
            $notify[]       = ['error', "You can request maximum " . showAmount($maximumCoin) . ' ' . gs('cur_text')];
            return back()->withNotify($notify);
        }

        $amount = $request->amount;
        $user   = auth()->user();

        $requestCoin                 = new CoinRequest();
        $requestCoin->user_id        = $user->id;
        $requestCoin->request_number = getTrx();
        $requestCoin->amount         = $amount;
        $requestCoin->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = ' ' . $user->username . ' request for ' . getAmount($amount) . ' ' . gs('cur_text') . '';
        $adminNotification->click_url = route('admin.coin.request.log') . "?search=" . $requestCoin->request_number;
        $adminNotification->save();

        $notify[] = ['success', 'Thank you for your request ' . gs('cur_text') . '. Admin will review your request within a short time'];
        return back()->withNotify($notify);
    }
}
