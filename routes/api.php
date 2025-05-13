<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RequirementController;
use App\Http\Controllers\Api\CourtController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\PremiumController;
use App\Http\Controllers\Api\HandleWalletController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\ForumQuestionController;
use App\Http\Controllers\ForumAnswerController;
use App\Http\Controllers\ForumNormalPostController;
use App\Http\Controllers\ForumOnlineEventPostController;
use App\Http\Controllers\ForumOfflineEventPostController;
use App\Http\Controllers\ForumOccasionPostController;
use App\Http\Controllers\ForumHiringPostController;
use App\Http\Controllers\ForumPollPostController;
use App\Http\Controllers\Api\ServicesController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\ImagesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\RecipientController;

use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\RepostController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\KudosController;
use App\Http\Controllers\CardTypeController;
use App\Http\Controllers\PositionKudoController;

use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\WorkAniversaryController;
use App\Http\Controllers\Api\EducationMileStoneController;
use App\Http\Controllers\Api\NewPositionController;
use App\Http\Controllers\WelcomeTeamController;
use App\Http\Controllers\Api\ChooseInterestController;
use App\Http\Controllers\Api\TransactionHandlerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TrendingController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PendingStatusController;
use App\Http\Controllers\TrendingHashtagController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\Api\TopicsandGroupController;
use App\Http\Controllers\Api\GroupsController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// User Registration api

Route::controller(UserController::class)->group(function () {
    Route::get('/user/get','index');
    Route::post('/user/register','register');
    Route::post('/user/verification-otp-code','verification');
    Route::post('/user/resend-otp-code','resendotp');
    Route::post('/user/resetpassword','resetpassword');
    Route::post('/user/resetpassword/otp','rpotp_verfication');

});

Route::post('/user/updatepassword',[UserController::class,'updatePassword'])->middleware('auth:sanctum');

// User Login and logout api
Route::middleware('auth:sanctum')->get('/verification-status', [LoginController::class, 'getVerificationStatus']);
Route::post('/user/login',[LoginController::class,'login']);
Route::post('/user/logout',[LoginController::class,'logout'])->middleware('auth:sanctum');


Route::post('/currentuserdetails',[UserController::class,'currentuserdetails'])->middleware('auth:sanctum');

//home api
Route::get('/user/home',[HomeController::class,'index'])->middleware('auth:sanctum');

//CheckIn api
Route::post('/user/check-in',[CheckInController::class,'checkin'])->middleware('auth:sanctum');
Route::get('/user/check-in/history',[CheckInController::class,'history'])->middleware('auth:sanctum');
Route::get('/user/check-in/update',[CheckInController::class,'update'])->middleware('auth:sanctum');

//Fourm Category
Route::get('/user/forum-category',[ForumQuestionController::class,'forumcategory'])->middleware('auth:sanctum');

//Fourm Question api
Route::get('/user/forum-question',[ForumQuestionController::class,'index'])->middleware('auth:sanctum');
Route::post('/user/forum-question/store',[ForumQuestionController::class,'store'])->middleware('auth:sanctum');
Route::post('/user/forum/categories',[ForumQuestionController::class,'bycategories'])->middleware('auth:sanctum');
Route::post('/user/forum-question/reaction',[ForumQuestionController::class,'reaction'])->middleware('auth:sanctum');

//Fourm Answer api
Route::post('/user/forum-answer/',[ForumAnswerController::class,'index'])->middleware('auth:sanctum');
Route::post('/user/forum-answer/store',[ForumAnswerController::class,'store'])->middleware('auth:sanctum');


//Fourm Normal Post Api
Route::get('/user/forum-show-post/',[ForumNormalPostController::class,'index'])->middleware('auth:sanctum');
Route::post('/user/forum-normal-post/store',[ForumNormalPostController::class,'store'])->middleware('auth:sanctum');
//trending hashtags
Route::get('/trending-hashtags', [ForumNormalPostController::class, 'trendingHashtags']);
Route::post('/user/forum-normal-post/reaction',[ForumNormalPostController::class,'reaction'])->middleware('auth:sanctum');
Route::post('/user/forum-normal-post/comment',[ForumNormalPostController::class,'comment'])->middleware('auth:sanctum');
Route::post('/user/forum-normal-post/comment/reaction',[ForumNormalPostController::class,'commentreaction'])->middleware('auth:sanctum');
Route::post('/user/forum-normal-post/showcomment',[ForumNormalPostController::class,'showcomment'])->middleware('auth:sanctum');
Route::post('/user/forum-normal-post/comment/reply',[ForumNormalPostController::class,'reply'])->middleware('auth:sanctum');
Route::post('/user/forum-normal-post/comment/showreply',[ForumNormalPostController::class,'showreply'])->middleware('auth:sanctum');


//Fourm online event Post Api
Route::get('/user/forum-online-event-post/',[ForumOnlineEventPostController::class,'index'])->middleware('auth:sanctum');
Route::post('/user/forum-online-event-post/store',[ForumOnlineEventPostController::class,'store'])->middleware('auth:sanctum');

