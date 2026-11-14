<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventStorageUrlTest extends TestCase
{
    public function test_local_event_photos_follow_the_current_page_instead_of_a_stale_tunnel(): void
    {
        config(['filesystems.disks.public.driver' => 'local',
            'filesystems.disks.public.url' => 'https://expired-tunnel.example.test/storage']);
        $this->app['url']->setRequest(Request::create('https://eventos.example.test/evento/imersao'));

        $this->assertSame('https://eventos.example.test/storage/events/speakers/photo.jpg', Event::publicStorageUrl('events/speakers/photo.jpg'));
        $this->assertSame('https://eventos.example.test/storage/events/speakers/photo.jpg', Event::publicStorageUrl('/storage/events/speakers/photo.jpg'));
    }

    public function test_absolute_external_photo_urls_are_preserved(): void
    {
        $this->assertSame('https://images.example.test/photo.jpg', Event::publicStorageUrl('https://images.example.test/photo.jpg'));
        $this->assertNull(Event::publicStorageUrl(null));
        $this->assertNull(Event::publicStorageUrl(' '));
    }

    public function test_remote_public_disks_keep_the_storage_provider_url(): void
    {
        config(['filesystems.disks.public.driver' => 's3']);
        $disk = \Mockery::mock();
        $disk->shouldReceive('url')->once()->with('events/speakers/photo.jpg')->andReturn('https://cdn.example.test/events/speakers/photo.jpg');
        Storage::shouldReceive('disk')->once()->with('public')->andReturn($disk);
        $this->assertSame('https://cdn.example.test/events/speakers/photo.jpg', Event::publicStorageUrl('events/speakers/photo.jpg'));
    }
}
