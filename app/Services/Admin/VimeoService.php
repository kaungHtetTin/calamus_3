<?php

namespace App\Services\Admin;

use Vimeo\Vimeo;
use Illuminate\Support\Facades\Log;

class VimeoService
{
    protected Vimeo $vimeo;

    public function __construct()
    {
        $clientId     = config('services.vimeo.client_id');
        $clientSecret = config('services.vimeo.client_secret');
        $accessToken  = config('services.vimeo.access_token');

        if (!$clientId || !$clientSecret || !$accessToken) {
            throw new \Exception('Vimeo credentials are not configured.');
        }

        $this->vimeo = new Vimeo($clientId, $clientSecret, $accessToken);
    }

    /**
     * Upload video directly to Vimeo (no projects/folders)
     * Uses pathSegments to build unique video name to avoid duplicates
     *
     * @param mixed $file The uploaded file
     * @param string $title Video title
     * @param array $pathSegments Array of path segments to build unique video name (e.g., ['Language', 'Course', 'Category'] or ['Language', 'Course', 'Preview'])
     * @return string Player URL
     * @throws \Exception
     */
    public function uploadVideo(
        $file,
        string $title,
        array $pathSegments
    ): string {
        try {
            // Build unique video name from title and path segments to avoid duplicates
            $videoName = $this->buildVideoName($title, $pathSegments);

            // Upload video directly (no project attachment)
            $videoUri = $this->vimeo->upload(
                $file->getRealPath(),
                [
                    'name'        => $videoName,
                    'description' => 'Uploaded via API'
                ]
            );

            if (!$videoUri) {
                throw new \Exception('Video upload failed.');
            }

            // Return player URL
            return $this->buildPlayerUrl($videoUri);

        } catch (\Exception $e) {
            Log::error('Vimeo upload error', [
                'message' => $e->getMessage(),
                'path_segments' => $pathSegments,
                'title' => $title
            ]);

            throw $e;
        }
    }

    /* =========================================================
       Video name handling
       ========================================================= */

    /**
     * Build unique video name from title and path segments
     * Format: "Title - Segment1 / Segment2 / Segment3"
     * 
     * @param string $title Video title
     * @param array $pathSegments Array of path segment strings
     * @return string Unique video name
     */
    protected function buildVideoName(string $title, array $pathSegments): string
    {
        // Filter out empty segments and trim each segment
        $cleanSegments = array_filter(
            array_map('trim', $pathSegments),
            function($segment) {
                return !empty($segment);
            }
        );

        // Capitalize first letter of first segment (typically language)
        if (!empty($cleanSegments)) {
            $cleanSegments = array_values($cleanSegments); // Re-index array
            $cleanSegments[0] = ucfirst($cleanSegments[0]);
        }

        // Build path string
        $pathString = implode(' / ', $cleanSegments);

        // Combine title with path segments to create unique name
        if (!empty($pathString)) {
            return trim($title . ' - ' . $pathString);
        }

        return trim($title);
    }

    /**
     * Delete video from Vimeo
     * 
     * @param string $playerUrlOrVideoId Player URL (e.g., "https://player.vimeo.com/video/123456?title=0...") or video ID (e.g., "123456") or video URI (e.g., "/videos/123456")
     * @return bool True if deletion was successful, false otherwise
     * @throws \Exception
     */
    public function deleteVideo(string $playerUrlOrVideoId): bool
    {
        try {
            // Extract video ID from various input formats
            $videoId = $this->extractVideoId($playerUrlOrVideoId);

            if (!$videoId) {
                Log::warning('Vimeo delete: Could not extract video ID', [
                    'input' => $playerUrlOrVideoId
                ]);
                return false;
            }

            // Build video URI
            $videoUri = "/videos/{$videoId}";

            // Delete video using Vimeo API
            $response = $this->vimeo->request($videoUri, [], 'DELETE');

            $statusCode = $response['status'] ?? 0;

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info('Vimeo video deleted successfully', [
                    'video_id' => $videoId,
                    'video_uri' => $videoUri
                ]);
                return true;
            } else {
                Log::warning('Vimeo delete failed', [
                    'video_id' => $videoId,
                    'status_code' => $statusCode,
                    'response' => $response
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Vimeo delete error', [
                'message' => $e->getMessage(),
                'input' => $playerUrlOrVideoId,
                'trace' => $e->getTraceAsString()
            ]);

            // Don't throw exception - return false to allow graceful handling
            return false;
        }
    }

    /* =========================================================
       Helper methods
       ========================================================= */

