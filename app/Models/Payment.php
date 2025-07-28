<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'session_id','value','log_id','invoice_id','status'];

        public function invoice(){
            return $this->belongsTo(Invoice::class);
        }


}
