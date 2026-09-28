<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotRule extends Model
{
    protected $table = 'chatbot_rules'; 
    
    protected $fillable = ['keyword', 'response', 'category', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(ChatbotRule::class, 'parent_id');
    }
}