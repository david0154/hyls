<?php
/**
 * Video Embed Helper Functions
 * Handles automatic video URL detection and embed code generation with autoplay
 */

class VideoEmbed {
    
    /**
     * Detect platform from URL
     */
    public static function detectPlatform($url) {
        $url = strtolower($url);
        
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return 'youtube';
        } elseif (strpos($url, 'facebook.com') !== false || strpos($url, 'fb.watch') !== false) {
            return 'facebook';
        } elseif (strpos($url, 'instagram.com') !== false) {
            return 'instagram';
        } elseif (strpos($url, 'tiktok.com') !== false) {
            return 'tiktok';
        } elseif (strpos($url, 'vimeo.com') !== false) {
            return 'vimeo';
        } elseif (strpos($url, 'dailymotion.com') !== false) {
            return 'dailymotion';
        } elseif (strpos($url, 'twitter.com') !== false || strpos($url, 'x.com') !== false) {
            return 'twitter';
        } elseif (strpos($url, 'twitch.tv') !== false) {
            return 'twitch';
        }
        
        return 'unknown';
    }
    
    /**
     * Generate embed code with autoplay
     */
    public static function generateEmbed($url, $platform, $autoplay = true) {
        $autoplayParam = $autoplay ? 1 : 0;
        
        switch ($platform) {
            case 'youtube':
                return self::embedYouTube($url, $autoplayParam);
            case 'facebook':
                return self::embedFacebook($url, $autoplayParam);
            case 'instagram':
                return self::embedInstagram($url, $autoplayParam);
            case 'tiktok':
                return self::embedTikTok($url, $autoplayParam);
            case 'vimeo':
                return self::embedVimeo($url, $autoplayParam);
            case 'dailymotion':
                return self::embedDailymotion($url, $autoplayParam);
            case 'twitter':
                return self::embedTwitter($url);
            case 'twitch':
                return self::embedTwitch($url, $autoplayParam);
            default:
                return '';
        }
    }
    
    /**
     * YouTube embed
     */
    private static function embedYouTube($url, $autoplay) {
        // Extract video ID
        preg_match('/[\?\&]v=([^\?\&]+)/', $url, $matches);
        if (!$matches) {
            preg_match('/youtu\.be\/([^\?\&]+)/', $url, $matches);
        }
        
        if (empty($matches[1])) return '';
        
        $videoId = $matches[1];
        $embedUrl = "https://www.youtube.com/embed/{$videoId}?autoplay={$autoplay}&mute=1&rel=0&modestbranding=1";
        
        return '<iframe width="100%" height="315" src="' . htmlspecialchars($embedUrl) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    }
    
    /**
     * Facebook embed
     */
    private static function embedFacebook($url, $autoplay) {
        $encodedUrl = urlencode($url);
        $autoplayParam = $autoplay ? 'true' : 'false';
        $embedUrl = "https://www.facebook.com/plugins/video.php?href={$encodedUrl}&show_text=false&autoplay={$autoplayParam}&mute=1";
        
        return '<iframe width="100%" height="315" src="' . htmlspecialchars($embedUrl) . '" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>';
    }
    
    /**
     * Instagram embed (Reels/Videos)
     */
    private static function embedInstagram($url, $autoplay) {
        // Instagram requires oEmbed API or direct embed
        // Extract post ID
        preg_match('/\/p\/([^\/\?]+)/', $url, $matches);
        if (empty($matches[1])) {
            preg_match('/\/reel\/([^\/\?]+)/', $url, $matches);
        }
        
        if (empty($matches[1])) return '';
        
        $postId = $matches[1];
        $embedUrl = "https://www.instagram.com/p/{$postId}/embed/";
        
        return '<iframe width="100%" height="500" src="' . htmlspecialchars($embedUrl) . '" frameborder="0" scrolling="no" allowtransparency="true"></iframe>';
    }
    
    /**
     * TikTok embed
     */
    private static function embedTikTok($url, $autoplay) {
        // Extract video ID
        preg_match('/\/video\/([0-9]+)/', $url, $matches);
        if (empty($matches[1])) return '';
        
        $videoId = $matches[1];
        $embedUrl = "https://www.tiktok.com/embed/{$videoId}";
        
        return '<iframe width="100%" height="500" src="' . htmlspecialchars($embedUrl) . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
    }
    
    /**
     * Vimeo embed
     */
    private static function embedVimeo($url, $autoplay) {
        preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
        if (empty($matches[1])) return '';
        
        $videoId = $matches[1];
        $embedUrl = "https://player.vimeo.com/video/{$videoId}?autoplay={$autoplay}&muted=1";
        
        return '<iframe width="100%" height="315" src="' . htmlspecialchars($embedUrl) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
    }
    
    /**
     * Dailymotion embed
     */
    private static function embedDailymotion($url, $autoplay) {
        preg_match('/dailymotion\.com\/video\/([^_\?]+)/', $url, $matches);
        if (empty($matches[1])) return '';
        
        $videoId = $matches[1];
        $embedUrl = "https://www.dailymotion.com/embed/video/{$videoId}?autoplay={$autoplay}&mute=1";
        
        return '<iframe width="100%" height="315" src="' . htmlspecialchars($embedUrl) . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>';
    }
    
    /**
     * Twitter/X embed
     */
    private static function embedTwitter($url) {
        // Twitter requires their embed script
        $tweetUrl = htmlspecialchars($url);
        return '<blockquote class="twitter-tweet" data-theme="dark"><a href="' . $tweetUrl . '"></a></blockquote><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
    }
    
    /**
     * Twitch embed
     */
    private static function embedTwitch($url, $autoplay) {
        // Extract channel or video
        if (strpos($url, '/videos/') !== false) {
            preg_match('/\/videos\/(\d+)/', $url, $matches);
            if (!empty($matches[1])) {
                $embedUrl = "https://player.twitch.tv/?video={$matches[1]}&parent=" . $_SERVER['HTTP_HOST'] . "&autoplay={$autoplay}";
                return '<iframe width="100%" height="315" src="' . htmlspecialchars($embedUrl) . '" frameborder="0" allowfullscreen></iframe>';
            }
        }
        return '';
    }
    
    /**
     * Get thumbnail from URL (basic implementation)
     */
    public static function getThumbnail($url, $platform) {
        switch ($platform) {
            case 'youtube':
                preg_match('/[\?\&]v=([^\?\&]+)/', $url, $matches);
                if (!$matches) {
                    preg_match('/youtu\.be\/([^\?\&]+)/', $url, $matches);
                }
                if (!empty($matches[1])) {
                    return "https://img.youtube.com/vi/{$matches[1]}/maxresdefault.jpg";
                }
                break;
            case 'vimeo':
                preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
                if (!empty($matches[1])) {
                    return "https://vumbnail.com/{$matches[1]}.jpg";
                }
                break;
        }
        return '';
    }
}
?>