    /**
     * Format video duration from seconds to human-readable format
     * 
     * @param int|float $seconds Duration in seconds
     * @return string Formatted duration (e.g., "1hr 20min 2sec", "3min", "2min 30sec")
     */
    public static function formatDuration($seconds): string
    {
        $seconds = (int) abs($seconds);
        
        if ($seconds === 0) {
            return '0sec';
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        $parts = [];
        
        if ($hours > 0) {
            $parts[] = $hours . 'hr';
        }
        
        if ($minutes > 0) {
            $parts[] = $minutes . 'min';
        }
        
        if ($secs > 0) {
            $parts[] = $secs . 'sec';
        }
        
        return implode(' ', $parts);
    }

    /**
     * Extract video ID from various URL formats
     * 
     * @param string $input Player URL, video ID, or video URI
     * @return string|null Video ID or null if not found
     */
    protected function extractVideoId(string $input): ?string
    {
        if (empty($input)) {
            return null;
        }

        // If it's already just a numeric ID
        if (preg_match('/^\d+$/', trim($input))) {
            return trim($input);
        }

        // Extract from player URL: https://player.vimeo.com/video/123456?...
        if (preg_match('/player\.vimeo\.com\/video\/(\d+)/', $input, $matches)) {
            return $matches[1];
        }

        // Extract from vimeo.com URL: https://vimeo.com/123456
        if (preg_match('/vimeo\.com\/(\d+)/', $input, $matches)) {
            return $matches[1];
        }

        // Extract from video URI: /videos/123456
        if (preg_match('/\/videos\/(\d+)/', $input, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /* =========================================================
       Player URL
       ========================================================= */

    /**
     * Build player URL by fetching video details from Vimeo API
     * For unlisted videos, this retrieves the required hash parameter
     *
     * @param string $videoUri The video URI (e.g., "/videos/123456")
     * @return string The player embed URL with hash parameter
     * @throws \Exception
     */
    protected function buildPlayerUrl(string $videoUri): string
    {
        $videoId = basename($videoUri);
        
        // Fetch video details from Vimeo API to get the privacy hash
        $hash = $this->fetchVideoHash($videoUri);
        
        // Build player URL with hash parameter
        $playerUrl = "https://player.vimeo.com/video/{$videoId}";
        
        if ($hash) {
            $playerUrl .= "?h={$hash}";
        }
        
        // Add additional player parameters
        $playerUrl .= ($hash ? '&' : '?') . 'badge=0&autopause=0&player_id=0&app_id=58479';
        
        return $playerUrl;
    }

    /**
     * Fetch the privacy hash for a video from Vimeo API
     * The hash is required for unlisted videos to be playable
     *
     * @param string $videoUri The video URI (e.g., "/videos/123456")
     * @return string|null The hash parameter or null if not found/needed
     */
    protected function fetchVideoHash(string $videoUri): ?string
    {
        try {
            // Request video details from Vimeo API
            // The 'link' field contains the full URL with hash for unlisted videos
            $response = $this->vimeo->request($videoUri, [], 'GET');
            
            if (!isset($response['body'])) {
                Log::warning('Vimeo API response missing body', [
                    'video_uri' => $videoUri,
                    'response' => $response
                ]);
                return null;
            }
            
            $body = $response['body'];
            
            // Method 1: Extract hash from the 'link' field
            // Unlisted video links have format: https://vimeo.com/{video_id}/{hash}
            if (!empty($body['link'])) {
                $hash = $this->extractHashFromLink($body['link']);
                if ($hash) {
                    return $hash;
                }
            }
            
            // Method 2: Check privacy.embed field if available
            // Some responses include embed.html with the hash
            if (!empty($body['embed']['html'])) {
                $hash = $this->extractHashFromEmbed($body['embed']['html']);
                if ($hash) {
                    return $hash;
                }
            }
            
            // Method 3: Check if video URI contains hash (format: /videos/{id}:{hash})
            if (strpos($videoUri, ':') !== false) {
                $parts = explode(':', basename($videoUri));
                if (count($parts) === 2) {
                    return $parts[1];
                }
            }
            
            // No hash found - video might be public
            return null;
            
        } catch (\Exception $e) {
            Log::warning('Failed to fetch video hash from Vimeo', [
                'video_uri' => $videoUri,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extract hash from Vimeo video link
     * Unlisted links have format: https://vimeo.com/{video_id}/{hash}
     *
     * @param string $link The video link
     * @return string|null The extracted hash or null
     */
    protected function extractHashFromLink(string $link): ?string
    {
        // Match pattern: vimeo.com/{video_id}/{hash}
        // The hash is typically 10 alphanumeric characters
        if (preg_match('/vimeo\.com\/\d+\/([a-zA-Z0-9]+)/', $link, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Extract hash from embed HTML
     * Embed iframe src contains: player.vimeo.com/video/{id}?h={hash}
     *
     * @param string $embedHtml The embed HTML code
     * @return string|null The extracted hash or null
     */
    protected function extractHashFromEmbed(string $embedHtml): ?string
    {
        // Match pattern: h={hash} in the embed URL
        if (preg_match('/[?&]h=([a-zA-Z0-9]+)/', $embedHtml, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
