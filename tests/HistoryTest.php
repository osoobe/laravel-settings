<?php

namespace Osoobe\Laravel\Settings\Tests;

use Osoobe\Laravel\Settings\Tests\Fixtures\Post;

class HistoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // created event
    // -------------------------------------------------------------------------

    public function test_history_is_created_on_model_create()
    {
        $post = Post::create(['title' => 'New Post', 'status' => 'draft']);

        // getMetaHistory(false) bypasses the Eloquent relation cache
        $history = $post->getMetaHistory(false);
        $this->assertIsArray($history);
        $this->assertCount(1, $history);
    }

    public function test_first_history_entry_has_created_status()
    {
        $post = Post::create(['title' => 'New Post', 'status' => 'pending']);

        $entry = $post->getMetaHistory(false)[0];
        $this->assertSame('created', $entry['history_status']);
    }

    public function test_first_history_entry_contains_tracked_fields()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'pending']);

        $entry = $post->getMetaHistory(false)[0];
        $this->assertArrayHasKey('status', $entry);
        $this->assertSame('pending', $entry['status']);
    }

    public function test_first_history_entry_does_not_contain_untracked_fields()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);

        $entry = $post->getMetaHistory(false)[0];
        $this->assertArrayNotHasKey('title', $entry);
    }

    public function test_first_history_entry_has_a_timestamp()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);

        $entry = $post->getMetaHistory(false)[0];
        $this->assertArrayHasKey('timestamp', $entry);
        $this->assertIsInt($entry['timestamp']);
    }

    // -------------------------------------------------------------------------
    // updated event
    // -------------------------------------------------------------------------

    public function test_history_is_appended_on_model_update()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $history = $post->getMetaHistory(false);
        $this->assertCount(2, $history);
    }

    public function test_updated_entry_has_updated_status()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $history = $post->getMetaHistory(false);
        $lastEntry = end($history);
        $this->assertSame('updated', $lastEntry['history_status']);
    }

    public function test_updated_entry_contains_new_value_of_changed_tracked_field()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $history = $post->getMetaHistory(false);
        $lastEntry = end($history);
        $this->assertArrayHasKey('status', $lastEntry);
        $this->assertSame('published', $lastEntry['status']);
    }

    public function test_multiple_updates_accumulate_in_history()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);
        $post->update(['status' => 'review']);
        $post->update(['status' => 'published']);

        $this->assertCount(3, $post->getMetaHistory(false));
    }

    // -------------------------------------------------------------------------
    // getMetaHistory
    // -------------------------------------------------------------------------

    public function test_get_meta_history_returns_null_before_any_event_fires()
    {
        // On a fresh unsaved model, no history meta exists yet.
        $post = new Post(['title' => 'Draft', 'status' => 'draft']);
        $this->assertNull($post->getMetaValue('history', false));
    }

    // -------------------------------------------------------------------------
    // updateMetaHistory — purge
    // -------------------------------------------------------------------------

    public function test_update_meta_history_with_purge_resets_history()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $post->updateMetaHistory(['history_status' => 'reset'], true);

        $this->assertCount(1, $post->getMetaHistory(false));
    }

    public function test_update_meta_history_without_purge_appends()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);
        $beforeCount = count($post->getMetaHistory(false));

        $post->updateMetaHistory(['custom' => 'entry', 'history_status' => 'manual']);

        // Use false to bypass stale relation cache
        $this->assertCount($beforeCount + 1, $post->getMetaHistory(false));
    }

    // -------------------------------------------------------------------------
    // metaTrack
    // -------------------------------------------------------------------------

    public function test_meta_track_determines_which_fields_are_recorded()
    {
        $post = Post::create(['title' => 'Title', 'status' => 'draft']);
        $entry = $post->getMetaHistory(false)[0];

        // 'status' is in metaTrack(), 'title' is not
        $this->assertArrayHasKey('status', $entry);
        $this->assertArrayNotHasKey('title', $entry);
    }
}