//Fourm offline event Post Api
Route::get('/user/forum-offline-event-post/',[ForumOfflineEventPostController::class,'index'])->middleware('auth:sanctum');
Route::post('/user/forum-offline-event-post/store',[ForumOfflineEventPostController::class,'store'])->middleware('auth:sanctum');

//Fourm Occasion Post api
Route::get('/user/forum-occasion-post/',[ForumOccasionPostController::class,'index'])->middleware('auth:sanctum');
Route::post('/user/forum-occasion-post/store',[ForumOccasionPostController::class,'store'])->middleware('auth:sanctum');

//Forum Hiring Post api
// In routes/api.php

// Remove authentication middleware for testing
Route::get('/user/forum-hiring-post/', [ForumHiringPostController::class, 'index'])->middleware('auth:sanctum');
Route::post('/user/forum-hiring-post/store', [ForumHiringPostController::class, 'store'])->middleware('auth:sanctum');
Route::post('apply', [ForumHiringPostController::class, 'applyForJob'])->middleware('auth:sanctum');
Route::post('/forum-hiring-post/search', [ForumHiringPostController::class, 'search'])->middleware('auth:sanctum');


//Job Apply api
Route::post('/user/apply-job',[RequirementController::class,'store'])->middleware('auth:sanctum');
Route::get('/user/show-applyed-job', [RequirementController::class, 'applyhistory'])->middleware('auth:sanctum');

//Forum poll Post api
Route::post('/user/forum-poll-post/',[ForumPollPostController::class,'store'])->middleware('auth:sanctum');

// User roll api
Route::post('/user/roll',[ProfileController::class,'userRoll'])->middleware('auth:sanctum');
Route::get('/user/profile/',[ProfileController::class,'index'])->middleware('auth:sanctum');

//card image upload api

Route::post('/user/uploadCardImage',[UserController::class,'uploadCardImage'])->middleware('auth:sanctum');
//Route::post('/user/uploadCardImage',[ProfileController::class,'uploadCardImage'])->middleware('auth:sanctum');


// profile image uploade api
Route::post('/user/uploadProfileImage',[ProfileController::class,'uploadProfileImage'])->middleware('auth:sanctum');
Route::post('/updateBannerImage', [UserController::class, 'updateBannerImage'])->middleware('auth:sanctum');

