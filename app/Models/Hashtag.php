<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hashtag extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'useCount'
];

    public function normalposts()
    {
        return $this->morphedByMany(ForumNormalPost::class, 'postable', 'hashtag_post');
    }

    public function kudos()
    {
        return $this->morphedByMany(Kudo::class, 'postable', 'hashtag_post');
    }

    public function welcome()
    {
        return $this->morphedByMany(Welcome::class, 'postable', 'hashtag_post');
    }

    public function newposition()
    {
        return $this->morphedByMany(NewPosition::class, 'postable', 'hashtag_post');
    }

    public function education()
    {
        return $this->morphedByMany(Education::class, 'postable', 'hashtag_post');
    }
    public function services()
    {
        return $this->morphedByMany(Services::class, 'postable', 'hashtag_post');
    }
    public function eventoffline()
    {
        return $this->morphedByMany(ForumEventOfflinePost::class, 'postable', 'hashtag_post');
    }
    public function eventonline(){
        return $this->morphedByMany(ForumEventOnlinePost::class, 'postable', 'hashtag_post');
    }
    public function certificates(){
        return $this->morphedByMany(Certificate::class, 'postable', 'hashtag_post');
    }
    public function workaniversary(){
        return $this->morphedByMany(WorkAniversary::class, 'postable', 'hashtag_post');
    }

// Method to get the total count of posts associated with this hashtag
      public function getTotalCountAttribute()
    {
          return $this->normalposts()->count() + $this->kudos()->count() + $this->welcome()->count() + $this->newposition()->count() + $this->education()->count() + $this->services()->count() + $this->eventoffline()->count() + $this->eventonline()->count() + $this->certificates()->count() + $this->workaniversary()->count();
    }
}
