<?php

use App\Models\FileDescriptor;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration for use with "novice"
 */
class FileMigration {
    function run()
    {
        Capsule::schema()->dropIfExists('file');
        Capsule::schema()->create('file', function($table) {
            $table->uuid('id');
            $table->integer('creator_id');
            $table->text('file_name');
            $table->string('file_type');
            $table->integer('file_size');
            $table->enum('modifier', FileDescriptor::MODIFIER);
            $table->json('json_append');
            $table->timestamps();
            $table->primary('id');
        });
    }
}
