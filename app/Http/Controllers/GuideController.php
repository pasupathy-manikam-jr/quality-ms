<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

class GuideController extends Controller
{
    /**
     * The user guide in the reader's language (resources/guide/<locale>.md), English when missing.
     */
    public function __invoke(): View
    {
        $path = resource_path('guide/'.app()->getLocale().'.md');
        $path = is_file($path) ? $path : resource_path('guide/en.md');

        $html = Str::markdown((string) file_get_contents($path), [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'heading_permalink' => ['symbol' => '#', 'id_prefix' => '', 'fragment_prefix' => '', 'insert' => 'after', 'min_heading_level' => 2, 'max_heading_level' => 3, 'aria_hidden' => true, 'title' => ''],
            'table_of_contents' => ['position' => 'placeholder', 'placeholder' => '[TOC]', 'min_heading_level' => 2, 'max_heading_level' => 2, 'html_class' => 'guide-toc'],
        ], [new HeadingPermalinkExtension, new TableOfContentsExtension]);

        return view('guide', ['html' => $html]);
    }
}
