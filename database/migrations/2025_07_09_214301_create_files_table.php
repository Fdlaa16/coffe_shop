<?php

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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('fileable');

            $table->string('type');
            $table->string('name');
            $table->text('original_name');
            $table->string('extension');
            $table->string('path');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fileable_id', 'fileable_type', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
