<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Warehouse extends Model
{
 protected $fillable = ['name'];
 public function batches(): HasMany { return $this->hasMany(Batch::class); }
}