//profile api
Route::get('/user/profile/',[UserController::class,'getUserProfile'])->middleware('auth:sanctum');
Route::post('user/profile-image-update', [UserController::class, 'updateProfileImage'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->post('/update-profile', [UserController::class, 'updateProfile']);
Route::get('user/{id}', [UserController::class, 'getUserById'])->middleware('auth:sanctum');

//skill api
Route::get('/skills',[SkillController::class,'index'])->middleware('auth:sanctum');

//areaofPractice api

Route::get('/areaofpractice',[AreaController::class,'index'])->middleware('auth:sanctum');

// Search all courts and sub-courts list
Route::post('/search',[SearchController::class,'search'])->middleware('auth:sanctum');

//wallet api
// Route::get('/wallet',[WalletController::class,'wallet'])->middleware('auth:sanctum');
// Route::post('/withdrawal',[WalletController::class,'withdrawal'])->middleware('auth:sanctum');
// Route::post('/withdrawal/history',[WalletController::class,'history'])->middleware('auth:sanctum');

//plan api
Route::get('/user/plans',[PremiumController::class,'index'])->middleware('auth:sanctum');

//Court and sub court api
Route::post('/court',[CourtController::class,'court'])->middleware('auth:sanctum');
Route::post('/subcourt', [CourtController::class, 'subcourt'])->middleware('auth:sanctum');
Route::post('/availableUser', [CourtController::class, 'availableUser']);//->middleware('auth:sanctum');

// services api

Route::get('/services', [ServicesController::class, 'index'])->middleware('auth:sanctum');
Route::post('/services/store', [ServicesController::class, 'store'])->middleware('auth:sanctum');

//poll api

Route::post('/polls', [PollController::class, 'store'])->middleware('auth:sanctum');
Route::get('/polls', [PollController::class, 'getPolls'])->middleware('auth:sanctum');
Route::post('polls/add_reaction',[PollController::class,'reaction'])->middleware('auth:sanctum');
// card api
Route::post('/card', [CardController::class, 'store'])->middleware('auth:sanctum');
Route::post('/update-card', [CardController::class, 'updateCard'])->middleware('auth:sanctum');

//buy coins
// Route::post('/buy/coins', [WalletController::class, 'buyCoins'])->middleware('auth:sanctum');

//  history
// Route::middleware('auth:sanctum')->group(function () {
// Route::get('/transaction/buy-history', [WalletController::class, 'buyHistory']);
// Route::get('/transaction/withdrawal-history', [WalletController::class, 'withdrawalHistory']);
// });

// repost

Route::post('/repost', [RepostController::class, 'store'])->middleware('auth:sanctum');

// follow
// Route::post('/followers/search', [FollowController::class, 'searchFollowingNames'])->middleware('auth:sanctum');
// Route::post('/following-names', [FollowController::class, 'getFollowingNames'])->middleware('auth:sanctum');

// Route::post('/follow', [FollowController::class, 'follow'])->middleware('auth:sanctum');
// Route::post('/unfollow', [FollowController::class, 'unfollow'])->middleware('auth:sanctum');

Route::post('/followers/search', [UserController::class, 'searchFollowingNames'])->middleware('auth:sanctum');
Route::post('/following-names', [UserController::class, 'getFollowingNames'])->middleware('auth:sanctum');

Route::post('/follow', [UserController::class, 'follow'])->middleware('auth:sanctum');
Route::post('/unfollow', [UserController::class, 'unfollow'])->middleware('auth:sanctum');

//kudoes
Route::get('/kudos', [KudosController::class, 'index']);
Route::post('/kudos', [KudosController::class, 'store']);
Route::get('/trending_hashtags_kudos', [KudosController::class, 'trendingHashtags']);
//Route::post('/kudos', [KudosController::class, 'postKudo'])->middleware('auth:sanctum');
Route::post('/card_types', [CardTypeController::class, 'store']);
Route::post('/cardtypes/details', [CardTypeController::class, 'getCardDetails'])->middleware('auth:sanctum');
Route::get('/get_card_types', [CardTypeController::class, 'getcardtypes'])->middleware('auth:sanctum');

//image api 
Route::middleware('auth:sanctum')->post('/new_position', [NewPositionController::class, 'store']);
Route::middleware('auth:sanctum')->get('/new_positions', [NewPositionController::class, 'index']);
Route::middleware('auth:sanctum')->post('/new_certificate', [CertificateController::class, 'store']);
Route::middleware('auth:sanctum')->get('/certificates', [CertificateController::class, 'index']);
Route::middleware('auth:sanctum')->post('/work_aniversary', [WorkAniversaryController::class, 'store']);
Route::middleware('auth:sanctum')->get('/work_aniversaries', [WorkAniversaryController::class, 'index']); 
Route::middleware('auth:sanctum')->post('/education_milestones', [EducationMileStoneController::class, 'store']);
Route::middleware('auth:sanctum')->get('/education_milestones', [EducationMileStoneController::class, 'index']);


// welcome team template
Route::get('/welcome_team', [WelcomeTeamController::class, 'index']);
Route::post('/welcome_team_post', [WelcomeTeamController::class, 'store']);
Route::get('/trending_hashtags_welcome', [WelcomeTeamController::class, 'trendingHashtags']);


//choose interest controller

Route::get('/interests', [ChooseInterestController::class, 'index']);
Route::post('/user-interest', [ChooseInterestController::class, 'store']);
Route::get('/user-interest/{id}', [ChooseInterestController::class, 'show']);

//wallet handle apis new wallet
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wallet', [HandleWalletController::class, 'getWallet']);
    Route::post('/wallet', [HandleWalletController::class, 'postWallet']);
    Route::post('/wallet/withdraw', [HandleWalletController::class, 'withdrawWallet']); 

});

//transaction handle routes

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transaction/add', [HandleWalletController::class, 'postWallet']);
    Route::post('/transaction/withdraw', [HandleWalletController::class, 'withdrawWallet']);
    Route::get('/transactions', [TransactionHandlerController::class, 'getTransactions']);
});
//push notifications
Route::post('/send-notification', [NotificationController::class,'sendPushNotification']);

//trending api old

Route::get('/trending', [TrendingController::class, 'trending']);

//trending hashtag  api old
Route::get('trending-hashtag', [TrendingHashtagController::class, 'index']);

//google login api

Route::post('/login/google', [GoogleAuthController::class, 'googleLogin']);

//show all posts api
Route::middleware('auth:sanctum')->group(function () {
Route::get('/posts', [PostController::class, 'getPosts']);
});

// Route to get user data by ID reload api
Route::get('/profile_status/{id}', [PendingStatusController::class, 'show']);

//responded user
Route::middleware('auth:sanctum')->group(function () {
Route::post('/choices/{choiceId}/respond', [PollController::class, 'respondToChoice'])->name('choices.respond');
});

//forum api
//Route::post('/forum', [ForumController::class, 'store']);

//trending hashtags working api
Route::get('/hashtags', [PostController::class, 'trendinghashtags'])->middleware('auth:sanctum');

//topics and group api routes

Route::get('/topics',[TopicsandGroupController::class,'index'])->middleware('auth:sanctum');
Route::post('topicn_groups', [TopicsandGroupController::class, 'store']);

//groups api routes
Route::get('/groups', [GroupsController::class, 'index'])->middleware('auth:sanctum');
Route::post('/post_group', [GroupsController::class,'store'])->middleware('auth:sanctum');
Route::get('/group_detail/{id}', [GroupsController::class, 'show'])->middleware('auth:sanctum');
