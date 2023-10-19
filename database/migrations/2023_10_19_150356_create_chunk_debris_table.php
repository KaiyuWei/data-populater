<?php
/**
 * One row in this table stands for one chunk that was failed to be added in the data table.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chunk_debris', function (Blueprint $table) {
            $table->id();
            // the file id of which this debris from
            $table->unsignedBigInteger('file_id');
            $table->foreign('file_id')->references('id')->on('external_files');
            // the start point in bytes in the file of this chunk
            $table->integer('start_point');
            // the size of this debris
            $table->integer('chunk_size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chunk_debris');
    }
};
