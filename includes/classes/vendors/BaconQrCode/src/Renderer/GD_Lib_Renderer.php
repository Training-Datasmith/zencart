<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer;

use Bacon_Qr_Code\Encoder\Byte_Matrix;
use Bacon_Qr_Code\Encoder\Matrix_Util;
use Bacon_Qr_Code\Encoder\Qr_Code;
use Bacon_Qr_Code\Exception\InvalidArgumentException;
use Bacon_Qr_Code\Exception\RuntimeException;
use Bacon_Qr_Code\Renderer\Color\Alpha;
use Bacon_Qr_Code\Renderer\Color\Color_Interface;
use Bacon_Qr_Code\Renderer\Renderer_Style\Eye_Fill;
use Bacon_Qr_Code\Renderer\Renderer_Style\Fill;
use Gd_Image;
final class Gd_Lib_Renderer implements Renderer_Interface
{
    private ?Gd_Image $image = null;
    /**
     * @var array<string, int>
     */
    private array $colors;
    public function __construct(private readonly int $size, private readonly int $margin = 4, private readonly string $image_format = 'png', private readonly int $compression_quality = 9, private ?Fill $fill = null)
    {
        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            throw new RuntimeException('You need to install the GD extension to use this back end');
        }
        if ($this->fill === null) {
            $this->fill = Fill::default();
        }
        if ($this->fill->has_gradient_fill()) {
            throw new InvalidArgumentException('GDLibRenderer does not support gradients');
        }
    }
    /**
     * @throws InvalidArgumentException if matrix width doesn't match height
     */
    public function render(Qr_Code $qr_code): string
    {
        $matrix = $qr_code->get_matrix();
        $matrix_size = $matrix->get_width();
        if ($matrix_size !== $matrix->get_height()) {
            throw new InvalidArgumentException('Matrix must have the same width and height');
        }
        Matrix_Util::remove_position_detection_patterns($matrix);
        $this->new_image();
        $this->draw($matrix);
        return $this->render_image();
    }
    private function new_image(): void
    {
        $img = imagecreatetruecolor($this->size, $this->size);
        if ($img === false) {
            throw new RuntimeException('Failed to create image of that size');
        }
        $this->image = $img;
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);
        $bg = $this->get_color($this->fill->get_background_color());
        imagefilledrectangle($this->image, 0, 0, $this->size, $this->size, $bg);
        imagealphablending($this->image, true);
    }
    private function draw(Byte_Matrix $matrix): void
    {
        $matrix_size = $matrix->get_width();
        $points_on_side = $matrix->get_width() + $this->margin * 2;
        $point_in_px = $this->size / $points_on_side;
        $this->draw_eye(0, 0, $point_in_px, $this->fill->get_top_left_eye_fill());
        $this->draw_eye($matrix_size - 7, 0, $point_in_px, $this->fill->get_top_right_eye_fill());
        $this->draw_eye(0, $matrix_size - 7, $point_in_px, $this->fill->get_bottom_left_eye_fill());
        $rows = $matrix->get_array()->to_array();
        $color = $this->get_color($this->fill->get_foreground_color());
        for ($y = 0; $y < $matrix_size; $y += 1) {
            for ($x = 0; $x < $matrix_size; $x += 1) {
                if (!$rows[$y][$x]) {
                    continue;
                }
                $points = $this->normalize_points([($this->margin + $x) * $point_in_px, ($this->margin + $y) * $point_in_px, ($this->margin + $x + 1) * $point_in_px, ($this->margin + $y) * $point_in_px, ($this->margin + $x + 1) * $point_in_px, ($this->margin + $y + 1) * $point_in_px, ($this->margin + $x) * $point_in_px, ($this->margin + $y + 1) * $point_in_px]);
                imagefilledpolygon($this->image, $points, $color);
            }
        }
    }
    private function draw_eye(int $x_offset, int $y_offset, float $point_in_px, Eye_Fill $eye_fill): void
    {
        $internal_color = $this->get_color($eye_fill->inherits_internal_color() ? $this->fill->get_foreground_color() : $eye_fill->get_internal_color());
        $external_color = $this->get_color($eye_fill->inherits_external_color() ? $this->fill->get_foreground_color() : $eye_fill->get_external_color());
        for ($y = 0; $y < 7; $y += 1) {
            for ($x = 0; $x < 7; $x += 1) {
                if (($y === 1 || $y === 5) && $x > 0 && $x < 6) {
                    continue;
                }
                if (($x === 1 || $x === 5) && $y > 0 && $y < 6) {
                    continue;
                }
                $points = $this->normalize_points([($this->margin + $x + $x_offset) * $point_in_px, ($this->margin + $y + $y_offset) * $point_in_px, ($this->margin + $x + $x_offset + 1) * $point_in_px, ($this->margin + $y + $y_offset) * $point_in_px, ($this->margin + $x + $x_offset + 1) * $point_in_px, ($this->margin + $y + $y_offset + 1) * $point_in_px, ($this->margin + $x + $x_offset) * $point_in_px, ($this->margin + $y + $y_offset + 1) * $point_in_px]);
                if ($y > 1 && $y < 5 && $x > 1 && $x < 5) {
                    imagefilledpolygon($this->image, $points, $internal_color);
                } else {
                    imagefilledpolygon($this->image, $points, $external_color);
                }
            }
        }
    }
    /**
     * Normalize points will trim right and bottom line by 1 pixel.
     * Otherwise pixels of neighbors are overlapping which leads to issue with transparency and small QR codes.
     */
    private function normalize_points(array $points): array
    {
        $max_x = $max_y = 0;
        for ($i = 0; $i < count($points); $i += 2) {
            // Do manual round as GD just removes decimal part
            $points[$i] = $new_x = round($points[$i]);
            $points[$i + 1] = $new_y = round($points[$i + 1]);
            $max_x = max($max_x, $new_x);
            $max_y = max($max_y, $new_y);
        }
        // Do trimming only if there are 4 points (8 coordinates), assumes this is square.
        for ($i = 0; $i < count($points); $i += 2) {
            $points[$i] = min($points[$i], $max_x - 1);
            $points[$i + 1] = min($points[$i + 1], $max_y - 1);
        }
        return $points;
    }
    private function render_image(): string
    {
        ob_start();
        $quality = $this->compression_quality;
        switch ($this->image_format) {
            case 'png':
                if ($quality > 9 || $quality < 0) {
                    $quality = 9;
                }
                imagepng($this->image, null, $quality);
                break;
            case 'gif':
                imagegif($this->image);
                break;
            case 'jpeg':
            case 'jpg':
                if ($quality > 100 || $quality < 0) {
                    $quality = 85;
                }
                imagejpeg($this->image, null, $quality);
                break;
            default:
                ob_end_clean();
                throw new InvalidArgumentException('Supported image formats are jpeg, png and gif, got: ' . $this->image_format);
        }
        $this->colors = [];
        $this->image = null;
        return ob_get_clean();
    }
    private function get_color(Color_Interface $color): int
    {
        $alpha = 100;
        if ($color instanceof Alpha) {
            $alpha = $color->get_alpha();
            $color = $color->get_base_color();
        }
        $rgb = $color->to_rgb();
        $color_key = sprintf('%02X%02X%02X%02X', $rgb->get_red(), $rgb->get_green(), $rgb->get_blue(), $alpha);
        if (!isset($this->colors[$color_key])) {
            $color_id = imagecolorallocatealpha($this->image, $rgb->get_red(), $rgb->get_green(), $rgb->get_blue(), (int) ((100 - $alpha) / 100 * 127));
            if ($color_id === false) {
                throw new RuntimeException('Failed to create color: #' . $color_key);
            }
            $this->colors[$color_key] = $color_id;
        }
        return $this->colors[$color_key];
    }
}