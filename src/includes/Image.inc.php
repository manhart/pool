<?php
/**
 * Convert a fraction string to a decimal.
 *
 * @param string $str
 * @return int|float
 * @since 2.5.0
 */
function exif_frac2dec($str)
{
    @list($n, $d) = explode('/', $str);
    if (!empty($d)) {
        return $n / $d;
    }

    return $str;
}


/**
 * Convert the exif date format to a unix timestamp.
 *
 * @param string $str
 * @return int
 * @since 2.5.0
 */
function exif_date2ts($str)
{
    @list($date, $time) = explode(' ', trim($str));
    @list($y, $m, $d) = explode(':', $date);

    return strtotime("{$y}-{$m}-{$d} {$time}");
}

/**
 * Get extended image metadata, exif or iptc as available.
 * Retrieves the EXIF metadata aperture, credit, camera, caption, copyright, iso
 * created_timestamp, focal_length, shutter_speed, and title.
 * The IPTC metadata that is retrieved is APP13, credit, byline, created date
 * and time, caption, copyright, and title. Also includes FNumber, Model,
 * DateTimeDigitized, FocalLength, ISOSpeedRatings, and ExposureTime.
 *
 * @param string $file
 * @return bool|array False on failure. Image metadata array on success.
 * @todo Try other exif libraries if available.
 * @since 2.5.0
 */
function readImageMetadata($file)
{
    list(, , $image_type) = @getimagesize($file);

    $meta = array(
        'aperture' => 0,
        'credit' => '',
        'camera' => '',
        'caption' => '',
        'created_timestamp' => 0,
        'copyright' => '',
        'focal_length' => 0,
        'iso' => 0,
        'shutter_speed' => 0,
        'title' => '',
        'orientation' => 0,
        'keywords' => array(),
    );

    $iptc = array();

    /*
    * Read IPTC first, since it might contain data not available in exif such
    * as caption, description etc.
    */
    if (is_callable('iptcparse')) {
        @getimagesize($file, $info);
        if (!empty($info['APP13'])) {
            $iptc = @iptcparse($info['APP13']);
            // Headline, "A brief synopsis of the caption."
            if (!empty($iptc['2#105'][0])) {
                $meta['title'] = trim($iptc['2#105'][0]);
                /*
                * Title, "Many use the Title field to store the filename of the image,
                * though the field may be used in many ways."
                */
            }
            elseif (!empty($iptc['2#005'][0])) {
                $meta['title'] = trim($iptc['2#005'][0]);
            }

            if (!empty($iptc['2#120'][0])) { // description / legacy caption
                $caption = trim($iptc['2#120'][0]);
                mbstring_binary_safe_encoding();
                $caption_length = strlen($caption);
                reset_mbstring_encoding();
                if (empty($meta['title']) && $caption_length < 80) {
                    // Assume the title is stored in 2:120 if it's short.
                    $meta['title'] = $caption;
                }
                $meta['caption'] = $caption;
            }

            if (!empty($iptc['2#110'][0])) { // credit
                $meta['credit'] = trim($iptc['2#110'][0]);
            }
            elseif (!empty($iptc['2#080'][0])) { // creator / legacy byline
                $meta['credit'] = trim($iptc['2#080'][0]);
            }

            if (!empty($iptc['2#055'][0]) && !empty($iptc['2#060'][0])) { // created date and time
                $meta['created_timestamp'] = strtotime($iptc['2#055'][0].' '.$iptc['2#060'][0]);
            }

            if (!empty($iptc['2#116'][0])) { // copyright
                $meta['copyright'] = trim($iptc['2#116'][0]);
            }

            if (!empty($iptc['2#025'][0])) { // keywords array
                $meta['keywords'] = array_values($iptc['2#025']);
            }
        }
    }

    $exif = array();

    $exif_image_types = array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM);
    if (is_callable('exif_read_data') && in_array($image_type, $exif_image_types)) {
        $exif = @exif_read_data($file);

        if (!empty($exif['ImageDescription'])) {
            mbstring_binary_safe_encoding();
            $description_length = strlen($exif['ImageDescription']);
            reset_mbstring_encoding();
            if (empty($meta['title']) && $description_length < 80) {
                // Assume the title is stored in ImageDescription
                $meta['title'] = trim($exif['ImageDescription']);
            }
            if (empty($meta['caption']) && !empty($exif['COMPUTED']['UserComment'])) {
                $meta['caption'] = trim($exif['COMPUTED']['UserComment']);
            }
            if (empty($meta['caption'])) {
                $meta['caption'] = trim($exif['ImageDescription']);
            }
        }
        elseif (empty($meta['caption']) && !empty($exif['Comments'])) {
            $meta['caption'] = trim($exif['Comments']);
        }

        if (empty($meta['credit'])) {
            if (!empty($exif['Artist'])) {
                $meta['credit'] = trim($exif['Artist']);
            }
            elseif (!empty($exif['Author'])) {
                $meta['credit'] = trim($exif['Author']);
            }
        }

        if (empty($meta['copyright']) && !empty($exif['Copyright'])) {
            $meta['copyright'] = trim($exif['Copyright']);
        }

        if (!empty($exif['FNumber'])) {
            $meta['aperture'] = round(exif_frac2dec($exif['FNumber']), 2);
        }

        if (!empty($exif['Model'])) {
            $meta['camera'] = trim($exif['Model']);
        }

        if (empty($meta['created_timestamp']) && !empty($exif['DateTimeDigitized'])) {
            $meta['created_timestamp'] = exif_date2ts($exif['DateTimeDigitized']);
        }

        if (!empty($exif['FocalLength'])) {
            $meta['focal_length'] = (string)exif_frac2dec($exif['FocalLength']);
        }

        if (!empty($exif['ISOSpeedRatings'])) {
            $meta['iso'] = is_array($exif['ISOSpeedRatings']) ? reset($exif['ISOSpeedRatings']) : $exif['ISOSpeedRatings'];
            $meta['iso'] = trim($meta['iso']);
        }

        if (!empty($exif['ExposureTime'])) {
            $meta['shutter_speed'] = (string)exif_frac2dec($exif['ExposureTime']);
        }

        if (!empty($exif['Orientation'])) {
            $meta['orientation'] = $exif['Orientation'];
        }
    }

    if(!empty($iptc) or !empty($exif)) {
        foreach (array('title', 'caption', 'credit', 'copyright', 'camera', 'iso') as $key) {
            if ($meta[$key] && !seems_utf8($meta[$key])) {
                $meta[$key] = utf8_encode($meta[$key]);
            }
        }

        foreach ($meta['keywords'] as $key => $keyword) {
            if (!seems_utf8($keyword)) {
                $meta['keywords'][$key] = utf8_encode($keyword);
            }
        }

        return $meta;
    }

    return false;
}

