<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryBook extends Model
{
    protected $table = 'library_books';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'pdf_file',
        'pdf_url',
        'cover_image',
        'category',
        'major',
        'created_at'
    ];
}
