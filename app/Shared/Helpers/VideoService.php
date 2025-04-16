<?php

namespace App\Shared\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;

class VideoService
{
    /**
     * Extract a poster image from a video file
     *
     * @param string $videoPath Full path to the video file
     * @param string $storageDisk Storage disk to use
     * @param string $destinationPath Path where to save the poster
     * @return string|null URL of the generated poster or null on failure
     */
    public function extractPoster(string $videoPath, string $storageDisk, string $destinationPath): ?string
    {
        try {
            // Create FFMpeg instance
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
                'ffprobe.binaries' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
                'timeout' => 3600, // 1 hour
                'ffmpeg.threads' => 12,
            ]);

            // Get the video file from storage
            $tempVideoPath = tempnam(sys_get_temp_dir(), 'video_');
            file_put_contents($tempVideoPath, Storage::disk($storageDisk)->get($videoPath));

            // Open the video file
            $video = $ffmpeg->open($tempVideoPath);

            // Generate a poster at 1 second
            $posterFileName = Str::random(20) . '.jpg';
            $tempPosterPath = sys_get_temp_dir() . '/' . $posterFileName;

            $video->frame(TimeCode::fromSeconds(1))
                ->save($tempPosterPath);

            // Upload the poster to storage
            $posterPath = $destinationPath . '/' . $posterFileName;
            Storage::disk($storageDisk)->put(
                $posterPath,
                file_get_contents($tempPosterPath),
            );
            
            // Clean up temporary files
            @unlink($tempVideoPath);
            @unlink($tempPosterPath);

            return $posterPath;
        } catch (\Exception $e) {
            Log::error('Failed to extract video poster: ' . $e->getMessage(), [
                'video_path' => $videoPath,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Extract a poster image from a video file using a fallback method
     * This method uses GD library which is more commonly available
     *
     * @param UploadedFile $videoFile The uploaded video file
     * @param string $storageDisk Storage disk to use
     * @param string $destinationPath Path where to save the poster
     * @return string|null URL of the generated poster or null on failure
     */
    public function extractPosterFallback(UploadedFile $videoFile, string $storageDisk, string $destinationPath): ?string
    {
        try {
            // Create a temporary file for the video
            $tempVideoPath = $videoFile->getRealPath();

            // Use getid3 to get video metadata
            $getID3 = new \getID3;
            $fileInfo = $getID3->analyze($tempVideoPath);

            // Check if we can extract frames
            if (!isset($fileInfo['video']['resolution_x']) || !isset($fileInfo['video']['resolution_y'])) {
                return null;
            }

            // Create a GD image with video dimensions
            $width = $fileInfo['video']['resolution_x'];
            $height = $fileInfo['video']['resolution_y'];
            $image = imagecreatetruecolor($width, $height);

            // Fill with black background as fallback
            $black = imagecolorallocate($image, 0, 0, 0);
            imagefill($image, 0, 0, $black);

            // Add video filename as text
            $white = imagecolorallocate($image, 255, 255, 255);
            $text = "Video: " . $videoFile->getClientOriginalName();
            imagestring($image, 5, 20, $height/2, $text, $white);

            // Save the image
            $posterFileName = Str::random(20) . '.jpg';
            $tempPosterPath = sys_get_temp_dir() . '/' . $posterFileName;
            imagejpeg($image, $tempPosterPath, 90);
            imagedestroy($image);

            // Upload the poster to storage
            $posterPath = $destinationPath . '/' . $posterFileName;
            Storage::disk($storageDisk)->put(
                $posterPath,
                file_get_contents($tempPosterPath)
            );

            // Clean up
            @unlink($tempPosterPath);

            return $posterPath;
        } catch (\Exception $e) {
            Log::error('Failed to extract video poster with fallback: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return null;
        }
    }
}
