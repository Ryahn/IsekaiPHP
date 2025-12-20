<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->string('type')->default('string'); // string, json, boolean, integer, float
    $table->text('description')->nullable();
    $table->string('group')->nullable(); // For grouping settings (e.g., 'app', 'mail', 'cache')
    $table->timestamps();
});
