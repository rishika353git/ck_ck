<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// use Laravel\Passport\HasApiTokens; //added

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

  //  protected $table='users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'profile',
        'bannerImage',
        'card_front',
        'card_back',
        'year_of_enrollment',
        'current_designation',
        'previous_experiences',
        'home_courts',
        'area_of_practice',
        'law_school',
        'batch',
        'linkedin_profile',
        'description',
        'profile_tagline',
        'total_judgments_given',
        'top_5_skills',
        'total_judgment_asked',
        'total_sitation_asked',
        'total_sitation_given',
        'total_follow',
        'total_followers',
        'questions_asked',
        'answers_given',
        'total_earning',
        'total_spend',
        'post',
        'drafts_uploaded',
        'rating_average',
        'following_id',
        'follower_id',
        // Add fcm_token to the fillable fields
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'top_5_skills' => 'array',
        'home_courts'=>'array',
        'area_of_practice'=>'array',

        //'previous_experiences'=>'array',
    ];

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id');
    }

    public function positionKudos()
    {
        return $this->hasMany(PositionKudo::class);
    }

    public function kudos()
    {
        return $this->hasMany(Kudo::class, 'following_id');
    }
  public function interests()
{
    return $this->belongsToMany(Interest::class, 'user_interest', 'user_id', 'interest_id');
 
}


}

