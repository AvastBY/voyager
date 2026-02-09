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
        Schema::table('settings', function (Blueprint $table) {
        	$table->dropUnique(['key']);
        	
            $table->tinyInteger('multilingual')->default(0);
            $table->string('locale')->nullable();
            
            $table->unique(['key', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
        	$table->dropUnique(['key', 'locale']);
        	
            $table->dropColumn('multilingual');
            $table->dropColumn('locale');
            
            $table->unique(['key']);
        });
    }
};
