<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'site_name',
        'logo_path',
    ];

    public function logoPublicUrl(): ?string
    {
        return PublicStorageUrl::fromDiskPath($this->logo_path);
    }
}
