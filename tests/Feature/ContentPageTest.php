<?php
use App\Models\ContentPage; use Illuminate\Foundation\Testing\RefreshDatabase; use Inertia\Testing\AssertableInertia as Assert;
uses(RefreshDatabase::class);
test('published public page can be viewed', function(){ ContentPage::create(['title'=>'Resources','slug'=>'resources','visibility'=>'public','status'=>'published','menu_location'=>'header','sort_order'=>1,'content_html'=>'<p>Resources</p>']); $this->get('/pages/resources')->assertOk()->assertInertia(fn(Assert $page)=>$page->component('Public/ContentPages/Show')->where('contentPage.slug','resources')); });
test('draft page is not public', function(){ ContentPage::create(['title'=>'Draft','slug'=>'draft','visibility'=>'public','status'=>'draft','menu_location'=>'none','sort_order'=>1]); $this->get('/pages/draft')->assertNotFound(); });
