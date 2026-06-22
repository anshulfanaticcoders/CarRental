<?php

namespace Tests\Unit;

use App\Helpers\ImageCompressionHelper;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageCompressionHelperTest extends TestCase
{
    public function test_vehicle_image_validation_accepts_short_landscape_images(): void
    {
        $image = UploadedFile::fake()->image('short-landscape.jpg', 1376, 686);

        $this->assertNull(ImageCompressionHelper::validateVehicleImageUpload($image));
    }

    public function test_vehicle_image_validation_accepts_portrait_images(): void
    {
        $image = UploadedFile::fake()->image('portrait.jpg', 1200, 1600);

        $this->assertNull(ImageCompressionHelper::validateVehicleImageUpload($image));
    }

    public function test_vehicle_image_validation_rejects_unreadable_files(): void
    {
        $file = UploadedFile::fake()->createWithContent('not-an-image.jpg', 'not an image');

        $this->assertSame(
            'We could not read the image dimensions.',
            ImageCompressionHelper::validateVehicleImageUpload($file)
        );
    }
}
