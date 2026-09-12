<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LandscapingServiceVisualsTest extends TestCase
{
    public function test_landscaping_services_page_loads_and_renders_all_six_service_cards_with_images(): void
    {
        $response = $this->get(route('services.landscaping'));
        $response->assertOk();

        $expectedServices = [
            [
                'title' => 'Garden Landscaping',
                'image' => 'images/landscaping/garden-landscaping.jpg',
            ],
            [
                'title' => 'Residential Landscaping',
                'image' => 'images/landscaping/residential-landscaping.jpg',
            ],
            [
                'title' => 'Commercial Landscaping',
                'image' => 'images/landscaping/commercial-landscaping.jpg',
            ],
            [
                'title' => 'Lawn & Garden Development',
                'image' => 'images/landscaping/lawn-garden-development.jpg',
            ],
            [
                'title' => 'Planting & Plantation',
                'image' => 'images/landscaping/planting-plantation.jpg',
            ],
            [
                'title' => 'Garden Maintenance',
                'image' => 'images/landscaping/garden-maintenance.jpg',
            ],
        ];

        foreach ($expectedServices as $srv) {
            $response->assertSee($srv['title']);
            $response->assertSee($srv['image']);

            $filePath = public_path($srv['image']);
            $this->assertTrue(File::exists($filePath), "Image asset '{$srv['image']}' must exist on disk.");
            $this->assertGreaterThan(0, File::size($filePath), "Image asset '{$srv['image']}' must not be empty.");
        }
    }

    public function test_commercial_landscaping_serves_as_the_visual_for_school_and_institutional_campus_grounds(): void
    {
        $response = $this->get(route('services.landscaping'));
        $response->assertOk();

        // Commercial landscaping card contains institutional grounds description and commercial image
        $response->assertSee('Commercial Landscaping');
        $response->assertSee('institutional grounds');
        $response->assertSee('images/landscaping/commercial-landscaping.jpg');
    }

    public function test_landscaping_is_not_converted_to_a_product_category(): void
    {
        $this->assertFalse(
            Category::where('name', 'Landscaping')->orWhere('slug', 'landscaping')->exists(),
            'Landscaping must strictly remain a service and not be present in the categories table.'
        );
    }
}
