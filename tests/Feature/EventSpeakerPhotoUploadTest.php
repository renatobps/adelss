<?php

namespace Tests\Feature;

use App\Http\Controllers\Agenda\EventosController;
use App\Models\Event;
use App\Models\EventSpeaker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EventSpeakerPhotoUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Schema::create('event_speakers', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('event_id'); $table->string('name');
            $table->text('description')->nullable(); $table->string('photo_path')->nullable();
            $table->integer('sort_order')->default(0); $table->timestamps();
        });
        Storage::fake('public');
    }

    private function sync(array $rows, array $files = []): void
    {
        $event = new Event(); $event->id = 99;
        $request = Request::create('/', 'POST', ['speakers' => $rows], [], ['speakers' => $files]);
        (new \ReflectionMethod(EventosController::class, 'syncSpeakers'))->invoke(new EventosController(), $event, $request, true);
    }

    public function test_uploaded_photo_is_stored_and_linked_to_the_correct_speaker(): void
    {
        $this->sync([0 => ['name' => 'Samara'], 3 => ['name' => 'Esdras']], [3 => ['photo' => UploadedFile::fake()->image('esdras.jpg')]]);
        $speaker = EventSpeaker::where('name', 'Esdras')->firstOrFail();
        $this->assertNotEmpty($speaker->photo_path);
        Storage::disk('public')->assertExists($speaker->photo_path);
        $this->assertNull(EventSpeaker::where('name', 'Samara')->firstOrFail()->photo_path);
    }

    public function test_storage_failure_is_reported_and_keeps_the_existing_photo_and_row(): void
    {
        Storage::disk('public')->put('events/speakers/old.jpg', 'old photo');
        $previous = EventSpeaker::create(['event_id' => 99, 'name' => 'Renato', 'photo_path' => 'events/speakers/old.jpg']);
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->andReturn(true);
        $file->shouldReceive('store')->once()->with('events/speakers', 'public')->andReturn(false);
        try {
            $this->sync([['name' => 'Renato', 'existing_photo' => 'events/speakers/old.jpg']], [['photo' => $file]]);
            $this->fail('Failed writes must not silently succeed.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('speakers.0.photo', $error->errors());
        }
        $this->assertSame(1, EventSpeaker::count());
        $this->assertSame('events/speakers/old.jpg', $previous->fresh()->photo_path);
        Storage::disk('public')->assertExists('events/speakers/old.jpg');
    }

    public function test_existing_photo_survives_an_edit_without_a_new_upload(): void
    {
        Storage::disk('public')->put('events/speakers/old.jpg', 'old photo');
        EventSpeaker::create(['event_id' => 99, 'name' => 'Renato', 'photo_path' => 'events/speakers/old.jpg']);
        $this->sync([['name' => 'Renato Bento', 'existing_photo' => 'events/speakers/old.jpg']]);
        $this->assertSame('events/speakers/old.jpg', EventSpeaker::firstOrFail()->photo_path);
        Storage::disk('public')->assertExists('events/speakers/old.jpg');
    }

    public function test_replacing_a_photo_removes_the_old_file_only_after_success(): void
    {
        Storage::disk('public')->put('events/speakers/old.jpg', 'old photo');
        EventSpeaker::create(['event_id' => 99, 'name' => 'Renato', 'photo_path' => 'events/speakers/old.jpg']);
        $this->sync([['name' => 'Renato', 'existing_photo' => 'events/speakers/old.jpg']], [['photo' => UploadedFile::fake()->image('new.jpg')]]);
        $path = EventSpeaker::firstOrFail()->photo_path;
        $this->assertNotSame('events/speakers/old.jpg', $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('events/speakers/old.jpg');
    }
    public function test_a_later_failed_upload_cleans_new_files_and_preserves_existing_speakers(): void
    {
        Storage::disk('public')->put('events/speakers/old.jpg', 'old photo');
        EventSpeaker::create(['event_id' => 99, 'name' => 'Renato', 'photo_path' => 'events/speakers/old.jpg']);
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->andReturn(true);
        $file->shouldReceive('store')->once()->andReturn(false);
        try {
            $this->sync([['name' => 'Samara'], ['name' => 'Esdras']], [
                ['photo' => UploadedFile::fake()->image('samara.jpg')], ['photo' => $file],
            ]);
            $this->fail('The second upload must fail.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('speakers.1.photo', $error->errors());
        }
        $this->assertSame('Renato', EventSpeaker::firstOrFail()->name);
        $this->assertSame(['events/speakers/old.jpg'], Storage::disk('public')->allFiles('events/speakers'));
    }

}