/**
 * Automatically rotates image in the right orientation.
 *
 * @param resource $image
 * @param int $orientation from Metadata
 * @return bool
 * @see readImageMetadata()
 */
function fixImageOrientation(&$image, $orientation)
{
    switch ($orientation) {
        case 3:
            $image = imagerotate($image, 180, 0);
            return true;

        case 6:
            $image = imagerotate($image, -90, 0);
            return true;

        case 8:
            $image = imagerotate($image, 90, 0);
            return true;
    }

    return false;
}

/**
 * Adjust picture to height.
 *
 * @param resource $im
 * @param int $max_height
 * @return false|resource
 */
function resizeImageToHeight($im, $max_height)
{
    $ratio = $max_height / imagesy($im);
    $width = imagesx($im) * $ratio;
    return resizeImage($im, $width, $max_height);
}

/**
 * Adjust picture to width.
 *
 * @param resource $im
 * @param int $max_width
 * @return false|resource
 */
function resizeImageToWidth($im, $max_width)
{
    $ratio = $max_width / imagesx($im);
    $height = imagesy($im) * $ratio;
    return resizeImage($im, $max_width, $height);
}

/**
 * changes the dimensions of the image to best fit.
 *
 * @param resource $im
 * @param int $max_width
 * @param int $max_height
 * @return false|resource
 */
function resizeImageToBestFit($im, int $max_width, int $max_height)
{
    $originalWidth = imagesx($im);
    $originalHeight = imagesy($im);

    if ($originalWidth <= $max_width and $originalHeight <= $max_height) {
        imagealphablending($im, false);
        imagesavealpha($im, true);
        return $im;
    }

    $ratio = $originalHeight / $originalWidth;
    $width = $max_width;
    $height = intval($width * $ratio);
    if ($height > $max_height) {
        $height = $max_height;
        $width = intval($height / $ratio);
    }

    return resizeImage($im, $width, $height);
}

/**
 * changes the dimensions of the image
 *
 * https://christianwood.net/posts/png-files-are-complicate/
 *
 * @param resource $im
 * @param int $width
 * @param int $height
 * @return false|resource
 */
function resizeImage($im, int $width, int $height)
{
    $source_x = $dest_x = 0;
    $source_y = $dest_y = 0;
    $source_w = imagesx($im);
    $source_h = imagesy($im);
    $dest_w = $width;
    $dest_h = $height;

    $dest_im = imagecreatetruecolor($dest_w, $dest_h);

    imagealphablending($dest_im, false);
    imagesavealpha($dest_im, true);
    $transparent = imagecolorallocatealpha($dest_im, 255, 255, 255, 127);
    imagefilledrectangle($dest_im, 0, 0, $source_w, $source_h, $transparent);

    imagecopyresampled(
        $dest_im,
        $im,
        $dest_x,
        $dest_y,
        $source_x,
        $source_y,
        $dest_w,
        $dest_h,
        $source_w,
        $source_h
    );

    return $dest_im;
}