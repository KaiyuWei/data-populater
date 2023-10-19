<?php
/**
 * This model stands for files that are being processed or fail to be processed.
 * When such a file is successfully processed, it will be deleted from the table
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalFile extends Model
{
    use HasFactory;
}
