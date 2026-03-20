<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer;

use Bacon_Qr_Code\Encoder\Matrix_Util;
use Bacon_Qr_Code\Encoder\Qr_Code;
use Bacon_Qr_Code\Exception\InvalidArgumentException;
use Bacon_Qr_Code\Renderer\Image\Image_Back_End_Interface;
use Bacon_Qr_Code\Renderer\Path\Path;
use Bacon_Qr_Code\Renderer\Renderer_Style\Eye_Fill;
use Bacon_Qr_Code\Renderer\Renderer_Style\Renderer_Style;
final readonly class Image_Renderer implements Renderer_Interface
{
    public function __construct(private Renderer_Style $renderer_style, private Image_Back_End_Interface $image_back_end)
    {
    }
    /**
     * @throws InvalidArgumentException if matrix width doesn't match height
     */
    public function render(Qr_Code $qr_code): string
    {
        $size = $this->renderer_style->get_size();
        $margin = $this->renderer_style->get_margin();
        $matrix = $qr_code->get_matrix();
        $matrix_size = $matrix->get_width();
        if ($matrix_size !== $matrix->get_height()) {
            throw new InvalidArgumentException('Matrix must have the same width and height');
        }
        $total_size = $matrix_size + $margin * 2;
        $module_size = $size / $total_size;
        $fill = $this->renderer_style->get_fill();
        $this->image_back_end->new($size, $fill->get_background_color());
        $this->image_back_end->scale((float) $module_size);
        $this->image_back_end->translate((float) $margin, (float) $margin);
        $module = $this->renderer_style->get_module();
        $module_matrix = clone $matrix;
        Matrix_Util::remove_position_detection_patterns($module_matrix);
        $module_path = $this->draw_eyes($matrix_size, $module->create_path($module_matrix));
        if ($fill->has_gradient_fill()) {
            $this->image_back_end->draw_path_with_gradient($module_path, $fill->get_foreground_gradient(), 0, 0, $matrix_size, $matrix_size);
        } else {
            $this->image_back_end->draw_path_with_color($module_path, $fill->get_foreground_color());
        }
        return $this->image_back_end->done();
    }
    private function draw_eyes(int $matrix_size, Path $module_path): Path
    {
        $fill = $this->renderer_style->get_fill();
        $eye = $this->renderer_style->get_eye();
        $external_path = $eye->get_external_path();
        $internal_path = $eye->get_internal_path();
        $module_path = $this->draw_eye($external_path, $internal_path, $fill->get_top_left_eye_fill(), 3.5, 3.5, 0, $module_path);
        $module_path = $this->draw_eye($external_path, $internal_path, $fill->get_top_right_eye_fill(), $matrix_size - 3.5, 3.5, 90, $module_path);
        $module_path = $this->draw_eye($external_path, $internal_path, $fill->get_bottom_left_eye_fill(), 3.5, $matrix_size - 3.5, -90, $module_path);
        return $module_path;
    }
    private function draw_eye(Path $external_path, Path $internal_path, Eye_Fill $fill, float $x_translation, float $y_translation, int $rotation, Path $module_path): Path
    {
        if ($fill->inherits_both_colors()) {
            return $module_path->append($external_path->rotate($rotation)->translate($x_translation, $y_translation))->append($internal_path->rotate($rotation)->translate($x_translation, $y_translation));
        }
        $this->image_back_end->push();
        $this->image_back_end->translate($x_translation, $y_translation);
        if (0 !== $rotation) {
            $this->image_back_end->rotate($rotation);
        }
        if ($fill->inherits_external_color()) {
            $module_path = $module_path->append($external_path->rotate($rotation)->translate($x_translation, $y_translation));
        } else {
            $this->image_back_end->draw_path_with_color($external_path, $fill->get_external_color());
        }
        if ($fill->inherits_internal_color()) {
            $module_path = $module_path->append($internal_path->rotate($rotation)->translate($x_translation, $y_translation));
        } else {
            $this->image_back_end->draw_path_with_color($internal_path, $fill->get_internal_color());
        }
        $this->image_back_end->pop();
        return $module_path;
    }
}