<?php
// create_assets.php - Run once to generate placeholder assets

// Create logo.png (200x200)
$logo = imagecreatetruecolor(200, 200);
$bg = imagecolorallocate($logo, 99, 102, 241); // #6366f1
$text_color = imagecolorallocate($logo, 255, 255, 255);
imagefill($logo, 0, 0, $bg);
$font_size = 60;
imagestring($logo, 5, 60, 85, 'HYLS', $text_color);
imagepng($logo, 'assets/logo.png');
imagedestroy($logo);

// Create favicon.ico (32x32)
$favicon = imagecreatetruecolor(32, 32);
$bg = imagecolorallocate($favicon, 99, 102, 241);
$text_color = imagecolorallocate($favicon, 255, 255, 255);
imagefill($favicon, 0, 0, $bg);
imagestring($favicon, 3, 8, 10, 'HL', $text_color);
imagepng($favicon, 'assets/favicon_temp.png');
imagedestroy($favicon);

// Convert PNG to ICO (basic conversion)
// For production, use proper ICO converter
copy('assets/favicon_temp.png', 'assets/favicon.ico');
unlink('assets/favicon_temp.png');

echo "Assets created successfully!
";
echo "Logo: assets/logo.png
";
echo "Favicon: assets/favicon.ico
";
?>
